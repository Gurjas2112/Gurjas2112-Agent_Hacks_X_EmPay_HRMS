<?php
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

$sql = "CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_email VARCHAR(255),
    recipient_email VARCHAR(255),
    subject VARCHAR(255),
    status ENUM('sent', 'failed'),
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($sql);
    echo "Table 'email_logs' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
