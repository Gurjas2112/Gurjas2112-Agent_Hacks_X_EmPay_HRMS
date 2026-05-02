# EmPay – Smart Human Resource Management System

> **Simplifying HR & Payroll Operations for Smarter Workplaces**

---

## 👥 Team

**Team: Agent Hacks** — [Odoo × VIT Pune Hackathon 2026](https://hackathon.odoo.com/event/odoo-x-vit-pune-hackathon-26-18/register)

| Photo | Name | Role | GitHub |
| :---: | --- | --- | --- |
| <img src="team_member_details/team_member_1.jpg" width="100" style="border-radius: 50%;"> | **Gurjas Singh Gandhi** | Team Leader | [Gurjas2112](https://github.com/Gurjas2112) |
| <img src="team_member_details/team_member_2.jpeg" width="100" style="border-radius: 50%;"> | **Joy Kujur** | Developer | [joyboy-pega](https://github.com/joyboy-pega) |
| <img src="team_member_details/team_member_4.jpeg" width="100" style="border-radius: 50%;"> | **Sarvesh Varode** | Developer | [sarveshvarode092704](https://github.com/sarveshvarode092704) |
| <img src="team_member_details/team_member_3.jpeg" width="100" style="border-radius: 50%;"> | **Prathamesh Nibandhe** | Developer | [prathamesh-coding](https://github.com/prathamesh-coding) |

---

## 🌟 Vision and Mission

**EmPay** aims to modernize and simplify how organizations manage people, processes, and payroll through a comprehensive, all-in-one Human Resource Management System (HRMS). 

---

## 🎯 Problem Statement

The challenge was to develop a working HRMS system focusing on the following core modules and flows:
1.  **User & Role Management**: Multi-tier RBAC (Admin, HR, Payroll, Employee).
2.  **Attendance & Leave**: Real-time tracking and approval workflows.
3.  **Payroll Management**: Attendance-linked salary calculation with PF and Tax deductions.
4.  **Dashboard & Analytics**: High-fidelity overview of HR metrics and company health.

---

## 🚀 Quick Start (Docker)

```bash
# Clone the repository
git clone https://github.com/Gurjas2112/Gurjas2112-Agent_Hacks_X_EmPay_HRMS.git
cd Gurjas2112-Agent_Hacks_X_EmPay_HRMS

# Start Odoo + PostgreSQL
docker-compose up -d
```

---

## 🏗️ Detailed Codebase Breakdown

### 📂 Root Directory
- `__init__.py`: Python package initializer.
- `__manifest__.py`: Module metadata (dependencies, views, security, demo data).

### 📂 models/ (Business Logic)
| File | Description | Use Case |
|------|-------------|----------|
| `hr_employee.py` | Core employee extension adding EmPay IDs, job details, and contract wages. | Profile management. |
| `hr_attendance.py` | Attendance logic (check-in/out), late/OT calculation, and real-time Bus notifications. | Daily tracking. |
| `hr_leave.py` | Time-off workflow management and dashboard reactivity. | Leave management. |
| `hr_payslip.py` | Standalone payroll calculation engine for salary, PF (12%), and slab-based taxes. | Wage computation. |
| `empay_payrun.py` | Batch payroll processing and dashboard statistics aggregation. | Monthly pay cycles. |

### 📂 views/ (User Interface)
| File | Description |
|------|-------------|
| `auth_templates.xml` | Custom premium branding for Odoo login and signup pages. |
| `dashboard_views.xml` | Action and client-action definition for the OWL Dashboard. |
| `hr_employee_views.xml` | Profile views (Kanban/Form/List) with custom EmPay fields. |
| `hr_attendance_views.xml` | Real-time attendance logs and analytics views. |
| `hr_leave_views.xml` | Leave request forms and approval workflows. |
| `hr_payslip_views.xml` | Individual payslip management and PDF generation actions. |
| `hr_payrun_views.xml` | Batch payrun management interface. |
| `menu_views.xml` | The primary navigation structure (Root menu and submenus). |

### 📂 static/ (Frontend Assets)
- **src/dashboard/empay_dashboard.js**: JavaScript logic for the OWL component (Bus listeners, state management).
- **src/dashboard/empay_dashboard.xml**: QWeb templates for dashboard widgets, charts, and action cards.
- **src/dashboard/empay_dashboard.scss**: SCSS for premium visual aesthetics (gradients, shadows, responsive grid).
- **src/img/logo.jpeg**: Official EmPay branding asset.

### 📂 data/ (Configuration & Initial State)
| File | Description |
|------|-------------|
| `salary_rules.xml` | Definitions for Basic, HRA, Transport, PF, and Tax components. |
| `leave_types.xml` | System-defined leave categories (Annual, Sick, Casual, Unpaid). |
| `demo_data.xml` | Mock users, employees, 29+ payslips, and attendance history for testing. |

### 📂 security/ (Access Control)
| File | Description |
|------|-------------|
| `empay_security.xml` | Defines the 4 security groups (Admin, HR, Payroll, Employee) and Record Rules. |
| `ir.model.access.csv` | Model-level CRUD permissions for each role. |

### 📂 wizard/ (Interactive Workflows)
| File | Description |
|------|-------------|
| `generate_payrun_wizard.py` | Logic to automate payslip generation for selected date ranges. |
| `generate_payrun_wizard.xml` | UI for the payroll generation step-by-step process. |

### 📂 report/ (Documentation)
| File | Description |
|------|-------------|
| `payslip_report.xml` | Report action definition for PDF output. |
| `payslip_report_template.xml`| Custom QWeb template for the professional salary breakdown document. |

---

## 👥 User Roles & Responsibilities

| Role | Responsibilities | Access Level |
|------|------------------|--------------|
| **Admin** | Register on portal, manage users, CRUD all data, manage roles. | **Full Access** |
| **Employee** | Apply for time off, view personal attendance/records, access directory (Read-only). | **Restricted**: No payroll, settings, or reports. |
| **HR Officer** | Manage employee profiles, monitor company-wide attendance, allocate leaves. | **HR Only**: No payroll data or system settings. |
| **Payroll Officer**| Approve/Reject time-off, generate payslips/reports, manage payroll & attendance. | **Payroll/Time-Off**: No profile modification or settings. |

---

## 💻 Code-Wise Workflow & Technical Pipelines

The system is built on a reactive architecture where frontend components and backend models communicate via a unified data pipeline.

### 1. Authentication & User Provisioning
- **Entry**: `/web/signup` (B2C Free Signup enabled in `auth_templates.xml`).
- **Logic**: Odoo's `auth_signup` creates a `res.users` record.
- **Auto-provisioning**: A triggered action (or default group in manifest) assigns the **EmPay: Employee** group.
- **UI**: Custom QWeb templates in `views/auth_templates.xml` inject premium CSS for the login/signup screens.

### 2. Real-Time Attendance Pipeline
- **Frontend**: The `AttendanceWidget` in `empay_dashboard.xml` triggers a JS call.
- **Backend**: `hr.attendance` model processes the `check_in`/`check_out`.
- **Reactivity**: The `_notify_dashboard` method in `hr_attendance.py` sends a message via `bus.bus`.
- **Dashboard Refresh**: `empay_dashboard.js` listens to the channel and silently updates the `state.stats` without a full page reload.

### 3. Leave Management Workflow
- **Submission**: Employee submits `hr.leave` via the dashboard or form view.
- **Validation**: `hr_leave.py` checks against `hr.leave.allocation` quotas.
- **Approval**: HR/Payroll Officer approves. This triggers a `bus.bus` notification to update the "On Leave" counter on the Admin Dashboard.
- **Payroll Integration**: Approved leaves are automatically fetched by the Payroll engine during the next Payrun.

### 4. Standalone Payroll Engine Pipeline
- **Initiation**: `generate_payrun_wizard.py` is called with a date range.
- **Batching**: `empay.payrun` creates individual `empay.payslip` records for all active employees.
- **Computation**:
    1.  Fetch `hr.attendance` for the period.
    2.  Calculate **Present Days** (Check-in count) vs **Working Days**.
    3.  Compute **Prorated Basic** = `(Wage / Working Days) * Present Days`.
    4.  Apply `salary_rules.xml` (HRA, Transport, PF).
    5.  Execute **Slab-based Tax Logic** in `hr_payslip.py`.
- **Finalization**: `report/payslip_report_template.xml` renders the data into a professional PDF.

---

## 💰 Statutory Calculations

### Payroll Formula
```
Basic Salary = (Contract Wage / Days in Month) * Present Days
Deductions:
- PF (Employee): 12% of Basic
- Professional Tax: Slab-based (₹0 to ₹300)
Net Pay = (Basic + HRA + Transport) - PF - Prof. Tax
```

---

## 👥 Demo Credentials

| Role | Login | Password |
|------|-------|----------|
| **Admin** | `empay_admin@demo.com` | `admin123` |
| **HR Officer** | `empay_hr@demo.com` | `hr123` |
| **Payroll Officer** | `empay_payroll@demo.com` | `payroll123` |
| **Employee** | `rahul@demo.com` | `emp123` |

---

## 📄 License

MIT License