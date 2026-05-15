<?php
/**
 * EmPay HRMS - Management Dashboard
 * Features KPI stats, recent attendance, and leave management.
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../config/policies.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$db = getDBConnection();
$userId = getUserId();
$role = getUserRole();

$totalEmployees = 0;

if ($role === ROLE_ADMIN || $role === ROLE_HR || $role === ROLE_PAYROLL) {
    // Global stats
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $totalEmployees = $stmt->fetchColumn();
    $stmt = $db->query("SELECT COUNT(*) FROM attendance WHERE date = CURRENT_DATE AND check_in IS NOT NULL");
    $presentToday = $stmt->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'approved' AND CURRENT_DATE BETWEEN from_date AND to_date");
    $onLeave = $stmt->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'");
    $pendingApprovals = $stmt->fetchColumn() ?: 0;

    // Fetch real recent attendance
    $stmt = $db->query("SELECT a.*, u.full_name as name FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.date DESC, a.check_in DESC LIMIT 5");
    $recentAttendance = $stmt->fetchAll();

    // Fetch real pending leaves
    $stmt = $db->query("SELECT l.*, u.full_name as name, t.name as type FROM leaves l JOIN users u ON l.user_id = u.id JOIN leave_types t ON l.leave_type_id = t.id WHERE l.status = 'pending' ORDER BY l.created_at DESC LIMIT 5");
    $recentLeaves = $stmt->fetchAll();
} else if ($role === ROLE_EMPLOYEE) {
    // Employee stats
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND date = CURRENT_DATE AND check_in IS NOT NULL");
    $stmt->execute([$userId]);
    $presentToday = $stmt->fetchColumn() ?: 0;

    $stmt = $db->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingApprovals = $stmt->fetchColumn() ?: 0;

    // Fetch my recent attendance
    $stmt = $db->prepare("SELECT a.*, u.full_name as name FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.user_id = ? ORDER BY a.date DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentAttendance = $stmt->fetchAll();

    // Fetch my recent leaves
    $stmt = $db->prepare("SELECT l.*, u.full_name as name, t.name as type FROM leaves l JOIN users u ON l.user_id = u.id JOIN leave_types t ON l.leave_type_id = t.id WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentLeaves = $stmt->fetchAll();
}
?>

<div class="p-6">
    <!-- Action Bar -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="page-title"><?= htmlspecialchars(getUserName()) ?> — <?= date('F Y') ?></h1>
    </div>

    <?php if ($role !== ROLE_EMPLOYEE && $role !== ROLE_PAYROLL): ?>
    <!-- Welcome Message per spec 5.1 -->
    <div class="card mb-6" style="background: var(--primary); color: white; border: none; padding: 24px;">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-[18px] font-medium mb-2 text-white">Welcome to EmPay</h2>
                <p class="text-[13px] text-white/80 max-w-2xl leading-relaxed mb-4">
                    Your all-in-one HR platform is ready. Start by adding your employees, setting up contracts, and running your first payroll — all in one place. Need a hand? Click the ? icon any time for contextual help.
                </p>
                <a href="<?= BASE_URL ?>index.php?page=users/form" class="inline-flex items-center bg-white text-primary px-4 py-2 rounded-md text-[13px] font-medium hover:bg-surface-50 transition-colors">
                    Let's Get Started &rarr;
                </a>
            </div>
            <i data-lucide="sparkles" class="w-12 h-12 opacity-20"></i>
        </div>
    </div>
    <?php endif; ?>

    <!-- KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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

        <?php if ($role === ROLE_ADMIN || $role === ROLE_HR): ?>
        <div class="stat-card">
            <p class="stat-label">Total Employees</p>
            <p class="stat-value stat-value-neutral"><?= $totalEmployees ?></p>
            <p class="caption mt-1">Active staff members</p>
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
            <div class="table-responsive">
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
                        if (empty($recentAttendance)):
                        ?>
                        <tr><td colspan="5" class="text-center text-muted py-6">No recent records.</td></tr>
                        <?php
                        else:
                        foreach ($recentAttendance as $a):
                            $badgeClass = match($a['status']) { 'present'=>'badge-present','late'=>'badge-late','absent'=>'badge-absent',default=>'badge-draft' };
                            $checkIn = $a['check_in'] ? date('H:i', strtotime($a['check_in'])) : '—';
                            $checkOut = $a['check_out'] ? date('H:i', strtotime($a['check_out'])) : '—';
                        ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="kanban-avatar w-7 h-7 text-[10px]"><?= strtoupper(substr($a['name'] ?? '?',0,2)) ?></div>
                                    <span class="font-medium"><?= htmlspecialchars($a['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?= date('d M Y', strtotime($a['date'])) ?></td>
                            <td class="text-muted"><?= $checkIn ?></td>
                            <td class="text-muted"><?= $checkOut ?></td>
                            <td><span class="badge <?= $badgeClass ?> capitalize"><?= htmlspecialchars($a['status']) ?></span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leave Requests — 1 col -->
        <div class="card !p-0">
            <div class="flex items-center justify-between px-6 py-4 border-b border-surface-200">
                <h2 class="section-heading">Time Off Requests</h2>
                <a href="<?= BASE_URL ?>index.php?page=leave/manage" class="btn btn-ghost text-[12px]">View All</a>
            </div>
            <div class="divide-y divide-surface-200">
                <?php
                if (empty($recentLeaves)):
                ?>
                <div class="px-6 py-8 text-center text-muted">No pending requests.</div>
                <?php
                else:
                foreach ($recentLeaves as $l):
                    $bc = match($l['status']) { 'pending'=>'badge-pending','approved'=>'badge-approved',default=>'badge-cancelled' };
                    $sl = match($l['status']) { 
                        'pending' => ($role === ROLE_EMPLOYEE ? 'Pending' : 'To Approve'),
                        'approved' => 'Approved',
                        default => 'Refused' 
                    };
                ?>
                <div class="flex items-center gap-3 px-6 py-3">
                    <div class="kanban-avatar w-8 h-8 text-[10px]"><?= strtoupper(substr($l['name'],0,2)) ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-medium truncate"><?= htmlspecialchars($l['name']) ?></p>
                        <p class="caption"><?= htmlspecialchars($l['type']) ?> · <?= floor($l['days']) ?> day<?= $l['days']>1?'s':'' ?></p>
                    </div>
                    <span class="badge <?= $bc ?>"><?= $sl ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6">
        <h2 class="section-heading mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <a href="<?= BASE_URL ?>index.php?page=attendance/mark" class="card !p-4 flex items-center gap-3 hover:border-primary transition-colors cursor-pointer group">
                <div class="p-2 rounded bg-primary/10 group-hover:bg-primary/20 transition-colors">
                    <i data-lucide="clock" class="w-5 h-5 text-primary"></i>
                </div>
                <span class="text-[13px] font-medium">Mark Attendance</span>
            </a>
            <a href="<?= BASE_URL ?>index.php?page=leave/apply" class="card !p-4 flex items-center gap-3 hover:border-primary transition-colors cursor-pointer group">
                <div class="p-2 rounded bg-primary/10 group-hover:bg-primary/20 transition-colors">
                    <i data-lucide="calendar-plus" class="w-5 h-5 text-primary"></i>
                </div>
                <span class="text-[13px] font-medium">Apply Leave</span>
            </a>
            <a href="<?= BASE_URL ?>index.php?page=payroll/my_payslips" class="card !p-4 flex items-center gap-3 hover:border-primary transition-colors cursor-pointer group">
                <div class="p-2 rounded bg-primary/10 group-hover:bg-primary/20 transition-colors">
                    <i data-lucide="file-text" class="w-5 h-5 text-primary"></i>
                </div>
                <span class="text-[13px] font-medium">My Payslips</span>
            </a>
            <a href="<?= BASE_URL ?>index.php?page=users" class="card !p-4 flex items-center gap-3 hover:border-primary transition-colors cursor-pointer group">
                <div class="p-2 rounded bg-primary/10 group-hover:bg-primary/20 transition-colors">
                    <i data-lucide="users" class="w-5 h-5 text-primary"></i>
                </div>
                <span class="text-[13px] font-medium">Directory</span>
            </a>
        </div>
    </div>

    <?php if ($role === ROLE_ADMIN): 
        // Fetch Email Metrics
        $emailStats = $db->query("SELECT 
            COUNT(*) as total, 
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM email_logs")->fetch();
        
        $totalMail = $emailStats['total'] ?? 0;
        $successRate = $totalMail > 0 ? round(($emailStats['success'] / $totalMail) * 100) : 0;
        
        $recentMailLogs = $db->query("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 5")->fetchAll();
    ?>
    <!-- Email Metrics Section (Admin Only) -->
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="section-heading">System Communication Metrics</h2>
            <div class="flex items-center gap-4 text-[12px]">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-success"></span> Sent: <?= $emailStats['success'] ?></span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-danger"></span> Failed: <?= $emailStats['failed'] ?></span>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Stats Card -->
            <div class="card flex flex-col justify-center items-center py-8">
                <div class="relative w-24 h-24 flex items-center justify-center mb-4">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-100" />
                        <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-primary" stroke-dasharray="<?= (2 * pi() * 40) ?>" stroke-dashoffset="<?= (2 * pi() * 40) * (1 - $successRate/100) ?>" />
                    </svg>
                    <span class="absolute text-[18px] font-bold text-primary"><?= $successRate ?>%</span>
                </div>
                <p class="text-[13px] font-medium text-text">Delivery Success Rate</p>
                <p class="caption"><?= $totalMail ?> Total Emails Processed</p>
            </div>

            <!-- Logs Table -->
            <div class="lg:col-span-2 card !p-0">
                <div class="px-6 py-4 border-b border-surface-200">
                    <p class="text-[13px] font-medium">Recent Communication Logs</p>
                </div>
                <table class="data-table">
                    <thead><tr>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th class="text-right">Time</th>
                    </tr></thead>
                    <tbody>
                        <?php if (empty($recentMailLogs)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No emails sent yet.</td></tr>
                        <?php else: foreach ($recentMailLogs as $log): ?>
                            <tr>
                                <td class="text-[12px]"><?= htmlspecialchars($log['recipient_email']) ?></td>
                                <td class="text-[12px] truncate max-w-[150px]"><?= htmlspecialchars($log['subject']) ?></td>
                                <td>
                                    <span class="badge <?= $log['status'] === 'sent' ? 'badge-approved' : 'badge-cancelled' ?> !text-[10px]">
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </td>
                                <td class="text-right text-muted text-[11px]"><?= date('H:i', strtotime($log['sent_at'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
