{
    'name': 'EmPay – Smart HRMS',
    'version': '19.0.1.0.0',
    'summary': 'All-in-one HR, Attendance, Leave & Payroll Management',
    'description': """
EmPay HRMS - Smart Human Resource Management System

A comprehensive, all-in-one Human Resource Management System (HRMS)
designed to modernize and simplify how organizations manage people,
processes, and payroll.

Key Features:
* User and Role Management (Employee, HR Officer, Payroll Officer, Admin)
* Attendance and Leave Management with approval workflows
* Payroll Management with attendance-linked wage proration
* Dashboard and Analytics with live HR statistics
* QWeb PDF Payslip Reports
* Provident Fund and Professional Tax calculations
    """,
    'author': 'EmPay Team',
    'website': 'https://empay.example.com',
    'category': 'Human Resources',
    'depends': [
        'hr',
        'hr_attendance',
        'hr_holidays',
        'mail',
        'bus',
        'base_setup',
        'auth_signup',
    ],
    'data': [
        'security/empay_security.xml',
        'security/ir.model.access.csv',
        'data/leave_types.xml',
        'report/payslip_report.xml',
        'report/payslip_report_template.xml',
        'wizard/generate_payrun_wizard.xml',
        'views/hr_employee_views.xml',
        'views/hr_attendance_views.xml',
        'views/hr_leave_views.xml',
        'views/hr_payrun_views.xml',
        'views/hr_payslip_views.xml',
        'views/dashboard_views.xml',
        'views/auth_templates.xml',
        'views/menu_views.xml',
    ],
    'demo': [
        'data/demo_data.xml',
    ],
    'assets': {
        'web.assets_backend': [
            'empay_hrms/static/src/dashboard/empay_dashboard.js',
            'empay_hrms/static/src/dashboard/empay_dashboard.xml',
            'empay_hrms/static/src/dashboard/empay_dashboard.scss',
        ],
    },
    'installable': True,
    'application': True,
    'auto_install': False,
    'license': 'LGPL-3',
}
