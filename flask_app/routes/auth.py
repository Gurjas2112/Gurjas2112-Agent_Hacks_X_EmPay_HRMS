"""Auth routes — Login, Signup, Logout."""
from flask import Blueprint, render_template, request, redirect, url_for, session, flash
from odoo_rpc import OdooRPC
from config import Config

auth_bp = Blueprint('auth', __name__)


def _get_rpc():
    """Create a fresh RPC client."""
    return OdooRPC(Config.ODOO_URL, Config.ODOO_DB)


@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    if session.get('uid'):
        return redirect(url_for('dashboard.index'))

    if request.method == 'POST':
        login_val = request.form.get('login', '').strip()
        password = request.form.get('password', '').strip()

        if not login_val or not password:
            flash('Please enter both email and password.', 'error')
            return render_template('auth/login.html')

        rpc = _get_rpc()
        try:
            uid = rpc.authenticate(login_val, password)
            # Get session info
            info = rpc.get_session_info()
            user_name = info.get('name', login_val) if info else login_val

            # Get user groups
            groups = rpc.get_user_groups(uid)

            # Determine role label
            if groups.get('is_admin'):
                role = 'admin'
                role_label = 'Admin'
            elif groups.get('is_payroll_officer'):
                role = 'payroll_officer'
                role_label = 'Payroll Officer'
            elif groups.get('is_hr_officer'):
                role = 'hr_officer'
                role_label = 'HR Officer'
            else:
                role = 'employee'
                role_label = 'Employee'

            # Store in session
            session['uid'] = uid
            session['login'] = login_val
            session['password'] = password
            session['user_name'] = user_name
            session['role'] = role
            session['role_label'] = role_label
            session['groups'] = groups

            # Store session cookies from the RPC session
            session['odoo_cookies'] = dict(rpc.session.cookies)

            return redirect(url_for('dashboard.index'))
        except ValueError:
            flash('Invalid email or password.', 'error')
        except ConnectionError:
            flash('Cannot connect to the server. Please try again later.', 'error')
        except Exception as e:
            flash(str(e), 'error')

    return render_template('auth/login.html')


@auth_bp.route('/signup', methods=['GET', 'POST'])
def signup():
    if session.get('uid'):
        return redirect(url_for('dashboard.index'))

    if request.method == 'POST':
        name = request.form.get('name', '').strip()
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '').strip()
        confirm = request.form.get('confirm_password', '').strip()

        if not all([name, email, password, confirm]):
            flash('All fields are required.', 'error')
            return render_template('auth/signup.html')

        if password != confirm:
            flash('Passwords do not match.', 'error')
            return render_template('auth/signup.html')

        if len(password) < 6:
            flash('Password must be at least 6 characters.', 'error')
            return render_template('auth/signup.html')

        # Authenticate as admin to create the user
        rpc = _get_rpc()
        try:
            # Using the credentials you provided for the technical user
            rpc.authenticate('admin@empay.com', 'admin123') 
            # Create user
            user_id = rpc.create('res.users', {
                'name': name,
                'login': email,
                'password': password,
                'groups_id': [(4, rpc.call_kw('ir.model.data', '_xmlid_to_res_id',
                                              ['empay_hrms.group_employee']))],
            })
            flash('Account created! Please log in.', 'success')
            return redirect(url_for('auth.login'))
        except Exception as e:
            flash('Signup failed: %s' % str(e), 'error')

    return render_template('auth/signup.html')


@auth_bp.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('auth.login'))
