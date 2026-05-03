<?php
/**
 * EmPay HRMS - Assign Schedule Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

// Strict Role Check - Only Admin or HR can assign schedules
if (!canManageUsers()) {
    setFlash('error', 'Unauthorized access.');
    header('Location: ' . BASE_URL . 'index.php?page=schedule/index');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=schedule/index');
    exit;
}

$db = getDBConnection();

$userId = (int)$_POST['user_id'];
$shiftDate = $_POST['shift_date'];
$startTime = $_POST['start_time'];
$endTime = $_POST['end_time'];
$notes = $_POST['notes'] ?? '';

if (!$userId || !$shiftDate || !$startTime || !$endTime) {
    setFlash('error', 'All required fields must be filled.');
    header('Location: ' . BASE_URL . 'index.php?page=schedule/index');
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO schedules (user_id, shift_date, start_time, end_time, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $shiftDate, $startTime, $endTime, $notes, getUserId()]);
    
    // Notify Employee via Email
    require_once __DIR__ . '/../../utils/mailer.php';
    
    $userStmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if ($user && !empty($user['email'])) {
        $subject = "New Work Schedule Assigned - " . APP_NAME;
        $formattedDate = date('D, d M Y', strtotime($shiftDate));
        $formattedTime = date('h:i A', strtotime($startTime)) . " - " . date('h:i A', strtotime($endTime));
        
        $body = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #4f46e5; padding: 24px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 20px;'>Work Schedule Notification</h1>
                </div>
                <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                    <p>Hello <strong>{$user['full_name']}</strong>,</p>
                    <p>A new work schedule has been assigned to you. Please find the details below:</p>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 16px;'>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; width: 100px;'>Date:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #f1f5f9;'>{$formattedDate}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600;'>Shift:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #f1f5f9;'>{$formattedTime}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600;'>Notes:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #f1f5f9;'>{$notes}</td>
                        </tr>
                    </table>
                    <div style='margin-top: 24px; text-align: center;'>
                        <a href='" . BASE_URL . "' style='background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;'>View Dashboard</a>
                    </div>
                </div>
                <div style='background-color: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #64748b;'>
                    &copy; " . date('Y') . " " . APP_NAME . ". All rights reserved.
                </div>
            </div>
        ";
        
        sendEmPayEmail($user['email'], $subject, $body, 'system');
    }
    
    setFlash('success', 'Schedule assigned successfully and notification sent.');
} catch (PDOException $e) {
    error_log("Database Error (assign schedule): " . $e->getMessage());
    setFlash('error', 'Failed to assign schedule. Please try again.');
}

header('Location: ' . BASE_URL . 'index.php?page=schedule/index');
exit;
