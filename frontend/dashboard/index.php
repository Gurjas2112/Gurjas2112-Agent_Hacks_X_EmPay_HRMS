<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$db = getDBConnection();
$userId = getUserId();
$role = getUserRole();

// Initialize stats
$presentToday = 0;
$onLeave = 0;
$pendingApprovals = 0;
$openPositions = 3; // Mocked for now

if ($role === ROLE_ADMIN || $role === ROLE_HR) {
    // Admin/HR see global stats
    $stmt = $db->query("SELECT COUNT(*) FROM attendance WHERE date = CURRENT_DATE AND check_in IS NOT NULL");
    $presentToday = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'approved' AND CURRENT_DATE BETWEEN from_date AND to_date");
    $onLeave = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
    $pendingApprovals = $stmt->fetchColumn();
} else if ($role === ROLE_EMPLOYEE) {
    // Employee sees their own stats
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND date = CURRENT_DATE AND check_in IS NOT NULL");
    $stmt->execute([$userId]);
    $presentToday = $stmt->fetchColumn(); // 1 if checked in, 0 otherwise

    $stmt = $db->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingApprovals = $stmt->fetchColumn();
}
?>

<div class="p-6">
<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <h1 class="page-title"><?= htmlspecialchars(getUserName()) ?> — <?= date('F Y') ?></h1>
</div>

<?php if ($role !== ROLE_EMPLOYEE && $role !== ROLE_PAYROLL): ?>
<!-- Welcome Message per spec 5.1 -->
<div class="card mb-6" style="background: var(--primary); color: white; border: none;">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-[18px] font-medium mb-2 text-white">Welcome to EmPay</h2>
            <p class="text-[13px] text-white/80 max-w-2xl leading-relaxed mb-4">
                Your all-in-one HR platform is ready. Start by adding your employees, setting up contracts, and running your first payroll — all in one place. Need a hand? Click the ? icon any time for contextual help.
            </p>
            <button class="bg-white text-brand px-4 py-2 rounded-md text-[13px] font-medium hover:bg-surface-50 transition-colors">
                Let's Get Started &rarr;
            </button>
        </div>
        <i data-lucide="sparkles" class="w-12 h-12 opacity-20"></i>
    </div>
</div>
<?php endif; ?>

<!-- KPI Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-6">
    <div class="stat-card">
        <p class="stat-label"><?= $role === ROLE_EMPLOYEE ? 'Checked In Today' : 'Present today' ?></p>
        <p class="stat-value stat-value-positive"><?= $presentToday ?></p>
        <p class="caption mt-1"><?= $role === ROLE_EMPLOYEE ? ($presentToday ? 'You are checked in' : 'Not checked in') : 'Live attendance count' ?></p>
    </div>
    
    <?php if ($role !== ROLE_EMPLOYEE && $role !== ROLE_PAYROLL): ?>
    <div class="stat-card">
        <p class="stat-label">On leave</p>
        <p class="stat-value stat-value-negative"><?= $onLeave ?></p>
        <p class="caption mt-1">Approved time off</p>
    </div>
    <?php endif; ?>

    <div class="stat-card">
        <p class="stat-label"><?= $role === ROLE_EMPLOYEE ? 'My Pending Leaves' : 'Pending approvals' ?></p>
        <p class="stat-value stat-value-neutral"><?= $pendingApprovals ?></p>
        <p class="caption mt-1">Leave requests awaiting action</p>
    </div>

    <?php if ($role !== ROLE_EMPLOYEE && $role !== ROLE_PAYROLL): ?>
    <div class="stat-card">
        <p class="stat-label">Open Positions</p>
        <p class="stat-value stat-value-neutral"><?= $openPositions ?></p>
        <p class="caption mt-1">Vacant roles in active recruitment</p>
    </div>
    <?php endif; ?>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    
    <!-- Recent Attendance — 2 cols -->
    <div class="xl:col-span-2 card !p-0">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-200">
            <h2 class="section-heading">Recent Attendance</h2>
            <a href="<?= BASE_URL ?>index.php?page=attendance/history" class="btn btn-ghost text-[12px]">
                View All <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Employee</th>
                <th>Date</th>
                <th>Check in</th>
                <th>Check out</th>
                <th>Status</th>
            </tr></thead>
            <tbody>
                <?php
                $attendance = [
                    ['name'=>'Arjun Mehta',   'date'=>'02 May 2026','in'=>'09:02','out'=>'18:15','status'=>'present'],
                    ['name'=>'Priya Sharma',  'date'=>'02 May 2026','in'=>'09:15','out'=>'18:00','status'=>'present'],
                    ['name'=>'Ravi Kumar',    'date'=>'02 May 2026','in'=>'10:30','out'=>'18:30','status'=>'late'],
                    ['name'=>'Sneha Patel',   'date'=>'02 May 2026','in'=>'—',    'out'=>'—',    'status'=>'absent'],
                    ['name'=>'Vikram Singh',  'date'=>'02 May 2026','in'=>'08:55','out'=>'18:00','status'=>'present'],
                ];
                foreach ($attendance as $a):
                    $badgeClass = match($a['status']) { 'present'=>'badge-present','late'=>'badge-late','absent'=>'badge-absent',default=>'badge-draft' };
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="kanban-avatar w-7 h-7 text-[10px]"><?= strtoupper(substr($a['name'],0,2)) ?></div>
                            <span class="font-medium"><?= $a['name'] ?></span>
                        </div>
                    </td>
                    <td class="text-muted"><?= $a['date'] ?></td>
                    <td class="text-muted"><?= $a['in'] ?></td>
                    <td class="text-muted"><?= $a['out'] ?></td>
                    <td><span class="badge <?= $badgeClass ?> capitalize"><?= $a['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Leave Requests — 1 col -->
    <div class="card !p-0">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface-200">
            <h2 class="section-heading">Time Off Requests</h2>
            <a href="<?= BASE_URL ?>index.php?page=leave/manage" class="btn btn-ghost text-[12px]">View All</a>
        </div>
        <div class="divide-y divide-surface-200">
            <?php
            $leaves = [
                ['name'=>'Priya Sharma','type'=>'Sick Leave','days'=>2,'status'=>'pending'],
                ['name'=>'Ravi Kumar','type'=>'Casual Leave','days'=>1,'status'=>'approved'],
                ['name'=>'Sneha Patel','type'=>'Annual Leave','days'=>5,'status'=>'pending'],
            ];
            foreach ($leaves as $l):
                $bc = match($l['status']) { 'pending'=>'badge-pending','approved'=>'badge-approved',default=>'badge-cancelled' };
                $sl = match($l['status']) { 'pending'=>'To Approve','approved'=>'Approved',default=>'Refused' };
            ?>
            <div class="flex items-center gap-3 px-6 py-3">
                <div class="kanban-avatar w-8 h-8 text-[10px]"><?= strtoupper(substr($l['name'],0,2)) ?></div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-medium truncate"><?= $l['name'] ?></p>
                    <p class="caption"><?= $l['type'] ?> · <?= $l['days'] ?> day<?= $l['days']>1?'s':'' ?></p>
                </div>
                <span class="badge <?= $bc ?>"><?= $sl ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-6">
    <h2 class="section-heading mb-4">Quick Actions</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="<?= BASE_URL ?>index.php?page=attendance/mark" class="card !p-4 flex items-center gap-3 hover:border-[var(--border-hover)] transition-colors">
            <i data-lucide="clock" class="w-5 h-5 text-brand"></i>
            <span class="text-[13px] font-medium">Mark Attendance</span>
        </a>
        <a href="<?= BASE_URL ?>index.php?page=leave/apply" class="card !p-4 flex items-center gap-3 hover:border-[var(--border-hover)] transition-colors">
            <i data-lucide="calendar-plus" class="w-5 h-5 text-brand"></i>
            <span class="text-[13px] font-medium">Apply Leave</span>
        </a>
        <a href="<?= BASE_URL ?>index.php?page=payroll/payslip" class="card !p-4 flex items-center gap-3 hover:border-[var(--border-hover)] transition-colors">
            <i data-lucide="file-text" class="w-5 h-5 text-brand"></i>
            <span class="text-[13px] font-medium">View Payslip</span>
        </a>
        <a href="<?= BASE_URL ?>index.php?page=users/form" class="card !p-4 flex items-center gap-3 hover:border-[var(--border-hover)] transition-colors">
            <i data-lucide="user-plus" class="w-5 h-5 text-brand"></i>
            <span class="text-[13px] font-medium">Create Employee</span>
        </a>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
