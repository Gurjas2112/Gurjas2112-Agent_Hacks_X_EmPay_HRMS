USE empay_hrms;

-- 1. Clear the designations table (removing the auth roles mistakenly added)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE designations;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Insert Real Job Designations
INSERT INTO designations (name) VALUES 
('Software Engineer'),
('Senior Sales Executive'),
('HR Manager'),
('Project Manager'),
('Accountant'),
('Technical Lead'),
('Customer Support Specialist');

-- 3. Update users to associate with real designations (Demo mapping)
-- We'll just distribute them for now as a demo
UPDATE users SET designation_id = (SELECT id FROM designations WHERE name = 'Software Engineer') WHERE id % 3 = 0;
UPDATE users SET designation_id = (SELECT id FROM designations WHERE name = 'Senior Sales Executive') WHERE id % 3 = 1;
UPDATE users SET designation_id = (SELECT id FROM designations WHERE name = 'HR Manager') WHERE id % 3 = 2;

-- 4. Re-populate policies for these new designations (Demo)
TRUNCATE TABLE work_policies;
TRUNCATE TABLE leave_policies;

-- Default Policies
INSERT INTO work_policies (is_default, working_days_per_week, weekly_off_days, start_time, end_time) 
VALUES (TRUE, 5, 'Sunday', '09:00:00', '18:00:00');

INSERT INTO leave_policies (is_default, paid_leaves, sick_leaves, casual_leaves) 
VALUES (TRUE, 12, 6, 6);

-- Specific Override for Software Engineer (e.g. 9:00 - 19:00)
INSERT INTO work_policies (designation_id, is_default, working_days_per_week, weekly_off_days, start_time, end_time)
SELECT id, 0, 5, 'Saturday, Sunday', '10:00:00', '19:00:00' FROM designations WHERE name = 'Software Engineer';
