import logging
from datetime import datetime, timedelta

from odoo import api, fields, models, _
from odoo.exceptions import UserError

_logger = logging.getLogger(__name__)


class EmpayPayrun(models.Model):
    _name = 'empay.payrun'
    _description = 'EmPay Pay Run'
    _inherit = ['mail.thread', 'mail.activity.mixin']
    _order = 'date_from desc'

    name = fields.Char(string='Pay Run Name', required=True, tracking=True)
    date_from = fields.Date(string='Period Start', required=True, tracking=True)
    date_to = fields.Date(string='Period End', required=True, tracking=True)
    state = fields.Selection([
        ('draft', 'Draft'),
        ('confirmed', 'Confirmed'),
        ('paid', 'Paid'),
    ], string='Status', default='draft', tracking=True, index=True)
    payslip_ids = fields.One2many('empay.payslip', 'payrun_id', string='Payslips')
    total_employees = fields.Integer(string='Total Employees', compute='_compute_totals', store=True)
    total_gross = fields.Float(string='Total Gross', compute='_compute_totals', store=True)
    total_net = fields.Float(string='Total Net', compute='_compute_totals', store=True)
    total_pf = fields.Float(string='Total PF', compute='_compute_totals', store=True)
    company_id = fields.Many2one('res.company', string='Company',
                                  default=lambda self: self.env.company, required=True)

    @api.depends('payslip_ids', 'payslip_ids.gross_salary', 'payslip_ids.net_salary', 'payslip_ids.pf_employee')
    def _compute_totals(self):
        for payrun in self:
            slips = payrun.payslip_ids
            payrun.total_employees = len(slips)
            payrun.total_gross = sum(slips.mapped('gross_salary'))
            payrun.total_net = sum(slips.mapped('net_salary'))
            payrun.total_pf = sum(slips.mapped('pf_employee'))

    def action_generate_payslips(self):
        self.ensure_one()
        if self.state != 'draft':
            raise UserError(_('Can only generate payslips for draft payruns.'))
        employees = self.env['hr.employee'].search([
            ('active', '=', True),
        ])
        if not employees:
            raise UserError(_('No active employees found.'))

        payslip_vals = []
        for emp in employees:
            # Odoo 19: hr.contract is now hr.version
            version = self.env['hr.version'].search([
                ('employee_id', '=', emp.id),
            ], limit=1, order='id desc')
            if not version:
                continue
            payslip_vals.append({
                'employee_id': emp.id,
                'contract_id': version.id,
                'date_from': self.date_from,
                'date_to': self.date_to,
                'name': _('%s - %s') % (emp.name, self.name),
                'payrun_id': self.id,
            })

        if payslip_vals:
            payslips = self.env['empay.payslip'].create(payslip_vals)
            for slip in payslips:
                try:
                    slip.compute_sheet()
                except Exception as e:
                    _logger.warning('Error computing sheet for %s: %s', slip.employee_id.name, e)

        self.write({'state': 'confirmed'})
        self._notify_dashboard('payrun_confirmed', 'Payrun "%s" confirmed with %d payslips.' % (self.name, len(payslip_vals)))
        return True

    def action_mark_paid(self):
        self.ensure_one()
        if self.state != 'confirmed':
            raise UserError(_('Only confirmed payruns can be marked as paid.'))
        self.write({'state': 'paid'})
        self.message_post(body=_('Payrun marked as paid. %d payslips processed.') % len(self.payslip_ids))
        self._notify_dashboard('payrun_paid', 'Payrun "%s" marked as paid.' % self.name)

    def action_view_payslips(self):
        """Open the list of payslips for this payrun (used by smart buttons)."""
        self.ensure_one()
        return {
            'type': 'ir.actions.act_window',
            'name': _('Payslips - %s') % self.name,
            'res_model': 'empay.payslip',
            'view_mode': 'list,form',
            'domain': [('payrun_id', '=', self.id)],
            'context': {'default_payrun_id': self.id},
        }

    def action_reset_draft(self):
        self.ensure_one()
        if self.state == 'paid':
            raise UserError(_('Cannot reset a paid payrun to draft.'))
        self.payslip_ids.unlink()
        self.write({'state': 'draft'})
        self._notify_dashboard('payrun_reset', 'Payrun "%s" reset to draft.' % self.name)

    @api.model
    def get_dashboard_stats(self):
        today = fields.Date.today()
        total_employees = self.env['hr.employee'].search_count([('active', '=', True)])

        today_dt_start = datetime.combine(today, datetime.min.time())
        today_dt_end = datetime.combine(today, datetime.max.time())
        today_start = fields.Datetime.to_string(today_dt_start)
        today_end = fields.Datetime.to_string(today_dt_end)
        present_today = self.env['hr.attendance'].search_count([
            ('check_in', '>=', today_start),
            ('check_in', '<=', today_end),
        ])

        pending_leaves = self.env['hr.leave'].search_count([('state', '=', 'confirm')])

        first_of_month = today.replace(day=1)
        current_payruns = self.search([
            ('date_from', '>=', first_of_month),
            ('date_to', '<=', today),
        ])
        month_net = sum(current_payruns.mapped('total_net'))

        on_leave_today = self.env['hr.leave'].search_count([
            ('state', '=', 'validate'),
            ('date_from', '<=', today_end),
            ('date_to', '>=', today_start),
        ])

        # Late Arrival: Check-in after 09:15 AM
        late_threshold = today_dt_start.replace(hour=9, minute=15)
        late_arrivals = self.env['hr.attendance'].search_count([
            ('check_in', '>', fields.Datetime.to_string(late_threshold)),
            ('check_in', '<=', today_end),
        ])

        # Early Departure: Check-out before 05:30 PM
        early_threshold = today_dt_start.replace(hour=17, minute=30)
        early_departures = self.env['hr.attendance'].search_count([
            ('check_out', '<', fields.Datetime.to_string(early_threshold)),
            ('check_out', '>=', today_start),
        ])

        last_payrun = self.search([], order='date_to desc', limit=1)
        last_payrun_info = {
            'name': last_payrun.name if last_payrun else 'N/A',
            'state': last_payrun.state if last_payrun else 'N/A',
            'total_net': last_payrun.total_net if last_payrun else 0,
        }

        # Monthly attendance for last 6 months
        monthly_attendance = []
        for i in range(5, -1, -1):
            d = today - timedelta(days=30 * i)
            m, y = d.month, d.year
            from calendar import monthrange
            _, last_day = monthrange(y, m)
            m_start = fields.Datetime.to_string(datetime(y, m, 1))
            m_end = fields.Datetime.to_string(datetime(y, m, last_day, 23, 59, 59))
            count = self.env['hr.attendance'].search_count([
                ('check_in', '>=', m_start), ('check_in', '<=', m_end),
            ])
            monthly_attendance.append({'month': d.strftime('%b %Y'), 'count': count})

        # Leave distribution by type
        leave_types = self.env['hr.leave.type'].search([])
        leave_distribution = []
        for lt in leave_types:
            count = self.env['hr.leave'].search_count([
                ('holiday_status_id', '=', lt.id),
                ('state', '=', 'validate'),
            ])
            if count > 0:
                leave_distribution.append({'type': lt.name, 'count': count})

        # Recent Activities (Last 5 attendances)
        recent_attendances = self.env['hr.attendance'].search([], limit=5, order='id desc')
        recent_activities = []
        for att in recent_attendances:
            emp_name = att.employee_id.name or 'Unknown'
            initials = "".join([n[0].upper() for n in emp_name.split()[:2]]) if emp_name else 'U'
            act_time = att.check_out or att.check_in
            time_str = fields.Datetime.to_string(act_time) if act_time else ''
            recent_activities.append({
                'employee_name': emp_name,
                'employee_initials': initials,
                'type': 'Check Out' if att.check_out else 'Check In',
                'time': time_str,
                'status': att.status or 'present',
            })

        # Payroll cost trend last 6 months
        payroll_trend = []
        for i in range(5, -1, -1):
            d = today - timedelta(days=30 * i)
            m, y = d.month, d.year
            from calendar import monthrange
            _, last_day = monthrange(y, m)
            runs = self.search([
                ('date_from', '>=', datetime(y, m, 1).strftime('%Y-%m-%d')),
                ('date_to', '<=', datetime(y, m, last_day).strftime('%Y-%m-%d')),
            ])
            payroll_trend.append({'month': d.strftime('%b %Y'), 'total': sum(runs.mapped('total_net'))})

        # User Role and Employee Specific Data
        is_admin = self.env.is_admin() or \
                   self.env.user.has_group('empay_hrms.group_admin') or \
                   self.env.user.has_group('empay_hrms.group_hr_officer') or \
                   self.env.user.has_group('empay_hrms.group_payroll_officer')
        
        employee_data = {}
        if not is_admin:
            employee = self.env.user.employee_id
            if employee:
                # Leave Balances
                leave_balances = []
                for lt in leave_types:
                    # Odoo Community doesn't have easy remaining_days, so we'll just sum allocations - leaves
                    allocs = self.env['hr.leave.allocation'].search([
                        ('employee_id', '=', employee.id),
                        ('holiday_status_id', '=', lt.id),
                        ('state', '=', 'validate')
                    ])
                    taken = self.env['hr.leave'].search([
                        ('employee_id', '=', employee.id),
                        ('holiday_status_id', '=', lt.id),
                        ('state', '=', 'validate')
                    ])
                    remaining = sum(allocs.mapped('number_of_days')) - sum(taken.mapped('number_of_days'))
                    leave_balances.append({'type': lt.name, 'remaining': remaining})
                
                # Latest Payslip
                latest_slip = self.env['empay.payslip'].search([
                    ('employee_id', '=', employee.id),
                    ('state', '=', 'done')
                ], limit=1, order='date_to desc')
                
                employee_data = {
                    'id': employee.id,
                    'name': employee.name,
                    'job_title': employee.job_title or 'Employee',
                    'emp_id': employee.empay_employee_id or 'N/A',
                    'leave_balances': leave_balances,
                    'latest_slip': {
                        'basic': latest_slip.prorated_basic if latest_slip else 0,
                        'hra': latest_slip.hra if latest_slip else 0,
                        'transport': latest_slip.transport_allowance if latest_slip else 0,
                        'pf': latest_slip.pf_employee if latest_slip else 0,
                        'net': latest_slip.net_salary if latest_slip else 0,
                    } if latest_slip else None
                }

        return {
            'is_admin': is_admin,
            'employee_data': employee_data,
            'total_employees': total_employees,
            'present_today': present_today,
            'pending_leaves': pending_leaves,
            'month_net_payroll': month_net,
            'on_leave_today': on_leave_today,
            'late_arrivals': late_arrivals,
            'early_departures': early_departures,
            'last_payrun': last_payrun_info,
            'monthly_attendance': monthly_attendance,
            'leave_distribution': leave_distribution,
            'payroll_trend': payroll_trend,
            'recent_activities': recent_activities,
        }

    def _notify_dashboard(self, event, message):
        """Send a bus notification to all connected dashboard clients.

        Args:
            event (str): Event type identifier (e.g. 'attendance_checkin').
            message (str): Human-readable message for the toast notification.
        """
        try:
            self.env['bus.bus']._sendone(
                'empay_dashboard',
                'empay_dashboard',
                {'event': event, 'message': message},
            )
        except Exception as e:
            _logger.debug('Bus notification failed (non-critical): %s', e)
