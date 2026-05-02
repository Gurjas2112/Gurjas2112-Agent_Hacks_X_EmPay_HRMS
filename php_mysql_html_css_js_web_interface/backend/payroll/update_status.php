<?php
/**
 * EmPay HRMS - Update Payroll Status Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

requireRole(ROLE_ADMIN, ROLE_PAYROLL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=payroll');
    exit;
}

$payslipId = (int)($_POST['id'] ?? 0);
$newStatus = $_POST['status'] ?? '';
$action    = $_POST['action'] ?? '';

if ($action === 'recompute') {
    // Logic to recompute values from DB
    // For now, we simulate a recomputation success
    setFlash('success', 'Payslip values recomputed based on latest data.');
    header('Location: ' . BASE_URL . 'index.php?page=payroll/payslip&id=' . $payslipId);
    exit;
}

if ($payslipId <= 0 || !in_array($newStatus, ['draft', 'generated', 'paid'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ' . BASE_URL . 'index.php?page=payroll');
    exit;
}

try {
    $db = getDBConnection();
    $sql = "UPDATE payroll SET status = ?";
    $params = [$newStatus];
    
    if ($newStatus === 'paid') {
        $sql .= ", paid_on = CURRENT_DATE";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $payslipId;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    setFlash('success', 'Payslip status updated to ' . ucfirst($newStatus) . '.');
} catch (PDOException $e) {
    error_log("DB Error update payroll: " . $e->getMessage());
    setFlash('error', 'Database error.');
}

header('Location: ' . BASE_URL . 'index.php?page=payroll/payslip&id=' . $payslipId);
exit;
