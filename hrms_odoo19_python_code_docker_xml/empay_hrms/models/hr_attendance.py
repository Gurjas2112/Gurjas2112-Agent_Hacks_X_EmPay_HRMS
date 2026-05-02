import logging
from datetime import datetime, time, timedelta
import calendar

from odoo import api, fields, models, _

_logger = logging.getLogger(__name__)


class HrAttendance(models.Model):
    """Extend hr.attendance with EmPay status tracking and analytics."""

    _inherit = 'hr.attendance'

    # ------------------------------------------------------------------
    # Fields
    # ------------------------------------------------------------------
    status = fields.Selection(
        selection=[
            ('present', 'Present'),
            ('absent', 'Absent'),
            ('half_day', 'Half Day'),
            ('on_leave', 'On Leave'),
        ],
        string='Status',
        compute='_compute_status',
        store=True,
        help='Attendance status computed from worked hours.',
    )
    late_minutes = fields.Float(
        string='Late Minutes',
        compute='_compute_late_minutes',
        store=True,
        help='Minutes late from the standard 9:00 AM check-in.',
    )
    overtime_hours = fields.Float(
        string='Overtime Hours',
        compute='_compute_overtime_hours',
        store=True,
        help='Hours worked beyond the standard 8-hour day.',
    )

    # ------------------------------------------------------------------
    # Computed Fields
    # ------------------------------------------------------------------
    @api.depends('check_in', 'check_out', 'worked_hours')
    def _compute_status(self):
        """Determine attendance status from worked hours."""
        for record in self:
            if not record.check_in:
                record.status = 'absent'
            elif not record.check_out:
                # Checked in but not checked out yet — mark as present (in-progress)
                record.status = 'present'
            elif record.worked_hours >= 6.0:
                record.status = 'present'
            elif record.worked_hours >= 3.0:
                record.status = 'half_day'
            else:
                record.status = 'absent'

    @api.depends('check_in')
    def _compute_late_minutes(self):
        """Calculate minutes late from the standard 9:00 AM start time."""
        standard_start = time(9, 0, 0)
        for record in self:
            if record.check_in:
                check_in_time = fields.Datetime.context_timestamp(
                    record, record.check_in
                ).time()
                if check_in_time > standard_start:
                    delta = datetime.combine(datetime.min, check_in_time) - \
                            datetime.combine(datetime.min, standard_start)
                    record.late_minutes = delta.total_seconds() / 60.0
                else:
                    record.late_minutes = 0.0
            else:
                record.late_minutes = 0.0

    @api.depends('worked_hours')
    def _compute_overtime_hours(self):
        """Calculate overtime beyond 8-hour standard working day."""
        STANDARD_HOURS = 8.0
        for record in self:
            if record.worked_hours and record.worked_hours > STANDARD_HOURS:
                record.overtime_hours = record.worked_hours - STANDARD_HOURS
            else:
                record.overtime_hours = 0.0

    # ------------------------------------------------------------------
    # CRUD Overrides — Real-Time Bus Notifications
    # ------------------------------------------------------------------
    @api.model_create_multi
    def create(self, vals_list):
        """Override create to send bus notification on check-in."""
        records = super().create(vals_list)
        for record in records:
            emp_name = record.employee_id.name or 'Employee'
            self._notify_dashboard(
                'attendance_checkin',
                '%s checked in.' % emp_name,
            )
        return records

    def write(self, vals):
        """Override write to send bus notification on check-out."""
        result = super().write(vals)
        if 'check_out' in vals:
            for record in self:
                emp_name = record.employee_id.name or 'Employee'
                self._notify_dashboard(
                    'attendance_checkout',
                    '%s checked out.' % emp_name,
                )
        return result

    def _notify_dashboard(self, event, message):
        """Send bus notification to connected dashboard clients."""
        try:
            self.env['bus.bus']._sendone(
                'empay_dashboard',
                'empay_dashboard',
                {'event': event, 'message': message},
            )
        except Exception as e:
            _logger.debug('Bus notification failed (non-critical): %s', e)

    # ------------------------------------------------------------------
    # Business Methods
    # ------------------------------------------------------------------
    @api.model
    def get_monthly_summary(self, employee_id, month, year):
        """Return a dict summarizing attendance for a given employee/month/year.

        Args:
            employee_id (int): ID of the hr.employee record
            month (int): 1-12
            year (int): e.g. 2026

        Returns:
            dict: {
                'present_days': int,
                'absent_days': int,
                'half_days': int,
                'leave_days': int,
                'total_worked_hours': float,
                'overtime_hours': float,
            }
        """
        # Determine date range for the month
        _, last_day = calendar.monthrange(year, month)
        date_from = datetime(year, month, 1)
        date_to = datetime(year, month, last_day, 23, 59, 59)

        # Fetch attendance records for the employee in the period
        attendances = self.search([
            ('employee_id', '=', employee_id),
            ('check_in', '>=', fields.Datetime.to_string(date_from)),
            ('check_in', '<=', fields.Datetime.to_string(date_to)),
        ])

        present_days = len(attendances.filtered(lambda a: a.status == 'present'))
        half_days = len(attendances.filtered(lambda a: a.status == 'half_day'))
        total_worked_hours = sum(attendances.mapped('worked_hours'))
        overtime_hours = sum(attendances.mapped('overtime_hours'))

        # Count approved leaves in the period
        leave_days = 0.0
        leaves = self.env['hr.leave'].search([
            ('employee_id', '=', employee_id),
            ('state', '=', 'validate'),
            ('date_from', '<=', fields.Datetime.to_string(date_to)),
            ('date_to', '>=', fields.Datetime.to_string(date_from)),
        ])
        for leave in leaves:
            leave_days += leave.number_of_days

        # Calculate working days in the month (Mon-Fri)
        total_working_days = 0
        for day in range(1, last_day + 1):
            if datetime(year, month, day).weekday() < 5:
                total_working_days += 1

        # Absent = working days - present - half_days - leaves
        effective_present = present_days + (half_days * 0.5)
        absent_days = max(0, total_working_days - effective_present - leave_days)

        return {
            'present_days': present_days,
            'absent_days': int(absent_days),
            'half_days': half_days,
            'leave_days': leave_days,
            'total_worked_hours': round(total_worked_hours, 2),
            'overtime_hours': round(overtime_hours, 2),
            'total_working_days': total_working_days,
        }
