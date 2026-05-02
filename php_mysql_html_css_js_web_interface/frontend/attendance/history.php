<?php
$pageTitle = 'Attendance Log';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();
$userId = getUserId();
$role = getUserRole();

// Filter by month or user
$filterMonth = $_GET['month'] ?? date('Y-m');
$queryUserId = ($role === ROLE_ADMIN || $role === ROLE_HR || $role === ROLE_PAYROLL) && isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;

$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND date LIKE ? ORDER BY date DESC");
$stmt->execute([$queryUserId, $filterMonth . '%']);
$history = $stmt->fetchAll();

// Calculate Stats
$present = 0; $late = 0; $absent = 0; $totalMinutes = 0;
foreach ($history as $h) {
    if ($h['status'] === 'present') $present++;
    if ($h['status'] === 'late') $late++;
    if ($h['status'] === 'absent') $absent++;
    if ($h['work_hours']) $totalMinutes += ($h['work_hours'] * 60);
}
$avgMinutes = count($history) > 0 ? floor($totalMinutes / count($history)) : 0;
$avgHoursStr = floor($avgMinutes / 60) . 'h ' . ($avgMinutes % 60) . 'm';

?>

<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Attendance Log</h1>
    <form method="GET" class="flex items-center gap-2">
        <input type="hidden" name="page" value="attendance/history">
        <?php if ($role === ROLE_ADMIN || $role === ROLE_HR): ?>
            <!-- Optional: Select user dropdown here in real app -->
        <?php endif; ?>
        <input type="month" name="month" value="<?= htmlspecialchars($filterMonth) ?>" class="form-input !w-auto" onchange="this.form.submit()">
    </form>
</div>

<!-- Summary Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-6">
    <div class="stat-card"><p class="stat-label">Present</p><p class="stat-value stat-value-positive"><?= $present ?></p></div>
    <div class="stat-card"><p class="stat-label">Late</p><p class="stat-value stat-value-neutral"><?= $late ?></p></div>
    <div class="stat-card"><p class="stat-label">Absent</p><p class="stat-value stat-value-negative"><?= $absent ?></p></div>
    <div class="stat-card"><p class="stat-label">Avg hours</p><p class="stat-value stat-value-neutral"><?= $avgHoursStr ?></p></div>
</div>

<!-- Table -->
<div class="card !p-0">
    <table class="data-table">
        <thead><tr>
            <th>Date</th><th>Check in</th><th>Check out</th><th>Working hours</th><th>Status</th>
        </tr></thead>
        <tbody>
            <?php if (count($history) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">No attendance records found for this period.</td></tr>
            <?php endif; ?>
            <?php foreach ($history as $h):
                $bc = match($h['status']) { 'present'=>'badge-present','late'=>'badge-late',default=>'badge-absent' };
                $inTime = $h['check_in'] ? date('H:i', strtotime($h['check_in'])) : '—';
                $outTime = $h['check_out'] ? date('H:i', strtotime($h['check_out'])) : '—';
                $workHours = $h['work_hours'] ? floor($h['work_hours']) . 'h ' . round(($h['work_hours'] - floor($h['work_hours'])) * 60) . 'm' : '—';
            ?>
            <tr>
                <td class="font-medium"><?= date('d M Y', strtotime($h['date'])) ?></td>
                <td class="text-muted"><?= $inTime ?></td>
                <td class="text-muted"><?= $outTime ?></td>
                <td class="text-muted"><?= $workHours ?></td>
                <td><span class="badge <?= $bc ?> capitalize"><?= htmlspecialchars($h['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
