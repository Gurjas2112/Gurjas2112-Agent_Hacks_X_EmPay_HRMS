<?php
$pageTitle = 'Payslip Details';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();
$userId = getUserId();
$role = getUserRole();
$isEmployeeSelfService = !canManageUsers() && !canManagePayroll();

$payslipId = $_GET['id'] ?? null;
if (!$payslipId) {
    header('Location: ' . BASE_URL . 'index.php?page=payroll/my_payslips');
    exit;
}

// Fetch payslip
$stmt = $db->prepare("SELECT p.*, u.full_name as name 
                      FROM payroll p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.id = ?");
$stmt->execute([$payslipId]);
$payslip = $stmt->fetch();

if (!$payslip) {
    echo "Payslip not found.";
    exit;
}

// Strict Check: Employee can only see their own payslip
if ($isEmployeeSelfService && $payslip['user_id'] != $userId) {
    echo "Unauthorized access.";
    exit;
}

$empName = $payslip['name'];
$period = date('F Y', strtotime($payslip['month'].'-01'));
$status = $payslip['status'];

// Calculate fields
$basic = $payslip['basic_salary'];
$hra = $payslip['hra'];
$transport = $payslip['transport'];
$special = $payslip['special'];
$gross = $basic + $hra + $transport + $special;

$pf = $payslip['pf'];
$pt = $payslip['professional_tax'];
$tds = $payslip['tds'];
$otherDeds = $payslip['other_deductions'];
$totalDed = $pf + $pt + $tds + $otherDeds;

$net = $gross - $totalDed;
?>

<!-- Action Bar & Workflow Pipeline -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
    <div class="flex items-center gap-2">
        <?php if (!$isEmployeeSelfService): ?>
            <?php if ($status === 'draft'): ?>
                <form action="<?= BASE_URL ?>../backend/payroll/update_status.php" method="POST" class="inline">
                    <input type="hidden" name="id" value="<?= $payslipId ?>">
                    <input type="hidden" name="status" value="generated">
                    <button type="submit" class="btn btn-primary">Confirm Payslip</button>
                </form>
            <?php elseif ($status === 'generated'): ?>
                <form action="<?= BASE_URL ?>../backend/payroll/update_status.php" method="POST" class="inline">
                    <input type="hidden" name="id" value="<?= $payslipId ?>">
                    <input type="hidden" name="status" value="paid">
                    <button type="submit" class="btn btn-primary">Mark as Paid</button>
                </form>
                <form action="<?= BASE_URL ?>../backend/payroll/update_status.php" method="POST" class="inline">
                    <input type="hidden" name="id" value="<?= $payslipId ?>">
                    <input type="hidden" name="status" value="draft">
                    <button type="submit" class="btn btn-secondary">Set to Draft</button>
                </form>
            <?php endif; ?>
            
            <button class="btn btn-secondary">Recompute</button>
        <?php endif; ?>
        <button class="btn btn-secondary" onclick="window.print()"><i data-lucide="printer" class="w-4 h-4"></i> Print</button>
    </div>
    
    <div class="workflow-steps">
        <div class="workflow-step <?= $status === 'draft' ? 'active' : '' ?>">Draft</div>
        <div class="workflow-step <?= $status === 'generated' ? 'active' : '' ?>">Confirmed</div>
        <div class="workflow-step <?= $status === 'paid' ? 'active' : '' ?>">Done</div>
    </div>
</div>

<div class="card !p-0">
    <!-- Header Block (2-column grid per spec 7.1) -->
    <div class="p-6 border-b border-surface-200">
        <h1 class="text-[20px] font-medium mb-6"><?= $empName ?> — <?= $period ?></h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div class="grid grid-cols-3 items-center gap-4">
                    <span class="form-label mb-0 col-span-1">Employee</span>
                    <a href="#" class="link col-span-2 text-[13px] font-medium"><?= $empName ?></a>
                </div>
                <div class="grid grid-cols-3 items-center gap-4">
                    <span class="form-label mb-0 col-span-1">Contract</span>
                    <a href="#" class="link col-span-2 text-[13px]">CONT/2024/045</a>
                </div>
                <div class="grid grid-cols-3 items-center gap-4">
                    <span class="form-label mb-0 col-span-1">Pay Run</span>
                    <span class="col-span-2 text-[13px] text-muted">Batch_April_2026</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-3 items-center gap-4">
                    <span class="form-label mb-0 col-span-1">Period Start</span>
                    <span class="col-span-2 text-[13px]">01/04/2026</span>
                </div>
                <div class="grid grid-cols-3 items-center gap-4">
                    <span class="form-label mb-0 col-span-1">Period End</span>
                    <span class="col-span-2 text-[13px]">30/04/2026</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs per spec 5.5 -->
    <div class="tab-bar px-6 pt-4 bg-surface-50">
        <button class="tab-item" onclick="switchTab('attendance', this)">Attendance Summary</button>
        <button class="tab-item active" onclick="switchTab('salary', this)">Salary Breakdown</button>
    </div>

    <!-- Tab Contents -->
    <div class="p-6">
        <div id="tab-attendance" class="hidden">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                <div class="stat-card !p-4"><p class="stat-label">Present Days</p><p class="stat-value stat-value-positive">21.0</p></div>
                <div class="stat-card !p-4"><p class="stat-label">Half Days</p><p class="stat-value stat-value-neutral">0.0</p></div>
                <div class="stat-card !p-4"><p class="stat-label">Leave Days</p><p class="stat-value stat-value-neutral">1.0</p></div>
                <div class="stat-card !p-4"><p class="stat-label">Absent Days</p><p class="stat-value stat-value-negative">0.0</p></div>
                <div class="stat-card !p-4"><p class="stat-label">Total Working</p><p class="stat-value stat-value-neutral">22.0</p></div>
            </div>
        </div>

        <div id="tab-salary" class="block">
            <table class="data-table">
                <thead><tr>
                    <th>Component</th><th>Type</th><th class="text-right">Amount</th><th class="text-right">YTD Total</th>
                </tr></thead>
                <tbody>
                    <tr><td class="font-medium">Basic Salary</td><td class="text-muted">Earnings</td><td class="text-right text-txt">₹ <?= number_format($basic, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($basic * 12, 2) ?></td></tr>
                    <tr><td class="font-medium">House Rent Allowance (HRA)</td><td class="text-muted">Earnings</td><td class="text-right text-txt">₹ <?= number_format($hra, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($hra * 12, 2) ?></td></tr>
                    <tr><td class="font-medium">Transport Allowance</td><td class="text-muted">Earnings</td><td class="text-right text-txt">₹ <?= number_format($transport, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($transport * 12, 2) ?></td></tr>
                    <tr><td class="font-medium">Special Allowance</td><td class="text-muted">Earnings</td><td class="text-right text-txt">₹ <?= number_format($special, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($special * 12, 2) ?></td></tr>
                    <tr class="bg-surface-50"><td class="font-medium">Gross Earnings</td><td></td><td class="text-right font-medium text-success-text">₹ <?= number_format($gross, 2) ?></td><td></td></tr>
                    
                    <tr><td class="font-medium">Provident Fund (PF)</td><td class="text-muted">Deductions</td><td class="text-right text-txt">₹ <?= number_format($pf, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($pf * 12, 2) ?></td></tr>
                    <tr><td class="font-medium">Professional Tax</td><td class="text-muted">Deductions</td><td class="text-right text-txt">₹ <?= number_format($pt, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($pt * 12, 2) ?></td></tr>
                    <tr><td class="font-medium">Tax Deducted at Source (TDS)</td><td class="text-muted">Deductions</td><td class="text-right text-txt">₹ <?= number_format($tds, 2) ?></td><td class="text-right text-muted">₹ <?= number_format($tds * 12, 2) ?></td></tr>
                    <tr class="bg-surface-50 border-b-[2px] border-surface-200"><td class="font-medium">Total Deductions</td><td></td><td class="text-right font-medium text-danger-text">₹ <?= number_format($totalDed, 2) ?></td><td></td></tr>
                    
                    <tr><td class="font-medium text-[16px]">Net Salary</td><td></td><td class="text-right font-medium text-[16px] text-brand">₹ <?= number_format($net, 2) ?></td><td></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(id, btn) {
    document.getElementById('tab-attendance').classList.add('hidden');
    document.getElementById('tab-salary').classList.add('hidden');
    document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + id).classList.remove('hidden');
    btn.classList.add('active');
}
</script>

<style>
@media print {
    body { background: white; }
    nav, aside, .tab-bar, .workflow-steps, .btn-primary, .btn-secondary, footer { display: none !important; }
    main { padding: 0 !important; }
    .card { border: none !important; padding: 0 !important; }
    #tab-attendance { display: block !important; margin-bottom: 2rem; }
    #tab-salary { display: block !important; }
}
</style>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
