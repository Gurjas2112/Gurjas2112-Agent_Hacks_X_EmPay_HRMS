"""Payroll routes — Pay Runs, Payslips."""
from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from routes.dashboard import login_required, _get_authed_rpc

payroll_bp = Blueprint('payroll', __name__, url_prefix='/payroll')


@payroll_bp.route('/')
@login_required
def runs():
    role = session.get('role', 'employee')
    if role not in ('admin', 'payroll_officer'):
        flash('Permission denied.', 'error')
        return redirect(url_for('dashboard.index'))

    rpc = _get_authed_rpc()
    try:
        payruns = rpc.search_read('empay.payrun', [], fields=[
            'id', 'name', 'date_from', 'date_to', 'state',
            'total_employees', 'total_gross', 'total_net', 'total_pf',
        ], order='date_from desc')
    except Exception as e:
        payruns = []
        flash('Error loading pay runs: %s' % str(e), 'error')

    return render_template('payroll/runs.html', payruns=payruns)


@payroll_bp.route('/<int:run_id>')
@login_required
def detail(run_id):
    role = session.get('role', 'employee')
    if role not in ('admin', 'payroll_officer'):
        flash('Permission denied.', 'error')
        return redirect(url_for('dashboard.index'))

    rpc = _get_authed_rpc()
    try:
        data = rpc.read('empay.payrun', [run_id], [
            'id', 'name', 'date_from', 'date_to', 'state',
            'total_employees', 'total_gross', 'total_net', 'total_pf',
        ])
        payrun = data[0] if data else None

        payslips = rpc.search_read('empay.payslip', [('payrun_id', '=', run_id)], fields=[
            'id', 'name', 'employee_id', 'date_from', 'date_to',
            'present_days', 'total_working_days', 'absent_days',
            'basic_wage', 'prorated_basic', 'hra', 'transport_allowance',
            'pf_employee', 'professional_tax', 'gross_salary', 'net_salary', 'state',
        ], order='employee_id asc')
    except Exception as e:
        payrun = None
        payslips = []
        flash('Error loading pay run: %s' % str(e), 'error')

    if not payrun:
        flash('Pay run not found.', 'error')
        return redirect(url_for('payroll.runs'))

    return render_template('payroll/detail.html', payrun=payrun, payslips=payslips)


@payroll_bp.route('/generate', methods=['POST'])
@login_required
def generate():
    role = session.get('role', 'employee')
    if role not in ('admin', 'payroll_officer'):
        flash('Permission denied.', 'error')
        return redirect(url_for('payroll.runs'))

    rpc = _get_authed_rpc()
    name = request.form.get('name', '')
    date_from = request.form.get('date_from', '')
    date_to = request.form.get('date_to', '')

    if not all([name, date_from, date_to]):
        flash('All fields are required.', 'error')
        return redirect(url_for('payroll.runs'))

    try:
        run_id = rpc.create('empay.payrun', {
            'name': name,
            'date_from': date_from,
            'date_to': date_to,
        })
        # Generate payslips
        rpc.call_kw('empay.payrun', 'action_generate_payslips', [[run_id]])
        flash('Pay run generated with payslips!', 'success')
        return redirect(url_for('payroll.detail', run_id=run_id))
    except Exception as e:
        flash('Failed to generate pay run: %s' % str(e), 'error')

    return redirect(url_for('payroll.runs'))


@payroll_bp.route('/<int:run_id>/confirm', methods=['POST'])
@login_required
def confirm(run_id):
    rpc = _get_authed_rpc()
    try:
        rpc.call_kw('empay.payrun', 'action_generate_payslips', [[run_id]])
        flash('Pay run confirmed.', 'success')
    except Exception as e:
        flash('Failed: %s' % str(e), 'error')
    return redirect(url_for('payroll.detail', run_id=run_id))


@payroll_bp.route('/<int:run_id>/pay', methods=['POST'])
@login_required
def mark_paid(run_id):
    rpc = _get_authed_rpc()
    try:
        rpc.call_kw('empay.payrun', 'action_mark_paid', [[run_id]])
        flash('Pay run marked as paid.', 'success')
    except Exception as e:
        flash('Failed: %s' % str(e), 'error')
    return redirect(url_for('payroll.detail', run_id=run_id))


# ------------------------------------------------------------------
# Employee Payslips (My Payslips)
# ------------------------------------------------------------------

@payroll_bp.route('/my-payslips')
@login_required
def my_payslips():
    rpc = _get_authed_rpc()
    try:
        emp = rpc.search_read('hr.employee', [('user_id', '=', session['uid'])],
                              ['id'], limit=1)
        emp_id = emp[0]['id'] if emp else 0
        payslips = rpc.search_read('empay.payslip', [('employee_id', '=', emp_id)], fields=[
            'id', 'name', 'date_from', 'date_to',
            'present_days', 'total_working_days',
            'basic_wage', 'prorated_basic', 'hra', 'transport_allowance',
            'pf_employee', 'professional_tax', 'gross_salary', 'net_salary', 'state',
        ], order='date_from desc')
    except Exception as e:
        payslips = []
        flash('Error loading payslips: %s' % str(e), 'error')

    return render_template('payroll/payslips.html', payslips=payslips)
