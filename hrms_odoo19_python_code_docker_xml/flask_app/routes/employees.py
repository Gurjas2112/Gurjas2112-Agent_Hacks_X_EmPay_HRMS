"""Employee routes — List, detail, create, update."""
from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from routes.dashboard import login_required, _get_authed_rpc

employees_bp = Blueprint('employees', __name__, url_prefix='/employees')


@employees_bp.route('/')
@login_required
def list_employees():
    rpc = _get_authed_rpc()
    try:
        employees = rpc.search_read('hr.employee', domain=[('active', '=', True)], fields=[
            'id', 'name', 'empay_employee_id', 'job_title', 'department_id',
            'employment_type', 'work_email', 'work_phone',
            'date_of_joining', 'empay_group',
        ], order='name asc')
    except Exception as e:
        employees = []
        flash('Error loading employees: %s' % str(e), 'error')
    return render_template('employees/list.html', employees=employees)


@employees_bp.route('/<int:emp_id>')
@login_required
def detail(emp_id):
    rpc = _get_authed_rpc()
    try:
        data = rpc.read('hr.employee', [emp_id], [
            'id', 'name', 'empay_employee_id', 'job_title', 'department_id',
            'employment_type', 'work_email', 'work_phone',
            'date_of_joining', 'bank_account_number',
            'emergency_contact_name', 'emergency_contact_phone',
            'empay_group',
        ])
        employee = data[0] if data else None
    except Exception as e:
        employee = None
        flash('Error loading employee: %s' % str(e), 'error')

    if not employee:
        flash('Employee not found.', 'error')
        return redirect(url_for('employees.list_employees'))

    # Get attendance records for this employee (last 30)
    try:
        attendance = rpc.search_read('hr.attendance', domain=[
            ('employee_id', '=', emp_id),
        ], fields=[
            'check_in', 'check_out', 'worked_hours', 'status',
        ], limit=30, order='check_in desc')
    except Exception:
        attendance = []

    # Get leave records
    try:
        leaves = rpc.search_read('hr.leave', domain=[
            ('employee_id', '=', emp_id),
        ], fields=[
            'holiday_status_id', 'date_from', 'date_to', 'number_of_days', 'state',
        ], limit=20, order='date_from desc')
    except Exception:
        leaves = []

    # Get payslips
    try:
        payslips = rpc.search_read('empay.payslip', domain=[
            ('employee_id', '=', emp_id),
        ], fields=[
            'name', 'date_from', 'date_to', 'gross_salary', 'net_salary', 'state',
        ], limit=12, order='date_from desc')
    except Exception:
        payslips = []

    return render_template('employees/detail.html',
                           employee=employee, attendance=attendance,
                           leaves=leaves, payslips=payslips)


@employees_bp.route('/<int:emp_id>/update', methods=['POST'])
@login_required
def update(emp_id):
    role = session.get('role', 'employee')
    if role not in ('admin', 'hr_officer'):
        flash('Permission denied.', 'error')
        return redirect(url_for('employees.detail', emp_id=emp_id))

    rpc = _get_authed_rpc()
    vals = {}
    for field in ['job_title', 'work_email', 'work_phone', 'bank_account_number',
                   'emergency_contact_name', 'emergency_contact_phone']:
        val = request.form.get(field)
        if val is not None:
            vals[field] = val

    emp_type = request.form.get('employment_type')
    if emp_type:
        vals['employment_type'] = emp_type

    try:
        rpc.write('hr.employee', [emp_id], vals)
        flash('Employee updated successfully.', 'success')
    except Exception as e:
        flash('Update failed: %s' % str(e), 'error')

    return redirect(url_for('employees.detail', emp_id=emp_id))
