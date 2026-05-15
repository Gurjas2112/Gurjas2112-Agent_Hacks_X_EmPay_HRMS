<?php
require 'c:/xampp/htdocs/Agent_Hacks_X_EmPay_HRMS/php_mysql_html_css_js_web_interface/config/database.php';
$db = getDBConnection();
try {
    $stmt = $db->query("
        SELECT a.*, u.full_name, d.name as dept_name, des.name as job_title 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN designations des ON u.designation_id = des.id
        WHERE a.latitude IS NOT NULL AND a.date = CURRENT_DATE
    ");
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Attendees count: " . count($attendees) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
