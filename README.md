# EmPay – Smart Human Resource Management System 🚀

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

## ⚡ Tech Stack

The application has been modernized from its original framework to a fast, lightweight PHP architecture:
- **Backend:** Core PHP (no framework)
- **Database:** PDO (MySQL) for secure database interactions
- **Frontend:** Tailwind CSS (CDN) + Custom CSS + Vanilla JS
- **Icons:** Lucide Icons
- **Font:** Inter (Google Fonts)

---

## 📁 Project Structure

```text
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
├── /backend                 → API endpoints and action handlers
│   ├── /auth
│   ├── /users
│   ├── /attendance
│   ├── /leave
│   ├── /payroll
│   └── /schedule
│
├── /frontend                → View templates (HTML/PHP mix)
│   ├── /auth          
│   ├── /dashboard     
│   ├── /users         
│   ├── /attendance    
│   ├── /leave         
│   ├── /payroll       
│   ├── /reports
│   └── /schedule
│
├── /components              → Reusable UI components
│   ├── header.php     
│   ├── sidebar.php    
│   ├── navbar.php     
│   └── footer.php     
│
├── /public                  → Web root
│   ├── index.php      
│   ├── router.php     
│   └── /assets
│
└── seed.sql                 → Database schema and initial data
```

---

## 🚀 Getting Started

### Prerequisites
- XAMPP / WAMP / PHP 8.0+
- MySQL Server

### Installation

1. Clone/place the project in your web server root:
   ```bash
   C:\xampp\htdocs\Agent_Hacks_X_EmPay_HRMS
   ```
   *(Ensure the working directory points to `vit_odoo_hrms` inside if you are running it locally)*

2. Setup the Database:
   - Create a new MySQL database named `empay_hrms`
   - Import the `seed.sql` file (located in `/vit_odoo_hrms/seed.sql`) to set up the tables and demo data.

3. Start Apache & MySQL from XAMPP Control Panel.

4. Open in browser:
   ```text
   http://localhost/Agent_Hacks_X_EmPay_HRMS/vit_odoo_hrms/public/
   ```

5. You'll be redirected to the login page.

---

## 👥 Demo Accounts

| Role     | Email              | Password  |
|----------|--------------------|-----------|
| Admin    | admin@empay.com    | admin123  |
| HR       | hr@empay.com       | hr123     |
| Employee | emp@empay.com      | emp123    |
| Payroll  | payroll@empay.com  | pay123    |

---

## 🔐 Role-Based Access

| Feature         | Admin | HR  | Employee | Payroll |
|-----------------|-------|-----|----------|---------|
| Dashboard       | ✅    | ✅  | ✅       | ✅      |
| Manage Users    | ✅    | ✅  | ❌       | ❌      |
| Attendance      | ✅    | ✅  | ✅       | ❌      |
| Leave Apply     | ✅    | ✅  | ✅       | ❌      |
| Leave Approve   | ✅    | ✅  | ❌       | ❌      |
| Payroll         | ✅    | ❌  | ❌       | ✅      |

---

## 🎨 Design System

- **Modern Aesthetic** with primary plum purple accents (#714B67)
- **Glassmorphism & Gradients** implemented through Tailwind for a premium feel
- **Responsive Navigation** with a collapsible sidebar and mobile toggle
- **Micro-interactions** on hover/focus states to make the UI feel alive

---

## 📝 Notes

- Flash messages automatically dismiss after a few seconds.
- Session timeout is strictly enforced for security.
- Comprehensive backend validation is present on all form submissions.
- Passwords are conventionally hashed using `password_hash()` in the database.

---

## 📜 License

MIT License — Built for EmPay HRMS.