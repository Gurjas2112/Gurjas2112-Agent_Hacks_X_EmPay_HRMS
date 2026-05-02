import logging

from odoo import api, fields, models, _
from odoo.exceptions import UserError, AccessError

_logger = logging.getLogger(__name__)


class HrLeave(models.Model):
    """Extend hr.leave with EmPay approval workflow enhancements."""

    _inherit = 'hr.leave'

    # ------------------------------------------------------------------
    # Fields
    # ------------------------------------------------------------------
    approved_by = fields.Many2one(
        comodel_name='res.users',
        string='Approved By',
        readonly=True,
        tracking=True,
        help='The user who approved this leave request.',
    )
    rejection_reason = fields.Text(
        string='Rejection Reason',
        tracking=True,
        help='Reason provided when the leave request was rejected.',
    )

    # ------------------------------------------------------------------
    # CRUD Overrides — Real-Time Notifications
    # ------------------------------------------------------------------
    @api.model_create_multi
    def create(self, vals_list):
        """Override create to send bus notification for new leave requests."""
        records = super().create(vals_list)
        for record in records:
            emp_name = record.employee_id.name or 'Employee'
            record._notify_dashboard(
                'leave_requested',
                'New leave request from %s.' % emp_name,
            )
        return records

    # ------------------------------------------------------------------
    # Action Overrides
    # ------------------------------------------------------------------
    def action_approve(self):
        """Override leave approval to enforce role checks and track approver.

        Only Payroll Officers and Admins can approve leave requests.
        Sends a notification to the employee upon approval.
        """
        current_user = self.env.user
        is_payroll = current_user.has_group('empay_hrms.group_payroll_officer')
        is_admin = current_user.has_group('empay_hrms.group_admin')

        if not (is_payroll or is_admin):
            raise AccessError(_(
                'Only Payroll Officers or Admins can approve leave requests. '
                'Your current role does not permit this action.'
            ))

        for leave in self:
            leave.approved_by = current_user.id

        result = super().action_approve()

        # Send notification to employee
        for leave in self:
            if leave.employee_id and leave.employee_id.user_id:
                leave._send_leave_notification('approved')

        # Notify dashboard in real-time
        for leave in self:
            self._notify_dashboard(
                'leave_approved',
                'Leave approved for %s.' % (leave.employee_id.name or 'Employee'),
            )

        return result

    def action_refuse(self):
        """Override leave refusal to require a rejection reason.

        Ensures the rejection_reason field is filled before refusing.
        Sends a notification to the employee upon rejection.
        """
        for leave in self:
            if not leave.rejection_reason:
                raise UserError(_(
                    'Please provide a rejection reason before refusing the '
                    'leave request for %s.'
                ) % leave.employee_id.name)

        current_user = self.env.user
        is_payroll = current_user.has_group('empay_hrms.group_payroll_officer')
        is_admin = current_user.has_group('empay_hrms.group_admin')

        if not (is_payroll or is_admin):
            raise AccessError(_(
                'Only Payroll Officers or Admins can reject leave requests.'
            ))

        result = super().action_refuse()

        # Send notification to employee
        for leave in self:
            if leave.employee_id and leave.employee_id.user_id:
                leave._send_leave_notification('rejected')

        # Notify dashboard in real-time
        for leave in self:
            self._notify_dashboard(
                'leave_rejected',
                'Leave rejected for %s.' % (leave.employee_id.name or 'Employee'),
            )

        return result

    # ------------------------------------------------------------------
    # Real-Time Bus Notification
    # ------------------------------------------------------------------
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
    # Helper Methods
    # ------------------------------------------------------------------
    def _send_leave_notification(self, status):
        """Send an internal notification about leave status change.

        Args:
            status (str): 'approved' or 'rejected'
        """
        self.ensure_one()
        employee = self.employee_id
        if not employee or not employee.user_id:
            return

        if status == 'approved':
            subject = _('Leave Request Approved')
            body = _(
                '<p>Dear <strong>%s</strong>,</p>'
                '<p>Your leave request from <strong>%s</strong> to '
                '<strong>%s</strong> has been <span style="color: green;">'
                '<strong>approved</strong></span> by <strong>%s</strong>.</p>'
                '<p>Leave Type: %s</p>'
                '<p>Best regards,<br/>EmPay HRMS</p>'
            ) % (
                employee.name,
                self.date_from.strftime('%d %b %Y') if self.date_from else '',
                self.date_to.strftime('%d %b %Y') if self.date_to else '',
                self.approved_by.name if self.approved_by else 'System',
                self.holiday_status_id.name if self.holiday_status_id else '',
            )
        else:
            subject = _('Leave Request Rejected')
            body = _(
                '<p>Dear <strong>%s</strong>,</p>'
                '<p>Your leave request from <strong>%s</strong> to '
                '<strong>%s</strong> has been <span style="color: red;">'
                '<strong>rejected</strong></span>.</p>'
                '<p>Leave Type: %s</p>'
                '<p><strong>Reason:</strong> %s</p>'
                '<p>Best regards,<br/>EmPay HRMS</p>'
            ) % (
                employee.name,
                self.date_from.strftime('%d %b %Y') if self.date_from else '',
                self.date_to.strftime('%d %b %Y') if self.date_to else '',
                self.holiday_status_id.name if self.holiday_status_id else '',
                self.rejection_reason or _('No reason provided'),
            )

        # Post to the leave chatter and notify employee
        self.message_post(
            body=body,
            subject=subject,
            partner_ids=[employee.user_id.partner_id.id],
            message_type='notification',
            subtype_xmlid='mail.mt_note',
        )

        # Also try sending email if mail template exists
        try:
            mail_values = {
                'subject': subject,
                'body_html': body,
                'email_to': employee.work_email or employee.user_id.email,
                'email_from': self.env.company.email or 'noreply@empay.com',
            }
            mail = self.env['mail.mail'].sudo().create(mail_values)
            mail.send()
        except Exception as e:
            _logger.warning('Failed to send leave notification email: %s', e)
