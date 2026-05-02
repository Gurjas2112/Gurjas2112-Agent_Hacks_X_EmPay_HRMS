import logging
from datetime import datetime, timedelta

from odoo import api, fields, models, _

_logger = logging.getLogger(__name__)


class EmpayPayslip(models.Model):
    """EmPay Payslip — standalone payroll model for Community Edition.

    This replaces the Enterprise hr.payslip with a fully self-contained
    payslip model that computes salary from attendance data.
    """

    _name = 'empay.payslip'
    _description = 'EmPay Payslip'
    _inherit = ['mail.thread', 'mail.activity.mixin']
    _order = 'date_from desc, employee_id'

    # ------------------------------------------------------------------
    # Core Fields
    # ------------------------------------------------------------------
    name = fields.Char(string='Payslip Name', required=True, tracking=True)
    employee_id = fields.Many2one('hr.employee', string='Employee',
                                   required=True, tracking=True, index=True)
    contract_id = fields.Many2one('hr.version', string='Contract',
                                   tracking=True)
    payrun_id = fields.Many2one('empay.payrun', string='Pay Run',
                                 ondelete='cascade', index=True)
    date_from = fields.Date(string='Period Start', required=True, tracking=True)
    date_to = fields.Date(string='Period End', required=True, tracking=True)
    state = fields.Selection([
        ('draft', 'Draft'),
        ('done', 'Done'),
        ('cancelled', 'Cancelled'),
    ], string='Status', default='draft', tracking=True, index=True)
    company_id = fields.Many2one('res.company', string='Company',
                                  default=lambda self: self.env.company, required=True)

    # ------------------------------------------------------------------
    # Attendance Summary (computed from hr.attendance)
    # ------------------------------------------------------------------
    present_days = fields.Float(string='Present Days',
                                 compute='_compute_attendance_days', store=True)
    total_working_days = fields.Float(string='Total Working Days',
                                       compute='_compute_attendance_days', store=True)
    absent_days = fields.Float(string='Absent Days',
                                compute='_compute_attendance_days', store=True)
    leave_days = fields.Float(string='Leave Days',
                               compute='_compute_attendance_days', store=True)
    half_days = fields.Float(string='Half Days',
                              compute='_compute_attendance_days', store=True)

    # ------------------------------------------------------------------
    # Salary Fields (computed from contract + attendance)
    # ------------------------------------------------------------------
    basic_wage = fields.Float(string='Basic Wage',
                               compute='_compute_empay_salary', store=True)
    prorated_basic = fields.Float(string='Prorated Basic',
                                   compute='_compute_empay_salary', store=True)
    hra = fields.Float(string='HRA',
                        compute='_compute_empay_salary', store=True)
    transport_allowance = fields.Float(string='Transport Allowance',
                                        compute='_compute_empay_salary', store=True)
    pf_employee = fields.Float(string='PF (Employee)',
                                compute='_compute_empay_salary', store=True)
    pf_employer = fields.Float(string='PF (Employer)',
                                compute='_compute_empay_salary', store=True)
    professional_tax = fields.Float(string='Professional Tax',
                                     compute='_compute_empay_salary', store=True)
    gross_salary = fields.Float(string='Gross Salary',
                                 compute='_compute_empay_salary', store=True)
    net_salary = fields.Float(string='Net Salary',
                               compute='_compute_empay_salary', store=True)

    # ------------------------------------------------------------------
    # Attendance Computation
    # ------------------------------------------------------------------
    @api.depends('employee_id', 'date_from', 'date_to')
    def _compute_attendance_days(self):
        for payslip in self:
            if not payslip.employee_id or not payslip.date_from or not payslip.date_to:
                payslip.present_days = payslip.total_working_days = 0
                payslip.absent_days = payslip.leave_days = payslip.half_days = 0
                continue

            date_from = payslip.date_from
            date_to = payslip.date_to
            total_working_days = 0
            current = date_from
            while current <= date_to:
                if current.weekday() < 5:
                    total_working_days += 1
                current += timedelta(days=1)

            attendances = self.env['hr.attendance'].search([
                ('employee_id', '=', payslip.employee_id.id),
                ('check_in', '>=', fields.Datetime.to_string(
                    datetime.combine(date_from, datetime.min.time()))),
                ('check_in', '<=', fields.Datetime.to_string(
                    datetime.combine(date_to, datetime.max.time()))),
            ])
            present = len(attendances.filtered(lambda a: a.status == 'present'))
            half = len(attendances.filtered(lambda a: a.status == 'half_day'))

            leaves = self.env['hr.leave'].search([
                ('employee_id', '=', payslip.employee_id.id),
                ('state', '=', 'validate'),
                ('date_from', '<=', fields.Datetime.to_string(
                    datetime.combine(date_to, datetime.max.time()))),
                ('date_to', '>=', fields.Datetime.to_string(
                    datetime.combine(date_from, datetime.min.time()))),
            ])
            leave_count = sum(leaves.mapped('number_of_days'))
            effective_present = present + (half * 0.5)
            absent = max(0, total_working_days - effective_present - leave_count)

            payslip.present_days = present
            payslip.half_days = half
            payslip.total_working_days = total_working_days
            payslip.leave_days = leave_count
            payslip.absent_days = absent

    # ------------------------------------------------------------------
    # Salary Computation
    # ------------------------------------------------------------------
    @api.depends('employee_id', 'present_days', 'total_working_days',
                 'leave_days', 'half_days')
    def _compute_empay_salary(self):
        TRANSPORT = 1600.0
        for payslip in self:
            contract = payslip.contract_id
            if not contract:
                # Odoo 19: hr.contract is now hr.version
                Version = self.env['hr.version']
                contract = Version.search([
                    ('employee_id', '=', payslip.employee_id.id),
                ], limit=1, order='id desc')
            wage = contract.wage if contract else 0.0
            payslip.basic_wage = wage
            if not wage or not payslip.total_working_days:
                payslip.prorated_basic = payslip.hra = payslip.transport_allowance = 0
                payslip.pf_employee = payslip.pf_employer = payslip.professional_tax = 0
                payslip.gross_salary = payslip.net_salary = 0
                continue

            eff = payslip.present_days + (payslip.half_days * 0.5) + payslip.leave_days
            ratio = min(eff / payslip.total_working_days, 1.0)
            prorated = round(wage * ratio, 2)
            payslip.prorated_basic = prorated
            payslip.hra = round(prorated * 0.40, 2)
            payslip.transport_allowance = round(TRANSPORT * ratio, 2)
            gross = prorated + payslip.hra + payslip.transport_allowance
            payslip.gross_salary = round(gross, 2)
            payslip.pf_employee = round(prorated * 0.12, 2)
            payslip.pf_employer = round(prorated * 0.12, 2)

            # Professional Tax slabs
            if gross <= 10000:
                payslip.professional_tax = 0
            elif gross <= 15000:
                payslip.professional_tax = 150
            elif gross <= 25000:
                payslip.professional_tax = 200
            else:
                payslip.professional_tax = 300

            payslip.net_salary = round(
                gross - payslip.pf_employee - payslip.professional_tax, 2)

    # ------------------------------------------------------------------
    # Actions
    # ------------------------------------------------------------------
    def action_confirm(self):
        """Mark payslip as done."""
        for slip in self:
            slip.write({'state': 'done'})

    def action_cancel(self):
        """Cancel payslip."""
        for slip in self:
            slip.write({'state': 'cancelled'})

    def action_reset_draft(self):
        """Reset to draft."""
        for slip in self:
            slip.write({'state': 'draft'})

    def compute_sheet(self):
        """Trigger recomputation of salary fields (called by payrun)."""
        for slip in self:
            # Force recompute by writing employee_id (triggers depends chain)
            slip._compute_attendance_days()
            slip._compute_empay_salary()
        return True
