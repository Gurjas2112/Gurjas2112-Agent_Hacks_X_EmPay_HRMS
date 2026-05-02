USE empay_hrms;

-- 1. Rename roles table to designations
RENAME TABLE roles TO designations;

-- 2. Rename role_id columns to designation_id
ALTER TABLE users CHANGE COLUMN role_id designation_id INT;
ALTER TABLE work_policies CHANGE COLUMN role_id designation_id INT;
ALTER TABLE leave_policies CHANGE COLUMN role_id designation_id INT;

-- 3. Update existing data if necessary (already mapped in previous turn)
-- No data change needed, just column names.
