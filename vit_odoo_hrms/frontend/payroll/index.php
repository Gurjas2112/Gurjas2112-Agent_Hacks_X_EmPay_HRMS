<?php
$pageTitle = 'Payroll';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_PAYROLL);
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();

$sql = "SELECT month, COUNT(id) as emp_count, SUM(net_salary) as total_payout, MIN(status) as status
        FROM payroll
        GROUP BY month
        ORDER BY month DESC";
$payruns = $db->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Pay Runs</h1>
    <form action="<?= BASE_URL ?>../backend/payroll/generate_salary.php" method="POST" class="flex items-center gap-2">
        <input type="month" name="month" value="<?= date('Y-m') ?>" class="form-input !w-auto">
        <button type="submit" class="btn btn-primary">Generate Payroll</button>
    </form>
</div>

<div class="card !p-0">
    <table class="data-table">
        <thead><tr>
            <th>Pay Period</th><th>Employees</th><th>Total Payout</th><th>Status</th><th class="text-right">Actions</th>
        </tr></thead>
        <tbody>
            <?php if (count($payruns) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">No pay runs generated yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($payruns as $run):
                $monthStr = date('F Y', strtotime($run['month'] . '-01'));
                $payout = '₹ ' . number_format($run['total_payout'], 2);
                $bc = $run['status'] === 'paid' ? 'badge-done' : ($run['status'] === 'generated' ? 'badge-approved' : 'badge-draft');
            ?>
            <tr>
                <td class="font-medium text-primary"><?= $monthStr ?></td>
                <td class="text-muted"><?= $run['emp_count'] ?> employees</td>
                <td class="font-medium"><?= $payout ?></td>
                <td><span class="badge <?= $bc ?> capitalize"><?= htmlspecialchars($run['status']) ?></span></td>
                <td class="text-right">
                    <a href="<?= BASE_URL ?>index.php?page=payroll/my_payslips&month=<?= urlencode($run['month']) ?>" class="btn btn-secondary text-[12px]">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
