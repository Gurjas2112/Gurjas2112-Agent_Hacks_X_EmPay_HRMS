"""Attendance routes — List, clock-in, clock-out."""
from flask import Blueprint, render_template, request, redirect, url_for, flash, session, jsonify
from routes.dashboard import login_required, _get_authed_rpc

attendance_bp = Blueprint('attendance', __name__, url_prefix='/attendance')


@attendance_bp.route('/')
@login_required
def index():
    rpc = _get_authed_rpc()
    role = session.get('role', 'employee')

    try:
        if role in ('admin', 'hr_officer', 'payroll_officer'):
            domain = []
        else:
            # Employee can see only own
            emp = rpc.search_read('hr.employee', [('user_id', '=', session['uid'])],
                                  ['id'], limit=1)
            emp_id = emp[0]['id'] if emp else 0
            domain = [('employee_id', '=', emp_id)]

        records = rpc.search_read('hr.attendance', domain=domain, fields=[
            'employee_id', 'check_in', 'check_out', 'worked_hours',
            'status', 'late_minutes', 'overtime_hours',
        ], limit=100, order='check_in desc')
    except Exception as e:
        records = []
        flash('Error loading attendance: %s' % str(e), 'error')

    return render_template('attendance/index.html', records=records)


@attendance_bp.route('/clock-in', methods=['POST'])
@login_required
def clock_in():
    rpc = _get_authed_rpc()
    try:
        emp = rpc.search_read('hr.employee', [('user_id', '=', session['uid'])],
                              ['id'], limit=1)
        if emp:
            rpc.call_kw('hr.employee', 'attendance_manual',
                        [[emp[0]['id']], 'hr_attendance.hr_attendance_action_my_attendances'])
            flash('Clocked in successfully!', 'success')
        else:
            flash('No employee record found for your user.', 'error')
    except Exception as e:
        flash('Clock-in failed: %s' % str(e), 'error')
    return redirect(request.referrer or url_for('attendance.index'))


@attendance_bp.route('/clock-out', methods=['POST'])
@login_required
def clock_out():
    rpc = _get_authed_rpc()
    try:
        emp = rpc.search_read('hr.employee', [('user_id', '=', session['uid'])],
                              ['id'], limit=1)
        if emp:
            rpc.call_kw('hr.employee', 'attendance_manual',
                        [[emp[0]['id']], 'hr_attendance.hr_attendance_action_my_attendances'])
            flash('Clocked out successfully!', 'success')
        else:
            flash('No employee record found for your user.', 'error')
    except Exception as e:
        flash('Clock-out failed: %s' % str(e), 'error')
    return redirect(request.referrer or url_for('attendance.index'))
