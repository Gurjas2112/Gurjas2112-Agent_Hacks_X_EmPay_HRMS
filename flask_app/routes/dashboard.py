"""Dashboard routes — Main landing page with stats and charts."""
from flask import Blueprint, render_template, jsonify, session
from functools import wraps
from odoo_rpc import OdooRPC
from config import Config

dashboard_bp = Blueprint('dashboard', __name__)


def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if not session.get('uid'):
            from flask import redirect, url_for
            return redirect(url_for('auth.login'))
        return f(*args, **kwargs)
    return decorated


def _get_authed_rpc():
    """Create an RPC client pre-authenticated with session creds."""
    rpc = OdooRPC(Config.ODOO_URL, Config.ODOO_DB)
    cookies = session.get('odoo_cookies', {})
    for k, v in cookies.items():
        rpc.session.cookies.set(k, v)
    rpc.uid = session.get('uid')
    rpc.login = session.get('login')
    rpc.password = session.get('password')
    return rpc


@dashboard_bp.route('/')
@login_required
def index():
    rpc = _get_authed_rpc()
    try:
        stats = rpc.get_dashboard_stats()
    except Exception as e:
        stats = {
            'is_admin': False,
            'employee_data': {},
            'total_employees': 0,
            'present_today': 0,
            'pending_leaves': 0,
            'month_net_payroll': 0,
            'on_leave_today': 0,
            'late_arrivals': 0,
            'early_departures': 0,
            'last_payrun': {'name': 'N/A', 'state': 'N/A', 'total_net': 0},
            'monthly_attendance': [],
            'leave_distribution': [],
            'payroll_trend': [],
            'recent_activities': [],
        }
    return render_template('dashboard/index.html', stats=stats)


@dashboard_bp.route('/api/dashboard-stats')
@login_required
def api_stats():
    rpc = _get_authed_rpc()
    try:
        stats = rpc.get_dashboard_stats()
        return jsonify(stats)
    except Exception as e:
        return jsonify({'error': str(e)}), 500
