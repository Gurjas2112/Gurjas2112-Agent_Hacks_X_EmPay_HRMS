<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = getDBConnection();
    echo "<h3>System Diagnostics</h3>";
    
    // Check Users
    $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<li>Total Users in Database: <b>$count</b></li>";
    
    $joy = $db->query("SELECT id, full_name FROM users WHERE id = 13")->fetch();
    if ($joy) {
        echo "<li>User ID 13: <b>" . $joy['full_name'] . "</b> FOUND.</li>";
    } else {
        echo "<li>User ID 13: <b>NOT FOUND</b>.</li>";
    }
    
    // Check April Payroll
    $apr = $db->query("SELECT COUNT(*) FROM payroll WHERE month = '2026-04'")->fetchColumn();
    echo "<li>April 2026 Payroll Records: <b>$apr</b></li>";
    
    $joyApr = $db->query("SELECT id FROM payroll WHERE user_id = 13 AND month = '2026-04'")->fetch();
    if ($joyApr) {
        echo "<li>Joy Kapoor April Payroll: <b>EXISTS</b></li>";
    } else {
        echo "<li>Joy Kapoor April Payroll: <b>MISSING</b></li>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
