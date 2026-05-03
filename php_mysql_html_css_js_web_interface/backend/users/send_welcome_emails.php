<?php
/**
 * EmPay HRMS - Send Batch Welcome Emails
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
require_once __DIR__ . '/../../utils/mailer.php';

requireRole(ROLE_ADMIN, ROLE_HR);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_ids'])) {
    header('Location: ' . BASE_URL . 'index.php?page=users/import');
    exit;
}

$userIds = $_POST['user_ids'];
$db = getDBConnection();

$successCount = 0;
$failCount = 0;

foreach ($userIds as $id) {
    $stmt = $db->prepare("SELECT full_name, email, username FROM users WHERE id = ?");
    $stmt->execute([(int)$id]);
    $user = $stmt->fetch();

    if ($user && !empty($user['email'])) {
        $subject = "Welcome to " . APP_NAME . " - Your Account Details";
        
        $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #6366f1; padding: 32px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 24px;'>Welcome to the Team!</h1>
                </div>
                <div style='padding: 32px; color: #334155; line-height: 1.6;'>
                    <p>Hello <strong>{$user['full_name']}</strong>,</p>
                    <p>Your account on <strong>" . APP_NAME . "</strong> has been successfully created. You can now log in to manage your attendance, leave, and view your payslips.</p>
                    
                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 24px 0;'>
                        <h3 style='margin: 0 0 12px 0; font-size: 14px; color: #475569;'>Login Credentials:</h3>
                        <p style='margin: 4px 0;'><strong>Username:</strong> <code style='background: #e2e8f0; padding: 2px 4px; border-radius: 4px;'>{$user['username']}</code></p>
                        <p style='margin: 4px 0;'><strong>Temporary Password:</strong> <code style='background: #e2e8f0; padding: 2px 4px; border-radius: 4px;'>Empay@123</code></p>
                        <p style='margin: 12px 0 0 0; font-size: 12px; color: #64748b;'>* Please change your password after your first login.</p>
                    </div>

                    <div style='text-align: center;'>
                        <a href='" . BASE_URL . "' style='background-color: #6366f1; color: white; padding: 12px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;'>Login to Portal</a>
                    </div>
                </div>
                <div style='background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8;'>
                    This is an automated message. Please do not reply directly to this email.<br>
                    &copy; " . date('Y') . " " . APP_NAME . "
                </div>
            </div>
        ";

        if (sendEmPayEmail($user['email'], $subject, $body, 'system')) {
            $successCount++;
        } else {
            $failCount++;
        }
    }
}

setFlash('success', "Account creation emails sent to $successCount employees." . ($failCount > 0 ? " ($failCount failed)" : ""));
header('Location: ' . BASE_URL . 'index.php?page=users');
exit;
