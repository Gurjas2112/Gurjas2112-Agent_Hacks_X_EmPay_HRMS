import logging

from odoo import api, fields, models, _
from odoo.exceptions import UserError

_logger = logging.getLogger(__name__)


class GeneratePayrunWizard(models.TransientModel):
    """Wizard to generate a new pay run with optional employee selection."""

    _name = 'empay.generate.payrun.wizard'
    _description = 'Generate Pay Run Wizard'

    name = fields.Char(
        string='Pay Run Name',
        required=True,
        help='Descriptive name for this pay run (e.g., "May 2026 Payrun").',
    )
    date_from = fields.Date(
        string='Period Start',
        required=True,
        default=lambda self: fields.Date.today().replace(day=1),
        help='Start date of the pay period.',
    )
    date_to = fields.Date(
        string='Period End',
        required=True,
        help='End date of the pay period.',
    )
    employee_ids = fields.Many2many(
        comodel_name='hr.employee',
        string='Employees',
        help='Leave empty to include all active employees with open contracts.',
    )

    @api.constrains('date_from', 'date_to')
    def _check_dates(self):
        for wizard in self:
            if wizard.date_from and wizard.date_to:
                if wizard.date_from > wizard.date_to:
                    raise UserError(_(
                        'Period Start date must be before or equal to Period End date.'
                    ))

    def action_generate(self):
        """Create a payrun record and generate payslips for selected employees."""
        self.ensure_one()

        if not self.date_from or not self.date_to:
            raise UserError(_('Please specify both start and end dates for the pay period.'))

        if self.date_from > self.date_to:
            raise UserError(_('Period Start date must be before Period End date.'))

        # Check for overlapping payruns
        existing = self.env['empay.payrun'].search([
            ('date_from', '<=', self.date_to),
            ('date_to', '>=', self.date_from),
            ('state', '!=', 'draft'),
        ])
        if existing:
            raise UserError(_(
                'An active payrun already exists for this period: %s. '
                'Please choose a different date range or reset the existing payrun.'
            ) % existing[0].name)

        # Create the payrun
        payrun = self.env['empay.payrun'].create({
            'name': self.name,
            'date_from': self.date_from,
            'date_to': self.date_to,
        })

        # If specific employees were selected, generate only for them
        if self.employee_ids:
            employees = self.employee_ids
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
                    'payrun_id': payrun.id,
                })
            if payslip_vals:
                payslips = self.env['empay.payslip'].create(payslip_vals)
                for slip in payslips:
                    try:
                        slip.compute_sheet()
                    except Exception as e:
                        _logger.warning('Error computing payslip for %s: %s', slip.employee_id.name, e)
                payrun.write({'state': 'confirmed'})
            else:
                raise UserError(_('No employees with open contracts found in the selection.'))
        else:
            # Generate for all employees
            payrun.action_generate_payslips()

        # Return action to view the created payrun
        return {
            'type': 'ir.actions.act_window',
            'name': _('Pay Run'),
            'res_model': 'empay.payrun',
            'res_id': payrun.id,
            'view_mode': 'form',
            'target': 'current',
        }
