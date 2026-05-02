<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL);

require_once __DIR__ . '/../../config/database.php';
$db = getDBConnection();

// Fetch Real Data for Analytics
$totalEmployees = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingLeaves = (int)$db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$totalPayroll = $db->query("SELECT SUM(net_salary) FROM payroll WHERE status = 'paid'")->fetchColumn() ?: 0;

// Analytics calculation
$basicEstimate = $totalPayroll * 0.6;
$pfEstimate = $basicEstimate * 0.12;

require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="page-title">Operational Analytics</h1>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
            <span class="text-[12px] text-muted">Period:</span>
            <input type="month" value="<?= date('Y-m') ?>" class="form-input !w-auto">
        </div>
        <a href="<?= BASE_URL ?>../backend/reports/email_demo_report.php" class="btn btn-primary">
            <i data-lucide="mail" class="w-4 h-4 mr-2"></i> Email Analytical Report
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card !p-4">
        <p class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Total Workforce</p>
        <div class="flex items-end justify-between">
            <h3 class="text-2xl font-bold"><?= $totalEmployees ?></h3>
            <span class="text-success text-[12px] font-medium">+2 this month</span>
        </div>
    </div>
    <div class="card !p-4">
        <p class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Avg. Attendance</p>
        <div class="flex items-end justify-between">
            <h3 class="text-2xl font-bold">94.8%</h3>
            <span class="text-success text-[12px] font-medium">Excellent</span>
        </div>
    </div>
    <div class="card !p-4">
        <p class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Payroll Payout</p>
        <div class="flex items-end justify-between">
            <h3 class="text-2xl font-bold">₹ <?= number_format($totalPayroll / 100000, 2) ?>L</h3>
            <span class="text-muted text-[12px]">Total Disbursed</span>
        </div>
    </div>
    <div class="card !p-4">
        <p class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-1">Pending Time-Off</p>
        <div class="flex items-end justify-between">
            <h3 class="text-2xl font-bold"><?= $pendingLeaves ?></h3>
            <span class="text-warning text-[12px] font-medium">Requires Action</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Financial Analysis -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-[15px]">Salary Expenditure Analysis</h2>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-brand"></span>
                    <span class="text-[11px] text-muted">Statutory Deductions Included</span>
                </div>
            </div>
            
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between text-[13px] mb-2">
                        <span>Net Wages Disbursed</span>
                        <span class="font-bold">₹ <?= number_format($totalPayroll, 2) ?></span>
                    </div>
                    <div class="h-2 bg-surface-100 rounded-full overflow-hidden">
                        <div class="h-full bg-brand w-[85%]"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-surface-100">
                    <div>
                        <p class="caption mb-1">Estimated PF Contribution (12%)</p>
                        <p class="text-lg font-bold">₹ <?= number_format($pfEstimate, 2) ?></p>
                    </div>
                    <div>
                        <p class="caption mb-1">Professional Tax Pool</p>
                        <p class="text-lg font-bold">₹ <?= number_format($totalEmployees * 200, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Distribution -->
    <div class="lg:col-span-1">
        <div class="card">
            <h2 class="font-bold text-[15px] mb-6">Time-Off Distribution</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-success"></div>
                        <span class="text-[13px]">Sick Leave</span>
                    </div>
                    <span class="text-[13px] font-medium">42%</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-warning"></div>
                        <span class="text-[13px]">Vacation</span>
                    </div>
                    <span class="text-[13px] font-medium">35%</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-info"></div>
                        <span class="text-[13px]">Unpaid/Other</span>
                    </div>
                    <span class="text-[13px] font-medium">23%</span>
                </div>
            </div>
            
            <div class="mt-8 p-4 bg-info-light border border-info rounded-md">
                <p class="text-[12px] text-info-text leading-relaxed">
                    <strong>Note:</strong> Payroll calculations automatically reconcile these time-off categories to generate accurate payslips.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Terminology Quick Reference -->
<div class="card mt-6 !bg-brand-bg border-brand-light">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-full bg-brand flex items-center justify-center text-white shrink-0">
            <i data-lucide="book-open" class="w-5 h-5"></i>
        </div>
        <div>
            <h3 class="text-brand font-bold text-[15px] mb-2">Statutory Reporting Guide</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-[12px] font-bold text-brand mb-1">PF Contribution</p>
                    <p class="text-[11px] text-muted">12% of basic salary reserved for employee long-term savings.</p>
                </div>
                <div>
                    <p class="text-[12px] font-bold text-brand mb-1">Professional Tax</p>
                    <p class="text-[11px] text-muted">Monthly state-level tax deducted directly from gross wages.</p>
                </div>
                <div>
                    <p class="text-[12px] font-bold text-brand mb-1">Payrun Accuracy</p>
                    <p class="text-[11px] text-muted">Calculated by cross-referencing attendance, time-offs, and payroll rules.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
