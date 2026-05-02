"""EmPay Flask App — Configuration."""
import os


class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY', 'empay-secret-key-2026')
    ODOO_URL = os.environ.get('ODOO_URL', 'http://localhost:8069')
    ODOO_DB = os.environ.get('ODOO_DB', 'empay')
    FLASK_PORT = int(os.environ.get('FLASK_PORT', 5000))
    SESSION_TYPE = 'filesystem'
