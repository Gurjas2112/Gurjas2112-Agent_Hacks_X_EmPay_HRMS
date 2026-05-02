<?php
/**
 * EmPay HRMS - Apply Leave Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=leave/apply');
    exit;
}

$userId    = (int)($_POST['user_id'] ?? getUserId());
$leaveType = (int)($_POST['leave_type_id'] ?? 0);
$fromDate  = $_POST['from_date'] ?? '';
$toDate    = $_POST['to_date'] ?? '';
$reason    = trim($_POST['reason'] ?? '');

// Validation
if (empty($leaveType) || empty($fromDate) || empty($toDate) || empty($reason)) {
    setFlash('error', 'All fields are required.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/apply');
    exit;
}

if (strtotime($toDate) < strtotime($fromDate)) {
    setFlash('error', 'End date cannot be before start date.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/apply');
    exit;
}

// Validation: Employee can only apply for themselves
if ($userId !== getUserId() && !canManageUsers()) {
    setFlash('error', 'Unauthorized action.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/apply');
    exit;
}

$days = (int)((strtotime($toDate) - strtotime($fromDate)) / 86400) + 1;

try {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO leaves (user_id, leave_type_id, from_date, to_date, days, reason, status) VALUES (?,?,?,?,?,?,'pending')");
    $stmt->execute([$userId, $leaveType, $fromDate, $toDate, $days, $reason]);
} catch (PDOException $e) {
    error_log("DB Error apply leave: " . $e->getMessage());
    setFlash('error', 'Database error.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/apply');
    exit;
}

setFlash('success', "Leave request submitted for {$days} day(s). Awaiting approval.");
header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
exit;
