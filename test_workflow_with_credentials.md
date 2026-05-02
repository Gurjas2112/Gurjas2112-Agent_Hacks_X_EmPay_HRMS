# EmPay HRMS – Final System Testing Workflow & Validation

This document outlines the step-by-step testing workflow to verify the **EmPay – Smart HRMS** system against the hackathon problem statement and the provided mockup diagrams.

---

## 👥 Demo Credentials

Use these accounts to test the different permission levels and module access.

| Role | Login | Password |
|------|-------|----------|
| **Admin** | `empay_admin@demo.com` | `admin123` |
| **HR Officer** | `empay_hr@demo.com` | `hr123` |
| **Payroll Officer** | `empay_payroll@demo.com` | `payroll123` |
| **Employee** (Rahul) | `rahul@demo.com` | `emp123` |
| **Employee** (Neha) | `neha@demo.com` | `emp123` |

---

## 🚀 Phase 1: System Initialization & Auth Setup

1.  **Start Environment**: Ensure Docker is running (`docker-compose up -d`).
2.  **Access Portal**: Navigate to `http://localhost:8069`.
3.  **Database Setup**: Create a new database named `empay` and **ensure "Load demonstration data" is checked**.
4.  **Install Module**: Go to the **Apps** menu, search for "EmPay", and click **Activate**.
5.  **Enable Signup**: The module automatically enables **Free Signup (B2C)** on install.
6.  **Verify Branding**: Logout and observe the **Premium EmPay Login Page** (Gradient background, modern cards, and logo).

---

## 🛠️ Phase 2: Role-Based Testing Workflow

### Scenario A: Employee Self-Service (Rahul Kumar)
*Goal: Verify personal dashboard, attendance, and leave requests.*

1.  **Login** as `rahul@demo.com`.
2.  **Dashboard**:
    *   Verify the **Employee Dashboard** view loads (distinct from Admin view).
    *   Check your **EmPay ID** (e.g., EMP-004) and Job Title.
    *   Observe the **Leave Balances** (should show 20 days Annual, 10 days Sick).
3.  **Attendance**:
    *   Click **Clock In** on the dashboard widget.
    *   Verify the status changes and a "Check In" activity appears.
4.  **Time Off**:
    *   Navigate to **Time Off > My Time Off**.
    *   Click **New** and apply for a 2-day "Annual Leave".
5.  **Payslips**:
    *   Navigate to **My Payslips**.
    *   Verify you can view your processed payslips but **cannot** edit them.

### Scenario B: HR Management (Priya Sharma - HR Officer)
*Goal: Manage employee profiles and monitor attendance.*

1.  **Login** as `empay_hr@demo.com`.
2.  **Employees**:
    *   Navigate to **Employees > Employee Profiles**.
    *   Open a profile and update the **Emergency Contact** or **Job Title**.
    *   Verify you can create a **New Employee**.
3.  **Attendance Monitor**:
    *   Navigate to **Attendance > All Attendance**.
    *   Verify you can see the check-in/out logs for all employees (including Rahul's recent clock-in).
4.  **Security Check**:
    *   Attempt to access the **Payroll** menu. It should be **invisible/restricted** for this role.

### Scenario C: Payroll Processing (Vikram Patel - Payroll Officer)
*Goal: Approve leaves and execute a Pay Run.*

1.  **Login** as `empay_payroll@demo.com`.
2.  **Leave Approval**:
    *   Navigate to **Time Off > Leave Requests**.
    *   Locate Rahul's pending request and click **Approve**.
3.  **Execute Pay Run**:
    *   Navigate to **Payroll > Pay Runs**.
    *   Click **New** or use the **Generate Pay Run** wizard.
    *   Select the current month range and click **Generate**.
    *   Open the generated Pay Run and verify the **Net Pay** calculation (Basic + HRA + Transport - PF - Professional Tax).
    *   Click **Confirm** → **Mark as Paid**.
4.  **Reporting**:
    *   Open an individual **Payslip** and click **Print Payslip** to view the PDF breakdown.

### Scenario D: System Administration (Arjun Mehta - Admin)
*Goal: Full oversight and dashboard analytics.*

1.  **Login** as `empay_admin@demo.com`.
2.  **Admin Dashboard**:
    *   Verify **Stat Cards** (Total Employees, On Leave, Late Arrivals).
    *   Check **Monthly Salary Trends** and **Leave Distribution** charts.
    *   View **Recent Activities** to see live check-in events.
3.  **User Management**:
    *   Navigate to **Settings > Users & Companies > Users**.
    *   Verify you can modify roles (Groups) for any user.

### Scenario E: Account Creation & Login Testing
*Goal: Verify the self-registration flow and security redirection.*

1.  **Public Signup**:
    *   Logout of Odoo.
    *   On the login page, click **"Don't have an account?"**.
    *   Fill in: Email (`newuser@example.com`), Name (`New Tester`), and Password.
    *   Click **Sign up**.
2.  **Login Redirect**:
    *   Verify you are automatically logged in and redirected to the **EmPay Dashboard**.
3.  **Permission Check**:
    *   Navigate through the menus.
    *   Verify that as a new registrant, you have **Employee-level access** (cannot see Payroll or Settings).
4.  **Real-Time Validation**:
    *   Login with an incorrect password. Verify the **"Wrong login/password"** error appears with the custom styling.

---

## ✅ Requirements Validation & Verification

| Requirement (PS) | Implementation Status | Mockup Alignment |
|------------------|-----------------------|------------------|
| **1. User & Role Management** | **PASSED**: 4 distinct security groups (Admin, HR, Payroll, Employee) implemented with record rules. | Matches "Roles & Permissions" diagram. |
| **2. Attendance & Leave** | **PASSED**: Real-time check-in/out via OWL widget + multi-state leave workflow. | Matches "Attendance Logs" & "Leave Request" mockups. |
| **3. Payroll Management** | **PASSED**: Independent payroll engine calculating proration, PF (12%), and slab-based Prof. Tax. | Matches "Payrun List" & "Payslip Details" mockups. |
| **4. Dashboard & Analytics** | **PASSED**: Real-time OWL dashboard with SVG charts, stat cards, and bus notifications. | Matches "Main Dashboard" high-fidelity mockup. |
| **5. Employee Self-Service**| **PASSED**: Restricted view showing only own data, salary info, and balances. | Matches "Employee Portal" mockup. |

---

## 🛠️ Verification Checklist (Technical)

*   [x] **Real-Time Bus**: Dashboard updates instantly when an employee clocks in (via Odoo `bus_service`).
*   [x] **Calculation Logic**: PF is exactly 12% of Basic; Professional Tax follows the defined slabs (150/200/300).
*   [x] **UI Aesthetics**: Uses modern SCSS gradients, shadow depths, and Inter typography as per mockup design.
*   [x] **Responsive Grid**: Dashboard charts adapt from 2-column to 1-column on mobile/narrow view.
*   [x] **Error Handling**: Validated that leave allocations cannot be created in a final state directly (fixed via helper methods).

---
*Created for the EmPay HRMS Hackathon Submission.*
