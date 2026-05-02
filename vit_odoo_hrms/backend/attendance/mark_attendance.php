<?php
/**
 * EmPay HRMS - Mark Attendance Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
    exit;
}

$userId = (int)($_POST['user_id'] ?? getUserId());
$date   = $_POST['date'] ?? date('Y-m-d');
$action = $_POST['action'] ?? ''; // 'checkin' or 'checkout'
$time   = date('H:i:s');

if (!in_array($action, ['checkin', 'checkout'])) {
    setFlash('error', 'Invalid action.');
    header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
    exit;
}

$db = getDBConnection();

// Validation: Employee can only mark their own attendance
if ($userId !== getUserId() && !canManageUsers()) {
    setFlash('error', 'Unauthorized action.');
    header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
    exit;
}

try {
    if ($action === 'checkin') {
        // Check if already checked in today
        $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        if ($stmt->fetch()) {
            setFlash('error', 'Already checked in today.');
            header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
            exit;
        }

        // Determine if late (after 09:15)
        $status = $time > '09:15:00' ? 'late' : 'present';
        
        $stmt = $db->prepare("INSERT INTO attendance (user_id, date, check_in, status, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $date, $time, $status, $_SERVER['REMOTE_ADDR'] ?? '']);
    } else {
        // Check out
        $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND date = ? AND check_out IS NULL");
        $stmt->execute([$time, $userId, $date]);
        if ($stmt->rowCount() === 0) {
            setFlash('error', 'Check in first or already checked out.');
            header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("DB Error mark attendance: " . $e->getMessage());
    setFlash('error', 'Database error.');
    header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
    exit;
}

$msg = $action === 'checkin' ? 'Checked in at ' . date('h:i A') : 'Checked out at ' . date('h:i A');
setFlash('success', $msg);
header('Location: ' . BASE_URL . 'index.php?page=attendance/mark');
exit;
