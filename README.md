# EmPay – Smart Human Resource Management System

> All-in-one HR, Attendance, Leave & Payroll Management built on Odoo 19.0 Community Edition

---

## 🚀 Quick Start (Docker)

```bash
# Clone the repository
git clone https://github.com/Gurjas2112/Gurjas2112-Agent_Hacks_X_EmPay_HRMS.git
cd Gurjas2112-Agent_Hacks_X_EmPay_HRMS

# Start Odoo + PostgreSQL
docker-compose up -d

# Wait for services to start (first run takes ~60s)
docker-compose logs -f odoo
```

Then open **http://localhost:8069** in your browser.

### First-Time Database Setup
1. Go to `http://localhost:8069/web/database/manager`
2. Click **Create Database**
3. Master Password: `empay_admin_2026`
4. Database Name: `empay`
5. Email: `admin@empay.com`
6. Check **Load demonstration data** ✅
7. Click **Create Database**
8. Install the **EmPay – Smart HRMS** module from Apps

---

## 📋 Features

| Module | Description |
|--------|-------------|
| **User & Role Management** | 4 roles (Employee, HR Officer, Payroll Officer, Admin) with record-level security |
| **Attendance** | Check-in/out, status tracking (present/half-day/absent), late minutes, overtime |
| **Time Off** | Leave application, approval workflow, rejection reasons, email notifications |
| **Payroll** | Attendance-linked wage proration, PF, Professional Tax, salary breakdown |
| **Pay Runs** | Batch payslip generation, state workflow (Draft → Confirmed → Paid) |
| **Dashboard** | Real-time OWL dashboard with live stats, charts, and bus notifications |
| **Reports** | Professional PDF payslip via QWeb report engine |

---

## 🔄 Real-Time Dashboard

The dashboard auto-updates via two mechanisms:
- **Auto-polling**: Refreshes every 30 seconds (toggleable pause/resume)
- **Bus notifications**: Instant push updates when attendance, leave, or payroll data changes

Live events that trigger instant refresh:
- Employee check-in / check-out
- New leave request submitted
- Leave approved or rejected
- Payrun confirmed, paid, or reset

---

## 👥 Demo Credentials

| Role | Login | Password |
|------|-------|----------|
| Admin | `empay_admin@demo.com` | `admin123` |
| HR Officer | `empay_hr@demo.com` | `hr123` |
| Payroll Officer | `empay_payroll@demo.com` | `payroll123` |
| Employee | `rahul@demo.com` | `emp123` |
| Employee | `neha@demo.com` | `emp123` |
| Employee | `amit@demo.com` | `emp123` |
| Employee | `sneha@demo.com` | `emp123` |
| Employee | `ravi@demo.com` | `emp123` |

---

## 🏗️ Tech Stack

- **Framework**: [Odoo 19.0 Community Edition](https://www.odoo.com/)
- **Frontend**: [OWL (Odoo Web Library)](https://github.com/odoo/owl) - Component-based framework for the dashboard.
- **Styling**: SCSS / Vanilla CSS with a focus on premium aesthetics.
- **Communication**: Odoo `bus_service` (WebSocket/Longpolling) for real-time push updates.
- **Backend**: Python 3.12+ (Models, Business Logic, Wizards).
- **Database**: PostgreSQL 16.
- **Infrastructure**: Docker & Docker Compose (Containerized deployment).
- **Reporting**: QWeb PDF Engine for professional payslip generation.

---

## 🧩 Architecture & Design Logic

### 1. Standalone Payroll Engine (Community Compatibility)
To ensure 100% compatibility with Odoo Community Edition, we developed a standalone `empay.payslip` model. 
- **Decoupling**: Unlike Enterprise Odoo which relies on the `hr_payroll` module, EmPay implements its own calculation logic.
- **Algorithm**: The payroll engine performs real-time wage proration based on monthly attendance records.
  
### 2. Payroll Proration Algorithm
The system uses a precise proration algorithm to calculate "Prorated Basic" salary:
- **Daily Wage**: `Contract Wage / Days in Month`
- **Proration**: `Daily Wage * Attendance Count`
This ensures employees are paid exactly for the days they worked, excluding unapproved absences.

### 3. Real-Time Communication Layer
EmPay leverages the Odoo **Bus Service** to push instant updates from the server to the dashboard:
- **Observers**: Models like `hr.attendance`, `hr.leave`, and `empay.payrun` act as observers.
- **Triggers**: On every state change (e.g., check-in, leave approval), a notification is sent to the `empay_dashboard` channel.
- **Reactive UI**: The OWL Dashboard component listens to this channel and performs a "silent refresh" of its state without reloading the page.

### 4. Security & Role-Based Access Control (RBAC)
The system implements a strict 4-tier security model:
1. **Admin**: Full system access, configuration, and user management.
2. **HR Officer**: Manage employees, attendance, and leave approvals.
3. **Payroll Officer**: Generate payruns, validate payslips, and manage contract wages.
4. **Employee**: Personal dashboard, clock in/out, and leave applications.


---

## 📁 Module Structure

```
empay_hrms/
├── __init__.py
├── __manifest__.py
├── models/
│   ├── hr_employee.py       # EmPay ID, employment type, roles
│   ├── hr_attendance.py     # Status, late/overtime, bus notifications
│   ├── hr_leave.py          # Approval workflow, bus notifications
│   ├── hr_payslip.py        # Attendance-linked salary computation
│   └── empay_payrun.py      # Batch payslip gen, dashboard stats, bus
├── views/                   # Form, list, search, kanban, pivot, graph
├── security/                # 4 groups + record rules + access CSV
├── data/                    # Leave types, salary rules, demo data
├── report/                  # QWeb payslip PDF template
├── static/src/dashboard/    # OWL component (JS + XML + SCSS)
└── wizard/                  # Generate payrun wizard
```

---

## 🐳 Docker Commands

```bash
# Start services (Full Rebuild)
docker-compose up -d --build

# View real-time logs
docker-compose logs -f odoo

# Update Custom Module
docker-compose exec odoo odoo -d empay -u empay_hrms --stop-after-init

# Cleanup Volumes
docker-compose down -v
```

---

## 💰 Payroll Calculation

```
Contract Wage → Proration by Attendance → Prorated Basic
  ├── HRA: 40% of Basic
  ├── Transport: ₹1,600 (prorated)
  ├── Gross = Basic + HRA + Transport
  ├── PF Employee: 12% of Basic (deduction)
  ├── Professional Tax (slab-based)
  └── Net = Gross - PF - Tax
```

**Professional Tax Slabs:**
| Gross | Tax |
|-------|-----|
| ≤ ₹10,000 | ₹0 |
| ₹10,001 – ₹15,000 | ₹150 |
| ₹15,001 – ₹25,000 | ₹200 |
| ₹25,001+ | ₹300 |

---

## 📄 License

LGPL-3.0