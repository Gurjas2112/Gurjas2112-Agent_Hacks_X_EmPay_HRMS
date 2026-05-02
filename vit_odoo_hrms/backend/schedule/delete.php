<?php
/**
 * EmPay HRMS - Delete Schedule Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

// Strict Role Check - Only Admin or HR can delete schedules
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
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    try {
        $stmt = $db->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Schedule deleted successfully.');
    } catch (PDOException $e) {
        error_log("Database Error (delete schedule): " . $e->getMessage());
        setFlash('error', 'Failed to delete schedule.');
    }
}

header('Location: ' . BASE_URL . 'index.php?page=schedule/index');
exit;
