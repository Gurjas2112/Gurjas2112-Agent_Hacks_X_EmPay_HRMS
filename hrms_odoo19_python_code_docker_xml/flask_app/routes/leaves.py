"""Leave routes — List, apply, approve, reject."""
from flask import Blueprint, render_template, request, redirect, url_for, flash, session
from routes.dashboard import login_required, _get_authed_rpc

leaves_bp = Blueprint('leaves', __name__, url_prefix='/leaves')


@leaves_bp.route('/')
@login_required
def index():
    rpc = _get_authed_rpc()
    role = session.get('role', 'employee')

    try:
        if role in ('admin', 'hr_officer', 'payroll_officer'):
            domain = []
        else:
            emp = rpc.search_read('hr.employee', [('user_id', '=', session['uid'])],
                                  ['id'], limit=1)
            emp_id = emp[0]['id'] if emp else 0
            domain = [('employee_id', '=', emp_id)]

        leaves = rpc.search_read('hr.leave', domain=domain, fields=[
            'employee_id', 'holiday_status_id', 'date_from', 'date_to',
            'number_of_days', 'state', 'name',
        ], limit=100, order='date_from desc')

        # Get leave types for the apply form
        leave_types = rpc.search_read('hr.leave.type', [], ['id', 'name'])
    except Exception as e:
        leaves = []
        leave_types = []
        flash('Error loading leaves: %s' % str(e), 'error')

    return render_template('leaves/index.html', leaves=leaves, leave_types=leave_types)


@leaves_bp.route('/apply', methods=['POST'])
@login_required
def apply_leave():
    rpc = _get_authed_rpc()
    try:
        emp = rpc.search_read('hr.employee', [('user_id', '=', session['uid'])],
                              ['id'], limit=1)
        if not emp:
            flash('No employee record found.', 'error')
            return redirect(url_for('leaves.index'))

        leave_type_id = int(request.form.get('leave_type_id', 0))
        date_from = request.form.get('date_from', '')
        date_to = request.form.get('date_to', '')
        reason = request.form.get('reason', '')

        if not all([leave_type_id, date_from, date_to]):
            flash('All fields are required.', 'error')
            return redirect(url_for('leaves.index'))

        rpc.create('hr.leave', {
            'employee_id': emp[0]['id'],
            'holiday_status_id': leave_type_id,
            'date_from': date_from + ' 00:00:00',
            'date_to': date_to + ' 23:59:59',
            'name': reason or 'Leave Request',
        })
        flash('Leave request submitted!', 'success')
    except Exception as e:
        flash('Failed to apply leave: %s' % str(e), 'error')

    return redirect(url_for('leaves.index'))


@leaves_bp.route('/<int:leave_id>/approve', methods=['POST'])
@login_required
def approve(leave_id):
    role = session.get('role', 'employee')
    if role not in ('admin', 'payroll_officer'):
        flash('Permission denied.', 'error')
        return redirect(url_for('leaves.index'))

    rpc = _get_authed_rpc()
    try:
        rpc.call_kw('hr.leave', 'action_approve', [[leave_id]])
        flash('Leave approved.', 'success')
    except Exception as e:
        flash('Approval failed: %s' % str(e), 'error')

    return redirect(url_for('leaves.index'))


@leaves_bp.route('/<int:leave_id>/reject', methods=['POST'])
@login_required
def reject(leave_id):
    role = session.get('role', 'employee')
    if role not in ('admin', 'payroll_officer'):
        flash('Permission denied.', 'error')
        return redirect(url_for('leaves.index'))

    reason = request.form.get('reason', '')
    rpc = _get_authed_rpc()
    try:
        if reason:
            rpc.write('hr.leave', [leave_id], {'rejection_reason': reason})
        rpc.call_kw('hr.leave', 'action_refuse', [[leave_id]])
        flash('Leave rejected.', 'success')
    except Exception as e:
        flash('Rejection failed: %s' % str(e), 'error')

    return redirect(url_for('leaves.index'))
