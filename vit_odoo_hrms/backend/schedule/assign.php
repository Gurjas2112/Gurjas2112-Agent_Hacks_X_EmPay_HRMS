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
    setFlash('success', 'Schedule assigned successfully.');
} catch (PDOException $e) {
    error_log("Database Error (assign schedule): " . $e->getMessage());
    setFlash('error', 'Failed to assign schedule. Please try again.');
}

header('Location: ' . BASE_URL . 'index.php?page=schedule/index');
exit;
