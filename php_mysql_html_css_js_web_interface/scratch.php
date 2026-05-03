<?php
require 'c:/xampp/htdocs/Agent_Hacks_X_EmPay_HRMS/php_mysql_html_css_js_web_interface/config/database.php';
$db = getDBConnection();
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:\n";
    print_r($tables);
    
    $stmt = $db->query("DESCRIBE attendance");
    echo "\nAttendance columns:\n";
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

    $stmt = $db->query("DESCRIBE users");
    echo "\nUsers columns:\n";
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
