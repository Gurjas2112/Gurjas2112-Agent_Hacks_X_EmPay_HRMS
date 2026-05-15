<?php
/**
 * EmPay HRMS - Automated Demo Data Provisioner
 * Hardened version: ensures Net Salary is never 0.0
 */
function provisionDemoData() {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/db_init.php';
    
    // Ensure table structure is up to date
    initDatabase();
    
    $db = getDBConnection();
    if (!$db) return;

    try {
        // 1. Ensure Joy Kapoor exists with a proper salary
        $stmt = $db->prepare("SELECT id FROM users WHERE id = 13");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $sql = "INSERT INTO users (id, full_name, username, email, phone, role, designation, department_id, salary, date_of_join, password) 
                    VALUES (13, 'JOY KAPOOR', 'kobi_gurr', 'kari22yorokobi@gmail.com', '+91 8928138685', 'employee', 'Software Engineer', 2, 58000.00, '2026-05-21', ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([password_hash('EMP132002', PASSWORD_DEFAULT)]);
        }

        // 2. Fetch all users to populate full payroll and update zero salaries
        $users = $db->query("SELECT id, salary FROM users")->fetchAll();
        
        $months = [
            ['month' => '2026-04', 'status' => 'paid'],
            ['month' => '2026-05', 'status' => 'generated']
        ];

        $stmtCheck = $db->prepare("SELECT id FROM payroll WHERE user_id = ? AND month = ?");
        $stmtInsert = $db->prepare("INSERT INTO payroll (user_id, month, net_salary, status) VALUES (?, ?, ?, ?)");
        $stmtUpdateUser = $db->prepare("UPDATE users SET salary = ? WHERE id = ? AND (salary = 0 OR salary IS NULL)");

        foreach ($users as $u) {
            // Ensure user salary is not zero in profile
            $userSalary = (float)$u['salary'];
            if ($userSalary <= 0) {
                $userSalary = 50000 + (($u['id'] * 1234) % 35000);
                $stmtUpdateUser->execute([$userSalary, $u['id']]);
            }

            foreach ($months as $m) {
                $stmtCheck->execute([$u['id'], $m['month']]);
                if (!$stmtCheck->fetch()) {
                    $stmtInsert->execute([$u['id'], $m['month'], $userSalary, $m['status']]);
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Demo Provisioning Error: " . $e->getMessage());
    }
}
