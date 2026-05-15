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
    
    // Fetch employee details before update
    $stmt = $db->prepare("SELECT l.*, u.email, u.full_name, t.name as type 
                          FROM leaves l 
                          JOIN users u ON l.user_id = u.id 
                          JOIN leave_types t ON l.leave_type_id = t.id
                          WHERE l.id = ?");
    $stmt->execute([$leaveId]);
    $leaveData = $stmt->fetch();

    $stmt = $db->prepare("UPDATE leaves SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $stmt->execute([$status, getUserId(), $leaveId]);

    if ($leaveData) {
        require_once __DIR__ . '/../../utils/mailer.php';
        $subject = "Leave Request Update: " . ucfirst($status);
        $body = "
            <h2>Leave Request Update</h2>
            <p>Hi " . htmlspecialchars($leaveData['full_name']) . ",</p>
            <p>Your leave request for <strong>" . htmlspecialchars($leaveData['type']) . "</strong> has been <strong>" . strtoupper($status) . "</strong>.</p>
            <p><strong>Period:</strong> " . $leaveData['from_date'] . " to " . $leaveData['to_date'] . " (" . $leaveData['days'] . " days)</p>
            <br>
            <p>Regards,<br>EmPay HR Department</p>
        ";
        sendEmPayEmail($leaveData['email'], $subject, $body, 'hr');
    }

} catch (PDOException $e) {
    error_log("DB Error approve leave: " . $e->getMessage());
    setFlash('error', 'Database error.');
    header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
    exit;
}

setFlash('success', 'Leave request ' . $status . ' successfully.');
header('Location: ' . BASE_URL . 'index.php?page=leave/manage');
exit;
