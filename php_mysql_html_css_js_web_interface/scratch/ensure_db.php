<?php
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "Connected successfully to MySQL\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS empay_hrms");
    echo "Database empay_hrms ensured.\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
