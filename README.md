# EmPay HRMS 🚀

**Human Resource Management System** built with Core PHP, PDO, and Tailwind CSS.

## ⚡ Tech Stack

- **Backend:** Core PHP (no framework)
- **Database:** PDO (MySQL) — structure only, queries as placeholders
- **Frontend:** Tailwind CSS (CDN) + Custom CSS
- **Icons:** Lucide Icons
- **Font:** Inter (Google Fonts)

## 📁 Project Structure

```
/vit_odoo_hrms
├── /config
│   ├── app.php              → App constants, paths, roles
│   └── database.php         → PDO connection factory
│
├── /auth
│   ├── session.php          → Session management helpers
│   ├── login_check.php      → Auth guard (protect pages)
│   └── role_check.php       → Role-based access control
│
├── /backend
│   ├── /auth
│   │   ├── login_handler.php
│   │   ├── register_handler.php
│   │   └── logout.php
│   ├── /users
│   │   ├── create_user.php
│   │   └── update_user.php
│   ├── /attendance
│   │   └── mark_attendance.php
│   ├── /leave
│   │   ├── apply_leave.php
│   │   └── approve_leave.php
│   └── /payroll
│       └── generate_salary.php
│
├── /frontend
│   ├── /auth          → login.php, register.php
│   ├── /dashboard     → index.php
│   ├── /users         → index.php, form.php
│   ├── /attendance    → mark.php, history.php
│   ├── /leave         → apply.php, manage.php
│   └── /payroll       → index.php, payslip.php
│
├── /components
│   ├── header.php     → HTML head, Tailwind, icons
│   ├── sidebar.php    → Fixed sidebar navigation
│   ├── navbar.php     → Top bar with breadcrumbs
│   └── footer.php     → Footer + Lucide init
│
├── /public
│   ├── index.php      → Main entry point
│   ├── router.php     → Simple ?page= routing
│   └── /assets/css/custom.css
│
└── README.md
```

## 🚀 Getting Started

### Prerequisites
- XAMPP / WAMP / PHP 8.0+
- MySQL (optional for demo mode)

### Installation

1. Clone/place the project in your web server root:
   ```
   C:\xampp\htdocs\vit_odoo_hrms
   ```

2. Start Apache from XAMPP Control Panel

3. Open in browser:
   ```
   http://localhost/vit_odoo_hrms/public/
   ```

4. You'll be redirected to the login page.

### Demo Accounts

| Role     | Email              | Password  |
|----------|--------------------|-----------|
| Admin    | admin@empay.com    | admin123  |
| HR       | hr@empay.com       | hr123     |
| Employee | emp@empay.com      | emp123    |
| Payroll  | payroll@empay.com  | pay123    |

## 🔁 Routing

Simple query-parameter routing via `public/index.php`:

```
index.php?page=dashboard        → /frontend/dashboard/index.php
index.php?page=attendance/mark  → /frontend/attendance/mark.php
index.php?page=users/form       → /frontend/users/form.php
```

## 🔐 Role-Based Access

| Feature         | Admin | HR  | Employee | Payroll |
|-----------------|-------|-----|----------|---------|
| Dashboard       | ✅    | ✅  | ✅       | ✅      |
| Manage Users    | ✅    | ✅  | ❌       | ❌      |
| Attendance      | ✅    | ✅  | ✅       | ❌      |
| Leave Apply     | ✅    | ✅  | ✅       | ❌      |
| Leave Approve   | ✅    | ✅  | ❌       | ❌      |
| Payroll         | ✅    | ❌  | ❌       | ✅      |

## 🎨 Design

- **Odoo-Inspired Light Theme** with primary plum purple accents (#714B67)
- **Clinical clarity** with information-dense, data-driven layouts
- **Neutral canvas** using white and near-white surfaces
- **Responsive** sidebar with mobile toggle
- **Micro-interactions** on hover/focus (subtle background changes)

## 📝 Notes

- All database queries are **PDO placeholders** — structure only, no active database required
- Demo mode uses hardcoded user accounts in `login_handler.php`
- Flash messages auto-dismiss after 5 seconds
- Session timeout set to 30 minutes

## 📜 License

MIT License — Built for EmPay HRMS.
