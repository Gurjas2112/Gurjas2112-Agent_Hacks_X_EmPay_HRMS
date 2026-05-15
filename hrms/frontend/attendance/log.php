<?php
$pageTitle = 'Overall Attendance Log';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

// Only Admin, HR, and Payroll can see overall logs
requireRole(ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL);

require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();

// Filters
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterDept = $_GET['dept_id'] ?? 'all';

$sql = "
    SELECT a.*, u.full_name as emp_name, d.name as dept_name
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE a.date = ?
";
$params = [$filterDate];

if ($filterDept !== 'all') {
    $sql .= " AND u.department_id = ?";
    $params[] = (int)$filterDept;
}

$sql .= " ORDER BY a.check_in DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$departments = $db->query("SELECT id, name FROM departments")->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="page-title">Overall Attendance Log</h1>
    <form method="GET" class="flex items-center gap-3">
        <input type="hidden" name="page" value="attendance/log">
        
        <div class="flex items-center gap-2">
            <span class="text-[12px] text-muted">Date:</span>
            <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="form-input !w-auto" onchange="this.form.submit()">
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[12px] text-muted">Dept:</span>
            <select name="dept_id" class="form-input !w-auto" onchange="this.form.submit()">
                <option value="all">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= (int)$filterDept === (int)$dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card !p-4">
        <p class="caption mb-1">Total Logs for Day</p>
        <p class="text-2xl font-bold"><?= count($logs) ?></p>
    </div>
    <div class="card !p-4">
        <p class="caption mb-1">Late Arrivals</p>
        <p class="text-2xl font-bold text-warning">
            <?= count(array_filter($logs, fn($l) => $l['status'] === 'late')) ?>
        </p>
    </div>
    <div class="card !p-4">
        <p class="caption mb-1">On-Time</p>
        <p class="text-2xl font-bold text-success">
            <?= count(array_filter($logs, fn($l) => $l['status'] === 'present')) ?>
        </p>
    </div>
</div>

<div class="card !p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Work Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-12 text-muted">
                        No records found for <?= date('d M Y', strtotime($filterDate)) ?>.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): 
                    $statusClass = match($log['status']) {
                        'present' => 'badge-present',
                        'late' => 'badge-late',
                        'absent' => 'badge-absent',
                        default => 'badge-draft'
                    };
                    $workHours = $log['work_hours'] ? number_format($log['work_hours'], 2) . ' hrs' : '—';
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="kanban-avatar w-7 h-7 text-[10px]"><?= strtoupper(substr($log['emp_name'],0,2)) ?></div>
                            <span class="font-medium"><?= htmlspecialchars($log['emp_name']) ?></span>
                        </div>
                    </td>
                    <td class="text-muted text-[13px]"><?= htmlspecialchars($log['dept_name']) ?></td>
                    <td class="text-[13px]"><?= $log['check_in'] ? date('h:i A', strtotime($log['check_in'])) : '—' ?></td>
                    <td class="text-[13px]"><?= $log['check_out'] ? date('h:i A', strtotime($log['check_out'])) : '—' ?></td>
                    <td><span class="badge <?= $statusClass ?> capitalize"><?= htmlspecialchars($log['status']) ?></span></td>
                    <td class="text-muted text-[13px]"><?= $workHours ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
