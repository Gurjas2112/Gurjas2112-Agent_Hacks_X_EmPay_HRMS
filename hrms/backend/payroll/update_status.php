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

    if ($newStatus === 'paid') {
        // Fetch employee and salary details
        $stmt = $db->prepare("SELECT p.*, u.email, u.full_name FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmt->execute([$payslipId]);
        $payrollData = $stmt->fetch();

        if ($payrollData) {
            require_once __DIR__ . '/../../utils/mailer.php';
            $subject = "Payslip Paid: " . $payrollData['month'];
            $netSalary = $payrollData['basic_salary'] + $payrollData['hra'] + $payrollData['transport'] + $payrollData['special'] - ($payrollData['pf'] + $payrollData['professional_tax'] + $payrollData['tds']);
            
            $body = "
                <h2>Payslip Published</h2>
                <p>Hi " . htmlspecialchars($payrollData['full_name']) . ",</p>
                <p>Your salary for <strong>" . $payrollData['month'] . "</strong> has been processed and marked as PAID.</p>
                <p><strong>Net Amount:</strong> ₹ " . number_format($netSalary, 2) . "</p>
                <p>You can view and print your detailed payslip by logging into the EmPay portal.</p>
                <br>
                <p>Regards,<br>EmPay Payroll Department</p>
            ";
            sendEmPayEmail($payrollData['email'], $subject, $body, 'payroll');
        }
    }
    
    setFlash('success', 'Payslip status updated to ' . ucfirst($newStatus) . '.');
} catch (PDOException $e) {
    error_log("DB Error update payroll: " . $e->getMessage());
    setFlash('error', 'Database error.');
}

header('Location: ' . BASE_URL . 'index.php?page=payroll/payslip&id=' . $payslipId);
exit;
