<?php
require 'c:/xampp/htdocs/Agent_Hacks_X_EmPay_HRMS/php_mysql_html_css_js_web_interface/config/database.php';
$db = getDBConnection();
$stmt = $db->query("DESCRIBE designations");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
