<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$res = $db->query("SHOW TABLES LIKE 'email_logs'")->fetch();
echo $res ? "EXISTS" : "MISSING";
