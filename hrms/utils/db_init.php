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
    
    if (!$db) {
        error_log("EmPay Database Init Error: Could not connect to database.");
        return false;
    }
    
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
    
    // Create system_settings table
    $settingsSql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        setting_group VARCHAR(50) DEFAULT 'general'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    try {
        $db->exec($sql);
        $db->exec($settingsSql);
        
        // Seed default location if missing
        $db->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_group) VALUES 
            ('office_lat', '18.5204', 'location'),
            ('office_lng', '73.8567', 'location'),
            ('nfc_mode', 'standard', 'attendance')");
        
        // Add salary column to users if not exists
        try {
            $db->exec("ALTER TABLE users ADD COLUMN salary DECIMAL(10,2) DEFAULT 0.00 AFTER department_id");
        } catch (PDOException $e) {
            // Column likely already exists, ignore
        }
        
        // Add geolocation columns to attendance if not exists
        try {
            $db->exec("ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER notes");
            $db->exec("ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude");
            $db->exec("ALTER TABLE attendance ADD COLUMN location_type ENUM('office', 'remote', 'field') DEFAULT 'office' AFTER longitude");
        } catch (PDOException $e) { }

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
