# 🚀 EmPay HRMS - Comprehensive Workflow & Technical Documentation

This document provides a deep dive into the architecture, module workflows, and resource stack of the **EmPay HRMS** platform.

---

## 🛠 1. Core Technical Stack

| Resource | Purpose | Implementation |
| :--- | :--- | :--- |
| **PHP 8.2+ (Core)** | Server-side logic | Modular, functional approach without heavy frameworks. |
| **MySQL (PDO)** | Data Persistence | Prepared statements for security (SQL injection prevention). |
| **Tailwind CSS (v3.3)** | Styling Engine | Used via CDN with a custom design system for Odoo-inspired aesthetics. |
| **Lucide Icons** | Visual Indicators | Lightweight, vector-based icons for all UI elements. |
| **PHPMailer** | Email Dispatch | Configured with SMTP for secure, reliable employee notifications. |
| **Vanilla JS** | Client Interaction | Used for dynamic UI updates, modals, and CSV upload feedback. |

---

## 📁 2. Detailed File Architecture

### 🛡️ Configuration & Auth (`/config`, `/auth`)
- **`config/app.php`**: The "Heart" of the system. Defines global constants like `BASE_URL`, `APP_NAME`, and Role constants (`ROLE_ADMIN`, `ROLE_HR`).
- **`config/database.php`**: Establishes a singleton PDO connection. Uses environment-aware settings for seamless local/server transitions.
- **`auth/login_check.php`**: A security guard. Included at the top of every protected page to verify if a user is authenticated.
- **`auth/role_check.php`**: Implements RBAC (Role-Based Access Control). Functions like `requireRole()` ensure only authorized users access specific modules.

### 🍱 Components (`/components`)
- **`header.php`**: Contains the `<head>` section, importing Google Fonts (Inter), Tailwind CDN, and global CSS tokens.
- **`navbar.php`**: Dynamic breadcrumbs and user profile dropdown.
- **`sidebar.php`**: The primary navigation engine. Links are dynamically highlighted based on the current `$_GET['page']`.
- **`footer.php`**: Initializes **Lucide Icons** and handles the auto-dismissal logic for flash notifications.

### 🏗️ Backend Logic (`/backend`)
- **`backend/users/import_csv.php`**: 
  - *Process:* Reads CSV rows → Validates data → Maps Department Names to IDs → Skips Duplicates → Hashes Passwords → Returns new User IDs.
- **`backend/users/send_welcome_emails.php`**:
  - *Process:* Fetches new user details → Constructs a branded HTML template → Uses `sendEmPayEmail()` to dispatch credentials.
- **`backend/schedule/assign.php`**: Handles shift management. Now triggers an automated email notification to the employee upon shift creation.

### 🎨 Frontend Pages (`/frontend`)
- **`frontend/users/index.php`**: High-density Kanban/Grid view of employees with real-time search and status filtering.
- **`frontend/users/form.php`**: Unified Profile & Edit interface. Uses conditional rendering to switch between "Create" and "Edit" modes.
- **`frontend/users/import.php`**: Features a drag-and-drop zone and a post-import selection table for batch emailing.

---

## 🔄 3. Key Workflows

### 📥 A. Employee Bulk Onboarding (CSV Workflow)
1. **Trigger**: HR clicks "Bulk Import" on the user form.
2. **Action**: HR uploads `employees2.csv`.
3. **Logic**: `backend/import_csv.php` parses the file. It performs a **Collision Check** (Email/Username) and sends back a list of new IDs.
4. **Verification**: The frontend displays a summary of imported staff.
5. **Finalization**: HR selects staff members and clicks "Send Account Emails".

### 📧 B. Automated Notifications
- **Trigger**: New User Creation or Schedule Assignment.
- **Utility**: `utils/mailer.php` wraps PHPMailer.
- **Output**: Branded HTML emails with Call-to-Action (CTA) buttons, ensuring employees are instantly informed of system changes.

### 📅 C. Schedule Management
1. **Admin/HR** selects an employee from a designation-aware dropdown.
2. **Schedule** is saved to the `schedules` table.
3. **Instant Sync**: A `PDO::lastInsertId()` check triggers the notification engine to alert the employee.

---

## 🔧 4. Tools & External Resources

1. **Tailwind Design Tokens**: Custom extensions in `header.php` define the `brand` (#714B67), `surface`, and `success` colors.
2. **Lucide-Icons Library**: Used for all UI iconography (e.g., `user`, `upload`, `briefcase`).
3. **Google Fonts (Inter)**: Used for high readability in data-intensive tables.
4. **Demo Provisioner**: `utils/demo_provisioner.php` ensures the environment is always populated with "Agent Hacks" demo data (like Joy Kapoor) for testing.

---

## 📝 5. Maintenance Notes
- **Database Schema**: The system relies on `config/database.sql` for the primary structure.
- **Logging**: All outbound emails are logged in the `email_logs` table for audit purposes.
- **Security**: All passwords are encrypted using `PASSWORD_DEFAULT` (Bcrypt) and all inputs are sanitized via PDO prepared statements.

---
*Built with ❤️ by Antigravity for the EmPay HRMS Project.*
