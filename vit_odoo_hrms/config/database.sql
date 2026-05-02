-- ============================================
-- EmPay HRMS - Database Schema
-- MySQL 8.0+ / MariaDB 10.5+
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS `empay_hrms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `empay_hrms`;

-- ============================================
-- 1. DEPARTMENTS
-- ============================================
CREATE TABLE `departments` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 2. USERS (Employees / Staff)
-- ============================================
CREATE TABLE `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name`     VARCHAR(150) NOT NULL,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `password`      VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed via password_hash()',
    `phone`         VARCHAR(20)  NULL,
    `role`          ENUM('admin','hr','employee','payroll') NOT NULL DEFAULT 'employee',
    `department_id` INT UNSIGNED NULL,
    `designation`   VARCHAR(100) NULL,
    `date_of_birth` DATE NULL,
    `date_of_join`  DATE NULL,
    `gender`        ENUM('male','female','other') NULL,
    `address`       TEXT NULL,
    `avatar`        VARCHAR(255) NULL,
    `basic_salary`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `last_login`    TIMESTAMP NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_users_department`
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_department` (`department_id`),
    INDEX `idx_users_active` (`is_active`)
) ENGINE=InnoDB;

-- ============================================
-- 3. ATTENDANCE
-- ============================================
CREATE TABLE `attendance` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `date`        DATE NOT NULL,
    `check_in`    TIME NULL,
    `check_out`   TIME NULL,
    `work_hours`  DECIMAL(5,2) GENERATED ALWAYS AS (
                      CASE 
                          WHEN `check_in` IS NOT NULL AND `check_out` IS NOT NULL 
                          THEN TIMESTAMPDIFF(MINUTE, `check_in`, `check_out`) / 60.0
                          ELSE NULL 
                      END
                  ) STORED,
    `status`      ENUM('present','absent','late','half_day','on_leave') NOT NULL DEFAULT 'present',
    `notes`       VARCHAR(255) NULL,
    `ip_address`  VARCHAR(45) NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_attendance_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `uq_attendance_user_date` (`user_id`, `date`),
    INDEX `idx_attendance_date` (`date`),
    INDEX `idx_attendance_status` (`status`)
) ENGINE=InnoDB;

-- ============================================
-- 4. LEAVE TYPES
-- ============================================
CREATE TABLE `leave_types` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`            VARCHAR(50) NOT NULL UNIQUE,
    `description`     VARCHAR(255) NULL,
    `max_days`        INT UNSIGNED NOT NULL DEFAULT 12,
    `is_paid`         TINYINT(1) NOT NULL DEFAULT 1,
    `carry_forward`   TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 5. LEAVE REQUESTS
-- ============================================
CREATE TABLE `leaves` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,
    `from_date`     DATE NOT NULL,
    `to_date`       DATE NOT NULL,
    `days`          DECIMAL(4,1) NOT NULL COMMENT 'Supports half-day leaves',
    `reason`        TEXT NOT NULL,
    `status`        ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `approved_by`   INT UNSIGNED NULL,
    `admin_remarks` TEXT NULL,
    `approved_at`   TIMESTAMP NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_leaves_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_leaves_type`
        FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_leaves_approver`
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_leaves_status` (`status`),
    INDEX `idx_leaves_dates` (`from_date`, `to_date`),
    INDEX `idx_leaves_user` (`user_id`)
) ENGINE=InnoDB;

-- ============================================
-- 6. LEAVE BALANCES (Per user, per year)
-- ============================================
CREATE TABLE `leave_balances` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT UNSIGNED NOT NULL,
    `leave_type_id`  INT UNSIGNED NOT NULL,
    `year`           YEAR NOT NULL,
    `total_days`     DECIMAL(4,1) NOT NULL DEFAULT 0,
    `used_days`      DECIMAL(4,1) NOT NULL DEFAULT 0,
    `remaining_days` DECIMAL(4,1) GENERATED ALWAYS AS (`total_days` - `used_days`) STORED,

    CONSTRAINT `fk_lb_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_lb_type`
        FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `uq_lb_user_type_year` (`user_id`, `leave_type_id`, `year`)
) ENGINE=InnoDB;

-- ============================================
-- 7. PAYROLL
-- ============================================
CREATE TABLE `payroll` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT UNSIGNED NOT NULL,
    `month`           VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format',
    `working_days`    INT UNSIGNED NOT NULL DEFAULT 0,
    `present_days`    INT UNSIGNED NOT NULL DEFAULT 0,
    `basic_salary`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `hra`             DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'House Rent Allowance',
    `transport`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `special`         DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Special Allowance',
    `gross_salary`    DECIMAL(12,2) GENERATED ALWAYS AS (
                          `basic_salary` + `hra` + `transport` + `special`
                      ) STORED,
    `pf`              DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Provident Fund',
    `professional_tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tds`             DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Tax Deducted at Source',
    `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_deductions` DECIMAL(12,2) GENERATED ALWAYS AS (
                          `pf` + `professional_tax` + `tds` + `other_deductions`
                      ) STORED,
    `net_salary`      DECIMAL(12,2) GENERATED ALWAYS AS (
                          (`basic_salary` + `hra` + `transport` + `special`) - 
                          (`pf` + `professional_tax` + `tds` + `other_deductions`)
                      ) STORED,
    `status`          ENUM('draft','generated','paid') NOT NULL DEFAULT 'draft',
    `paid_on`         DATE NULL,
    `generated_by`    INT UNSIGNED NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_payroll_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payroll_generator`
        FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    UNIQUE KEY `uq_payroll_user_month` (`user_id`, `month`),
    INDEX `idx_payroll_month` (`month`),
    INDEX `idx_payroll_status` (`status`)
) ENGINE=InnoDB;

-- ============================================
-- 8. SCHEDULES
-- ============================================
CREATE TABLE `schedules` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `shift_date`  DATE NOT NULL,
    `start_time`  TIME NOT NULL,
    `end_time`    TIME NOT NULL,
    `notes`       VARCHAR(255) NULL,
    `created_by`  INT UNSIGNED NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_schedules_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_schedules_creator`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_schedules_date` (`shift_date`),
    INDEX `idx_schedules_user` (`user_id`)
) ENGINE=InnoDB;

-- ============================================
-- 9. ACTIVITY LOG (Audit Trail)
-- ============================================
CREATE TABLE `activity_log` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NULL,
    `action`      VARCHAR(50) NOT NULL COMMENT 'login, logout, create, update, delete, approve, reject',
    `module`      VARCHAR(50) NOT NULL COMMENT 'auth, users, attendance, leave, payroll',
    `description` TEXT NULL,
    `ip_address`  VARCHAR(45) NULL,
    `user_agent`  VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_log_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `idx_log_user` (`user_id`),
    INDEX `idx_log_action` (`action`),
    INDEX `idx_log_module` (`module`),
    INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB;


-- ============================================
-- SEED DATA
-- ============================================

-- Departments
INSERT INTO `departments` (`name`, `description`) VALUES
('Engineering',      'Software Development & IT'),
('Human Resources',  'People Operations & Talent'),
('Marketing',        'Branding, Growth & Content'),
('Design',           'UI/UX & Product Design'),
('Finance',          'Accounts, Payroll & Compliance'),
('Operations',       'Admin & Office Management');

-- Leave Types
INSERT INTO `leave_types` (`name`, `description`, `max_days`, `is_paid`, `carry_forward`) VALUES
('Casual Leave',    'For personal matters',              12, 1, 0),
('Sick Leave',      'For medical / health reasons',       7, 1, 0),
('Annual Leave',    'Earned / privilege leave',          15, 1, 1),
('Unpaid Leave',    'Leave without pay',                 30, 0, 0),
('Maternity Leave', 'For maternity / paternity',         90, 1, 0);

-- Demo Users (passwords are bcrypt hashed)
-- admin123  → $2y$10$...
-- hr123     → $2y$10$...
-- emp123    → $2y$10$...
-- pay123    → $2y$10$...
INSERT INTO `users` (`full_name`, `username`, `email`, `password`, `role`, `department_id`, `designation`, `basic_salary`, `date_of_join`) VALUES
('Admin User',    'admin',   'admin@empay.com',   '$2y$10$YxKm3XcGq7rS.V6Wd9Jyqu2E8VlGfNfkM4LKx0Y8bZpIe6aFWm8J6',   'admin',    1, 'System Administrator', 80000.00, '2023-01-01'),
('Priya Sharma',  'hruser',  'hr@empay.com',      '$2y$10$9Kq1LZ3GjFL.ONhA5n3Kf.1r5LbN8yS2M.4s/dK0xb0a.cHPnS1qK',      'hr',       2, 'HR Manager',           55000.00, '2023-08-01'),
('Arjun Mehta',   'empuser', 'emp@empay.com',     '$2y$10$xS2Lqs.wjYDP1C.GO/8KiO8m6X9FCgvJx8jmDqIbRt0gQ6CIi2qK',     'employee', 1, 'Software Engineer',    60000.00, '2024-03-15'),
('Vikram Singh',  'payuser', 'payroll@empay.com', '$2y$10$r8t8Ys9I6d7G3FzJhV3SOeNgvEfR8/KQF7vOLmHpY2s2UPQWO1qK', 'payroll',  5, 'Payroll Officer',      65000.00, '2023-11-05'),
('Ravi Kumar',    'ravi',    'ravi@empay.com',    '$2y$10$xS2Lqs.wjYDP1C.GO/8KiO8m6X9FCgvJx8jmDqIbRt0gQ6CIi',    'employee', 3, 'Marketing Executive',  50000.00, '2025-01-10'),
('Sneha Patel',   'sneha',   'sneha@empay.com',   '$2y$10$xS2Lqs.wjYDP1C.GO/8KiO8m6X9FCgvJx8jmDqIbRt0gQ6CIi',   'employee', 4, 'UI Designer',          52000.00, '2024-06-22'),
('Ananya Gupta',  'ananya',  'ananya@empay.com',  '$2y$10$xS2Lqs.wjYDP1C.GO/8KiO8m6X9FCgvJx8jmDqIbRt0gQ6CIi',  'employee', 1, 'Backend Developer',    58000.00, '2025-02-18');

-- Leave Balances for 2026
INSERT INTO `leave_balances` (`user_id`, `leave_type_id`, `year`, `total_days`, `used_days`) VALUES
-- Arjun Mehta (id=3)
(3, 1, 2026, 12, 4),
(3, 2, 2026,  7, 2),
(3, 3, 2026, 15, 5),
-- Priya Sharma (id=2)
(2, 1, 2026, 12, 3),
(2, 2, 2026,  7, 1),
(2, 3, 2026, 15, 2),
-- Ravi Kumar (id=5)
(5, 1, 2026, 12, 5),
(5, 2, 2026,  7, 0),
(5, 3, 2026, 15, 3);

-- Sample Attendance (May 2026)
INSERT INTO `attendance` (`user_id`, `date`, `check_in`, `check_out`, `status`) VALUES
(3, '2026-05-01', '08:55:00', '18:00:00', 'present'),
(3, '2026-05-02', '09:02:00', '18:15:00', 'present'),
(5, '2026-05-01', '09:10:00', '18:05:00', 'present'),
(5, '2026-05-02', '10:20:00', '18:30:00', 'late'),
(6, '2026-05-01', '09:00:00', '18:00:00', 'present'),
(6, '2026-05-02', NULL,       NULL,        'absent'),
(2, '2026-05-01', '08:50:00', '17:45:00', 'present'),
(2, '2026-05-02', '09:15:00', '18:10:00', 'present'),
(4, '2026-05-01', '09:05:00', '18:00:00', 'present'),
(4, '2026-05-02', '08:55:00', '18:00:00', 'present');

-- Sample Leave Requests
INSERT INTO `leaves` (`user_id`, `leave_type_id`, `from_date`, `to_date`, `days`, `reason`, `status`, `approved_by`, `approved_at`) VALUES
(2, 2, '2026-05-05', '2026-05-06', 2, 'Medical appointment with doctor',   'pending',  NULL, NULL),
(5, 1, '2026-05-08', '2026-05-08', 1, 'Personal work - bank visit',        'approved', 1,    '2026-05-03 10:30:00'),
(6, 3, '2026-05-12', '2026-05-16', 5, 'Family vacation trip',              'pending',  NULL, NULL),
(3, 2, '2026-04-28', '2026-04-29', 2, 'Fever and cold',                    'approved', 2,    '2026-04-27 14:00:00'),
(7, 1, '2026-05-03', '2026-05-03', 1, 'Personal errand',                   'rejected', 2,    '2026-05-02 09:00:00');

-- Sample Payroll (April 2026)
INSERT INTO `payroll` (`user_id`, `month`, `working_days`, `present_days`, `basic_salary`, `hra`, `transport`, `special`, `pf`, `professional_tax`, `tds`, `other_deductions`, `status`, `paid_on`, `generated_by`) VALUES
(3, '2026-04', 22, 21, 60000.00, 15000.00, 3000.00, 5000.00, 2160.00, 200.00, 3040.00, 0.00, 'paid', '2026-04-30', 4),
(2, '2026-04', 22, 22, 55000.00, 13750.00, 3000.00, 4000.00, 1980.00, 200.00, 2620.00, 0.00, 'paid', '2026-04-30', 4),
(5, '2026-04', 22, 20, 50000.00, 12500.00, 3000.00, 3500.00, 1800.00, 200.00, 2200.00, 0.00, 'paid', '2026-04-30', 4),
(6, '2026-04', 22, 19, 52000.00, 13000.00, 3000.00, 4000.00, 1872.00, 200.00, 2528.00, 0.00, 'paid', '2026-04-30', 4),
(4, '2026-04', 22, 22, 65000.00, 16250.00, 3000.00, 5000.00, 2340.00, 200.00, 3460.00, 0.00, 'paid', '2026-04-30', 4),
(7, '2026-04', 22, 21, 58000.00, 14500.00, 3000.00, 4500.00, 2088.00, 200.00, 2912.00, 0.00, 'paid', '2026-04-30', 4);

-- ============================================
-- HELPFUL VIEWS
-- ============================================

-- Employee directory with department name
CREATE OR REPLACE VIEW `v_employees` AS
SELECT 
    u.id,
    u.full_name,
    u.username,
    u.email,
    u.phone,
    u.role,
    u.designation,
    d.name AS department,
    u.basic_salary,
    u.date_of_join,
    u.is_active,
    u.last_login
FROM `users` u
LEFT JOIN `departments` d ON u.department_id = d.id;

-- Monthly attendance summary
CREATE OR REPLACE VIEW `v_attendance_summary` AS
SELECT
    a.user_id,
    u.full_name,
    DATE_FORMAT(a.date, '%Y-%m') AS month,
    COUNT(CASE WHEN a.status = 'present' THEN 1 END) AS present_days,
    COUNT(CASE WHEN a.status = 'late' THEN 1 END)    AS late_days,
    COUNT(CASE WHEN a.status = 'absent' THEN 1 END)  AS absent_days,
    ROUND(AVG(a.work_hours), 2)                       AS avg_hours
FROM `attendance` a
JOIN `users` u ON a.user_id = u.id
GROUP BY a.user_id, u.full_name, DATE_FORMAT(a.date, '%Y-%m');

-- Payslip view with full breakdown
CREATE OR REPLACE VIEW `v_payslips` AS
SELECT
    p.id,
    p.user_id,
    u.full_name,
    d.name AS department,
    u.designation,
    p.month,
    p.working_days,
    p.present_days,
    p.basic_salary,
    p.hra,
    p.transport,
    p.special,
    p.gross_salary,
    p.pf,
    p.professional_tax,
    p.tds,
    p.other_deductions,
    p.total_deductions,
    p.net_salary,
    p.status,
    p.paid_on
FROM `payroll` p
JOIN `users` u ON p.user_id = u.id
LEFT JOIN `departments` d ON u.department_id = d.id;
