<?php
$pageTitle = 'Schedule';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$db = getDBConnection();
$userId = getUserId();
$role = getUserRole();

// Auto-provision demo data to ensure "Joy Kapoor" and others exist
require_once __DIR__ . '/../../utils/demo_provisioner.php';
provisionDemoData();

// Admin/HR can see all schedules, Employee sees only theirs
$sql = "SELECT s.*, u.full_name as emp_name, u.designation, d.name as dept_name 
        FROM schedules s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id ";
$params = [];
if ($role === ROLE_EMPLOYEE) {
    $sql .= " WHERE s.user_id = ? ";
    $params[] = $userId;
}
$sql .= " ORDER BY s.shift_date ASC, s.start_time ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$canAssign = canManageUsers(); // Only Admin/HR can manage schedules
$employees = [];
if ($canAssign) {
    $employees = $db->query("SELECT id, full_name as name, designation FROM users WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll();
}
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Schedules</h1>
    <?php if ($canAssign): ?>
    <button class="btn btn-primary" onclick="document.getElementById('assign-schedule-modal').classList.remove('hidden')">
        <i data-lucide="plus" class="w-4 h-4"></i> Assign Schedule 
    </button>
    <?php endif; ?>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="mb-4 p-4 bg-success/10 border border-success/20 text-success rounded-lg flex items-center gap-2">
    <i data-lucide="check-circle" class="w-5 h-5"></i>
    <span>Schedule assigned and email notification sent successfully!</span>
</div>
<?php endif; ?>

<div class="card !p-0">
    <table class="data-table">
        <thead><tr>
            <th>Date</th><th>Employee</th><th>Dept / Designation</th><th>Shift Time</th><th>Notes</th>
            <?php if ($canAssign): ?><th class="text-right">Actions</th><?php endif; ?>
        </tr></thead>
        <tbody>
            <?php if (count($schedules) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-8">No schedules found.</td></tr>
            <?php endif; ?>
            <?php foreach ($schedules as $s): ?>
            <tr>
                <td class="font-medium text-brand"><?= date('D, d M Y', strtotime($s['shift_date'])) ?></td>
                <td>
                    <div class="flex flex-col">
                        <span class="font-medium text-txt"><?= htmlspecialchars($s['emp_name']) ?></span>
                    </div>
                </td>
                <td>
                    <div class="flex flex-col text-xs">
                        <span class="text-txt/80"><?= htmlspecialchars($s['dept_name'] ?? 'N/A') ?></span>
                        <span class="text-muted"><?= htmlspecialchars($s['designation'] ?? 'N/A') ?></span>
                    </div>
                </td>
                <td><span class="badge badge-draft"><?= date('H:i', strtotime($s['start_time'])) ?> - <?= date('H:i', strtotime($s['end_time'])) ?></span></td>
                <td class="text-muted max-w-xs truncate" title="<?= htmlspecialchars($s['notes']) ?>"><?= htmlspecialchars($s['notes']) ?></td>
                <?php if ($canAssign): ?>
                <td class="text-right">
                    <form action="<?= BASE_URL ?>../backend/schedule/delete.php" method="POST" class="inline" onsubmit="return confirm('Delete this schedule?');">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-ghost !p-1.5 text-danger-text hover:bg-danger/10" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($canAssign): ?>
<!-- Assign Schedule Modal -->
<div id="assign-schedule-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-surface-200 flex items-center justify-between">
            <h2 class="text-[16px] font-medium text-txt">Assign Schedule</h2>
            <button onclick="document.getElementById('assign-schedule-modal').classList.add('hidden')" class="text-muted hover:text-txt"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6">
            <form action="<?= BASE_URL ?>../backend/schedule/assign.php" method="POST" class="space-y-4">
                <div>
                    <label class="form-label block">Employee</label>
                    <select name="user_id" required class="form-input">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['designation'] ?? 'N/A') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label block">Shift Date</label>
                    <input type="date" name="shift_date" required class="form-input">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label block">Start Time</label>
                        <input type="time" name="start_time" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label block">End Time</label>
                        <input type="time" name="end_time" required class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label block">Notes</label>
                    <input type="text" name="notes" class="form-input" placeholder="e.g. Morning Shift">
                </div>
                <div class="pt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('assign-schedule-modal').classList.add('hidden')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
