<?php
/**
 * EmPay HRMS - CRITICAL DEMO SETUP
 * This script FORCES Joy Kapoor and his payroll data into the system.
 */
require_once __DIR__ . '/../config/database.php';

echo "<div style='font-family:sans-serif; padding:20px;'>";
echo "<h2>🚀 EmPay Data Synchronizer</h2>";

try {
    $db = getDBConnection();
    
    // 1. Force JOY KAPOOR User
    $db->exec("DELETE FROM users WHERE id = 13"); // Clean start for ID 13
    $sql = "INSERT INTO users (id, full_name, username, email, phone, role, designation, department_id, date_of_join, password) 
            VALUES (13, 'JOY KAPOOR', 'kobi_gurr', 'kari22yorokobi@gmail.com', '+91 8928138685', 'employee', 'Software Engineer', 2, '2026-05-21', ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([password_hash('EMP132002', PASSWORD_DEFAULT)]);
    echo "<p>✅ <b>JOY KAPOOR</b> (ID 13) has been inserted into the database.</p>";

    // 2. Force April 2026 Payroll
    $db->exec("DELETE FROM payroll WHERE user_id = 13 AND month = '2026-04'");
    $db->exec("INSERT INTO payroll (user_id, month, net_salary, status) VALUES (13, '2026-04', 58000.00, 'paid')");
    echo "<p>✅ <b>April 2026</b> Payroll Record created for Joy Kapoor.</p>";

    // 3. Force May 2026 Payroll
    $db->exec("DELETE FROM payroll WHERE user_id = 13 AND month = '2026-05'");
    $db->exec("INSERT INTO payroll (user_id, month, net_salary, status) VALUES (13, '2026-05', 58000.00, 'generated')");
    echo "<p>✅ <b>May 2026</b> Payroll Record created for Joy Kapoor.</p>";

    echo "<hr><h3 style='color:green'>Success! JOY KAPOOR will now appear on:</h3>";
    echo "<ul>
            <li><b>April Payroll Details:</b> ?page=payroll/my_payslips&month=2026-04</li>
            <li><b>Individual Payslips:</b> ?page=payroll/my_payslips</li>
            <li><b>Operational Reports:</b> ?page=reports/index</li>
          </ul>";
    echo "<p><b>Please go back to your dashboard and refresh the pages.</b></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";
