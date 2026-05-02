-- 1. Create schedules table
CREATE TABLE IF NOT EXISTS schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    notes VARCHAR(255),
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Seed some data if users table is empty
INSERT IGNORE INTO departments (id, name, description) VALUES 
(1, 'Engineering', 'Software Development'),
(2, 'Human Resources', 'HR & Admin'),
(3, 'Finance', 'Payroll and Accounting');

INSERT IGNORE INTO users (id, full_name, username, email, password, role, department_id, is_active) VALUES 
(1, 'Admin User', 'admin', 'admin@empay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1),
(2, 'HR Manager', 'hr', 'hr@empay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr', 2, 1),
(3, 'Payroll Officer', 'payroll', 'payroll@empay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'payroll', 3, 1),
(4, 'Arjun Mehta', 'arjun', 'arjun@empay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, 1),
(5, 'Priya Sharma', 'priya', 'priya@empay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, 1);

-- 3. Seed some attendance
INSERT IGNORE INTO attendance (id, user_id, date, check_in, check_out, status) VALUES 
(1, 4, CURRENT_DATE(), '09:00:00', NULL, 'present'),
(2, 5, CURRENT_DATE(), '09:15:00', NULL, 'present');

-- 4. Seed some leaves
INSERT IGNORE INTO leave_types (id, name, max_days) VALUES (1, 'Sick Leave', 10), (2, 'Casual Leave', 12);
INSERT IGNORE INTO leaves (id, user_id, leave_type_id, from_date, to_date, days, reason, status) VALUES 
(1, 4, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 6 DAY), 2, 'Fever', 'pending');

-- 5. Seed some schedules
INSERT IGNORE INTO schedules (id, user_id, shift_date, start_time, end_time, notes, created_by) VALUES 
(1, 4, CURRENT_DATE(), '09:00:00', '17:00:00', 'Regular Shift', 1),
(2, 4, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '09:00:00', '17:00:00', 'Regular Shift', 1),
(3, 5, CURRENT_DATE(), '10:00:00', '18:00:00', 'Late Shift', 1);
