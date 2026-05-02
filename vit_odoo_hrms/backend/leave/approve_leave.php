<?php
/**
 * EmPay HRMS - Approve/Reject Leave Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

requireRole(ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
    exit;
}

$leaveId = (int)($_POST['leave_id'] ?? 0);
$action  = $_POST['action'] ?? ''; // 'approve' or 'reject'

if ($leaveId <= 0 || !in_array($action, ['approve', 'reject'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
    exit;
}

$status = $action === 'approve' ? 'approved' : 'rejected';

try {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE leaves SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->execute([$status, getUserId(), $leaveId]);
} catch (PDOException $e) {
    error_log("DB Error approve leave: " . $e->getMessage());
    setFlash('error', 'Database error.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
    exit;
}

setFlash('success', 'Leave request ' . $status . ' successfully.');
header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
exit;
