import logging

from odoo import api, fields, models, _
from odoo.exceptions import ValidationError

_logger = logging.getLogger(__name__)


class HrEmployee(models.Model):
    """Extend hr.employee with EmPay-specific fields."""

    _inherit = 'hr.employee'

    # ------------------------------------------------------------------
    # EmPay Custom Fields
    # ------------------------------------------------------------------
    empay_employee_id = fields.Char(
        string='EmPay ID',
        readonly=True,
        copy=False,
        index=True,
        help='Auto-generated unique employee identifier (e.g., EMP-001)',
    )
    date_of_joining = fields.Date(
        string='Date of Joining',
        tracking=True,
        help='The date when the employee officially joined the organization.',
    )
    employment_type = fields.Selection(
        selection=[
            ('full_time', 'Full-Time'),
            ('part_time', 'Part-Time'),
            ('contract', 'Contract'),
            ('intern', 'Intern'),
        ],
        string='Employment Type',
        default='full_time',
        tracking=True,
    )
    bank_account_number = fields.Char(
        string='Bank Account Number',
        help='Employee bank account number for salary disbursement.',
    )
    emergency_contact_name = fields.Char(
        string='Emergency Contact Name',
    )
    emergency_contact_phone = fields.Char(
        string='Emergency Contact Phone',
    )
    empay_group = fields.Selection(
        selection=[
            ('employee', 'Employee'),
            ('hr_officer', 'HR Officer'),
            ('payroll_officer', 'Payroll Officer'),
            ('admin', 'Admin'),
        ],
        string='EmPay Role',
        compute='_compute_empay_group',
        store=True,
        help='Computed from the user\'s security groups.',
    )

    # ------------------------------------------------------------------
    # SQL Constraints
    # ------------------------------------------------------------------
    _sql_constraints = [
        (
            'empay_employee_id_unique',
            'UNIQUE(empay_employee_id)',
            'EmPay Employee ID must be unique!',
        ),
    ]

    # ------------------------------------------------------------------
    # Computed Fields
    # ------------------------------------------------------------------
    @api.depends('user_id')
    def _compute_empay_group(self):
        """Determine the highest EmPay role assigned to the employee's user."""
        for employee in self:
            user = employee.user_id
            if not user:
                employee.empay_group = 'employee'
                continue

            if user.has_group('empay_hrms.group_admin'):
                employee.empay_group = 'admin'
            elif user.has_group('empay_hrms.group_payroll_officer'):
                employee.empay_group = 'payroll_officer'
            elif user.has_group('empay_hrms.group_hr_officer'):
                employee.empay_group = 'hr_officer'
            else:
                employee.empay_group = 'employee'

    # ------------------------------------------------------------------
    # CRUD Overrides
    # ------------------------------------------------------------------
    @api.model_create_multi
    def create(self, vals_list):
        """Auto-generate empay_employee_id on creation using ir.sequence."""
        for vals in vals_list:
            if not vals.get('empay_employee_id'):
                vals['empay_employee_id'] = self._generate_empay_id()
        return super().create(vals_list)

    @api.model
    def _generate_empay_id(self):
        """Generate the next EmPay employee ID.

        Uses ir.sequence if available; otherwise falls back to counting
        existing records.
        """
        sequence = self.env['ir.sequence'].sudo().next_by_code('empay.employee.id')
        if sequence:
            return sequence

        # Fallback: compute from current count
        last_employee = self.sudo().search(
            [('empay_employee_id', '!=', False)],
            order='empay_employee_id desc',
            limit=1,
        )
        if last_employee and last_employee.empay_employee_id:
            try:
                last_num = int(last_employee.empay_employee_id.split('-')[-1])
            except (ValueError, IndexError):
                last_num = 0
        else:
            last_num = 0
        return f'EMP-{last_num + 1:03d}'

    @api.model
    def _set_demo_wages(self, employee_xmlid, wage):
        """Set wage on the auto-created hr.version for a demo employee.

        Called from demo_data.xml via <function> after employees are created.
        Odoo 19 auto-creates hr.version records, so we update them in-place.
        """
        try:
            employee = self.env.ref(employee_xmlid, raise_if_not_found=False)
            if employee:
                versions = self.env['hr.version'].search([
                    ('employee_id', '=', employee.id),
                ], order='id desc', limit=1)
                if versions:
                    versions.write({'wage': wage})
                    _logger.info('Set wage %s for %s', wage, employee.name)
        except Exception as e:
            _logger.warning('Could not set demo wage for %s: %s', employee_xmlid, e)

    @api.model
    def _validate_demo_allocation(self, allocation_xmlid):
        """Validate a demo leave allocation through the proper workflow.

        Odoo 19 requires allocations to go through draft → confirm → validate.
        Called from demo_data.xml via <function> after allocations are created.
        """
        try:
            alloc = self.env.ref(allocation_xmlid, raise_if_not_found=False)
            if alloc:
                alloc = alloc.sudo()
                if alloc.state == 'draft':
                    alloc.action_confirm()
                if alloc.state == 'confirm':
                    alloc.action_validate()
                _logger.info('Validated allocation %s', allocation_xmlid)
        except Exception as e:
            _logger.warning('Could not validate demo allocation %s: %s', allocation_xmlid, e)
