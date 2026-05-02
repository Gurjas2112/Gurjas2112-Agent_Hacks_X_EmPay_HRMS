"""EmPay HRMS — Flask Frontend Application."""
from flask import Flask, redirect, url_for
from config import Config


def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)
    app.secret_key = Config.SECRET_KEY

    # Register blueprints
    from routes.auth import auth_bp
    from routes.dashboard import dashboard_bp
    from routes.employees import employees_bp
    from routes.attendance import attendance_bp
    from routes.leaves import leaves_bp
    from routes.payroll import payroll_bp

    app.register_blueprint(auth_bp)
    app.register_blueprint(dashboard_bp)
    app.register_blueprint(employees_bp)
    app.register_blueprint(attendance_bp)
    app.register_blueprint(leaves_bp)
    app.register_blueprint(payroll_bp)

    # Redirect root to dashboard (handled by dashboard_bp)
    # Dashboard already handles '/'

    return app


if __name__ == '__main__':
    app = create_app()
    app.run(
        host='0.0.0.0',
        port=Config.FLASK_PORT,
        debug=True,
    )
