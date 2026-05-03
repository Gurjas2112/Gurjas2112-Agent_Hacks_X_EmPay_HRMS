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
- **`/auth`**: 
  - `login_handler.php`: Verifies credentials and manages session initialization.
  - `register_handler.php`: Handles new user signups with secure hashing.
- **`/users`**:
  - `create_user.php`: Manual employee registration logic.
  - `update_user.php`: Profile modification and status management.
  - `import_csv.php`: **(New)** Bulk employee onboarding engine with duplicate detection.
  - `send_welcome_emails.php`: **(New)** Triggers batch credential emails for new hires.
- **`/schedule`**:
  - `assign.php`: Logic for assigning shifts with instant email triggers to employees.
- **`/attendance`**:
  - `mark_attendance.php`: Handles real-time check-in/check-out with status validation.
- **`/leave`**:
  - `apply_leave.php`: Logic for employee leave requests.
  - `approve_leave.php`: HR decision engine for leave management.
- **`/payroll`**:
  - `generate_salary.php`: Automated payroll calculation and payslip generation.

### 🖼️ 4. Frontend & User Interface (`/frontend`)
- **`/dashboard`**: `index.php` - Role-specific metrics and real-time status updates.
- **`/users`**:
  - `index.php`: Searchable Kanban directory of all staff.
  - `form.php`: Detailed Profile & Edit interface with icon-labeled sections.
  - `import.php`: **(New)** Drag-and-drop CSV upload and selection UI.
- **`/attendance`**:
  - `mark.php`: Employee attendance terminal.
  - `log.php`: Comprehensive attendance history for HR audits.
- **`/leave`**:
  - `apply.php`: Leave application form for employees.
  - `manage.php`: Review board for Admin/HR to approve/reject leaves.
- **`/payroll`**:
  - `index.php`: Payroll summary dashboard for officers.
  - `payslip.php`: High-fidelity, printable HTML payslip generator.

### 🧱 5. Shared Components & Entry (`/components`, `/public`)
- **`components/header.php`**: Global meta-tags, Tailwind configurations, and design tokens.
- **`components/sidebar.php`**: Context-aware navigation sidebar.
- **`components/navbar.php`**: Breadcrumbs and user action center.
- **`components/footer.php`**: Script initializers for Lucide and animations.
- **`public/index.php`**: The main router that resolves all application pages.

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
*Built for the Odoo × VIT Pune Hackathon 2026.*
