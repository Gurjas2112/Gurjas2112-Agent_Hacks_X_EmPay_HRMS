<?php
/**
 * Scratch script to add Joy Kapoor to Payroll
 */
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // 1. Check if user 13 exists
    $stmt = $db->prepare("SELECT id FROM users WHERE id = 13");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create user if missing
        $sql = "INSERT INTO users (id, full_name, username, email, phone, role, designation, department_id, date_of_join, password) 
                VALUES (13, 'JOY KAPOOR', 'kobi_gurr', 'kari22yorokobi@gmail.com', '+91 8928138685', 'employee', 'Software Engineer', 2, '2026-05-21', ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([password_hash('EMP132002', PASSWORD_DEFAULT)]);
        echo "Created User JOY KAPOOR (ID 13)\n";
    }

    // 2. Add Payroll record for May 2026
    $month = '2026-05';
    $stmt = $db->prepare("SELECT id FROM payroll WHERE user_id = 13 AND month = ?");
    $stmt->execute([$month]);
    if (!$stmt->fetch()) {
        $sql = "INSERT INTO payroll (user_id, month, net_salary, status) VALUES (13, ?, 58000.00, 'generated')";
        $stmt = $db->prepare($sql);
        $stmt->execute([$month]);
        echo "Added Payroll record for JOY KAPOOR for $month\n";
    } else {
        echo "Payroll record already exists for JOY KAPOOR for $month\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
