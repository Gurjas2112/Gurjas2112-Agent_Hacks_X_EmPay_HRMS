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

## ✨ Core Modules & Features

EmPay is a high-fidelity HRMS solution designed for the Odoo Hackathon, featuring a modern, responsive UI and robust backend logic.

### 📊 1. Real-Time Analytics Dashboard
- **Stat Cards**: Instant visibility into Total Employees, Present Today, and Pending Leaves.
- **Dynamic Charts**: Interactive SVG charts (via OWL) for Monthly Salary Trends and Leave Distribution.
- **Activity Feed**: Real-time notifications for employee check-ins and system events.

### 🕒 2. Intelligent Attendance & Scheduling
- **NFC-Ready Terminal**: Support for NFC-based attendance (integrated via `nfc_attendance.php`).
- **One-Click Check-In**: Simplified dashboard widget for employees to clock in/out instantly.
- **Automated Scheduling**: Shift assignment engine with automatic email notifications.

### 📝 3. Seamless Leave Management
- **Self-Service Portal**: Employees can apply for Annual, Sick, or Casual leave and track balances.
- **Approval Workflow**: Multi-tier HR decision engine for approving/rejecting requests.
- **Priority Sorting**: Smart sorting that prioritizes pending and urgent requests for HR officers.

### 💰 4. Production-Ready Payroll
- **Statutory Compliance**: Automated calculations for PF (12%), Professional Tax, and Income Tax.
- **Proration Engine**: Smart salary calculation based on attendance and unpaid leaves.
- **High-Fidelity Payslips**: Professional, printable HTML/PDF payslips with detailed breakdowns.

### 📥 5. Bulk Onboarding & Auth
- **CSV Import Engine**: Batch create employee profiles with duplicate detection and automated field mapping.
- **Welcome Automations**: Triggers professional welcome emails with login credentials upon account creation.
- **Premium Auth**: Modern, branded login/signup experience with Role-Based Access Control (RBAC).

---

## 🏗️ Detailed Project Architecture

EmPay HRMS is architected for speed, modularity, and scalability. Below is a detailed map of all core directories and their respective files.

### 📁 1. Configuration & Core Utilities (`/config`, `/utils`)
- **`config/app.php`**: Global system constants, role definitions, and path configurations.
- **`config/database.php`**: Secure PDO connection wrapper with singleton pattern.
- **`config/mail.php`**: SMTP server credentials and sender configurations for PHPMailer.
- **`config/database.sql`**: The fundamental schema for all tables (Users, Attendance, Leaves, Payroll, Schedules).
- **`utils/mailer.php`**: The unified email engine. Handles HTML templating and delivery logging.
- **`utils/demo_provisioner.php`**: Automatically ensures the environment has demo data for "Agent Hacks" presentations.

### 🔒 2. Authentication & Security (`/auth`)
- **`auth/session.php`**: Manages PHP sessions and cross-page state persistence.
- **`auth/login_check.php`**: Middleware to protect routes from unauthenticated access.
- **`auth/role_check.php`**: Advanced RBAC (Role-Based Access Control) utility for granular feature access.

### ⚙️ 3. Backend Logic (`/backend`)
- **`/auth`**: Login/Signup handlers with secure hashing.
- **`/users`**: Employee CRUD, **Bulk CSV Import**, and automated welcome emails.
- **`/schedule`**: Shift assignment logic with email triggers.
- **`/attendance`**: Real-time validation and NFC-integration handlers.
- **`/leave`**: Leave request application and multi-state approval engine.
- **`/payroll`**: Salary calculation engine with statutory compliance.

### 🖼️ 4. Frontend & User Interface (`/frontend`)
- **`/dashboard`**: Role-specific metrics, SVG charts, and real-time status updates.
- **`/users`**: Searchable Kanban directory and high-fidelity profile forms.
- **`/attendance`**: Employee terminal and HR audit logs.
- **`/leave`**: Leave application and management interfaces.
- **`/payroll`**: Payroll dashboard and high-fidelity payslip generator.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- XAMPP or equivalent web server environment

### Setup
1. Clone the repository to your `htdocs` folder.
2. Import `config/database.sql` into your MySQL server.
3. Configure your SMTP settings in `config/mail.php` if you wish to test email notifications.
4. Access the app at `http://localhost/Agent_Hacks_X_EmPay_HRMS/php_mysql_html_css_js_web_interface/public/`.

---
*Built for the Odoo × VIT Pune Hackathon 2026 by Team Agent Hacks.*
