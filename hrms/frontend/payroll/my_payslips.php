<?php
$pageTitle = 'My Payslips';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();
$userId = getUserId();
$role = getUserRole();

$monthFilter = $_GET['month'] ?? null;
$params = [];
$sql = "SELECT p.*, u.full_name as name 
        FROM payroll p 
        JOIN users u ON p.user_id = u.id ";

if ($role === ROLE_EMPLOYEE) {
    $sql .= " WHERE p.user_id = ? ";
    $params[] = $userId;
    // Employee only sees generated or paid, not draft? 
    // Usually they see it when status != 'draft' or if it's 'paid'
    $sql .= " AND p.status != 'draft' ";
} else {
    // Admin/HR/Payroll can filter by month
    if ($monthFilter) {
        $sql .= " WHERE p.month = ? ";
        $params[] = $monthFilter;
    }
}
$sql .= " ORDER BY p.month DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payslips = $stmt->fetchAll();

$hasPayslips = count($payslips) > 0;
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">My Payslips</h1>
</div>

<?php if ($hasPayslips): ?>
    <div class="card !p-0">
        <table class="data-table">
            <thead><tr>
                <th>Month</th><th>Employee</th><th>Net Salary</th><th>Status</th><th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                <?php foreach ($payslips as $p):
                    $bc = $p['status'] === 'paid' ? 'badge-done' : ($p['status'] === 'generated' ? 'badge-approved' : 'badge-draft');
                ?>
                <tr>
                    <td class="font-medium text-primary"><?= date('F Y', strtotime($p['month'].'-01')) ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td class="font-medium">₹ <?= number_format($p['net_salary'], 2) ?></td>
                    <td><span class="badge <?= $bc ?> capitalize"><?= htmlspecialchars($p['status']) ?></span></td>
                    <td class="text-right">
                        <a href="<?= BASE_URL ?>index.php?page=payroll/payslip&id=<?= $p['id'] ?>" class="btn btn-secondary text-[12px]">View</a>
                        <?php if ($role !== ROLE_EMPLOYEE): ?>
                        <a href="<?= BASE_URL ?>../backend/payroll/email_demo_payslip.php?id=<?= $p['id'] ?>" class="btn btn-primary text-[12px]">Email Payslip</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i data-lucide="file-text" class="empty-state-icon"></i>
        <p class="empty-state-title">No Payslips Yet</p>
        <p class="empty-state-text">Your payslips will appear here once your HR team confirms your first pay run.</p>
    </div>
<?php endif; ?>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
