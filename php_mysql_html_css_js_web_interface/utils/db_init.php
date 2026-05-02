<?php
/**
 * EmPay HRMS — Database Initialization Utility
 * Ensures all required tables exist for communication metrics.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Initialize core database tables if they do not exist
 * Focused on the email_logs table for audit trails.
 */
function initDatabase() {
    $db = getDBConnection();
    
    // Create email_logs table for tracking all automated communication
    $sql = "CREATE TABLE IF NOT EXISTS email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_email VARCHAR(255),
        recipient_email VARCHAR(255),
        subject VARCHAR(255),
        status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
        error_message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $db->exec($sql);
        
        // Add salary column to users if not exists
        try {
            $db->exec("ALTER TABLE users ADD COLUMN salary DECIMAL(10,2) DEFAULT 0.00 AFTER department_id");
        } catch (PDOException $e) {
            // Column likely already exists, ignore
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("EmPay Database Init Error: " . $e->getMessage());
        return false;
    }
}

// Auto-run initialization if this script is accessed directly for setup
if (isset($argv) || (isset($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']))) {
    if (initDatabase()) {
        if (php_sapi_name() === 'cli') {
            echo "Success: EmPay database initialized.\n";
        }
    } else {
        if (php_sapi_name() === 'cli') {
            echo "Error: EmPay database initialization failed.\n";
        }
    }
}
