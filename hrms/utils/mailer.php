<?php
/**
 * EmPay HRMS — Email Utility
 * Handles sending emails via PHPMailer and logging metrics
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/db_init.php';

/**
 * Send an email using role-based SMTP credentials
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body HTML body
 * @param string $role 'hr', 'payroll', or 'system'
 * @return bool Success status
 */
function sendEmPayEmail($to, $subject, $body, $role = 'system') {
    // Ensure email_logs table exists
    initDatabase();
    
    $mailConfig = require __DIR__ . '/../config/mail.php';
    $account = $mailConfig['accounts'][$role] ?? $mailConfig['accounts']['system'];
    
    $mail = new PHPMailer(true);
    $db = getDBConnection();
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $account['email'];
        $mail->Password   = $account['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailConfig['port'];
        $mail->Timeout    = 30;
        
        // Critical for Gmail/Localhost SSL issues
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $mail->setFrom($account['email'], $account['name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        
        // Log Success
        $stmt = $db->prepare("INSERT INTO email_logs (sender_email, recipient_email, subject, status) VALUES (?, ?, ?, 'sent')");
        $stmt->execute([$account['email'], $to, $subject]);
        
        return true;
    } catch (Exception $e) {
        // Log Failure
        try {
            $stmt = $db->prepare("INSERT INTO email_logs (sender_email, recipient_email, subject, status, error_message) VALUES (?, ?, ?, 'failed', ?)");
            $stmt->execute([$account['email'], $to, $subject, $mail->ErrorInfo]);
        } catch (PDOException $pdoE) {
            error_log("Failed to log email failure: " . $pdoE->getMessage());
        }
        
        return false;
    }
}
