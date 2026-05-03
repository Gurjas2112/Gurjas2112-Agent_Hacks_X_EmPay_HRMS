<?php
$pageTitle = 'Manage Time Off';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();

$currentStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$sql = "SELECT l.*, u.full_name as name, t.name as type 
        FROM leaves l 
        JOIN users u ON l.user_id = u.id 
        JOIN leave_types t ON l.leave_type_id = t.id";

$where = [];
$params = [];

if ($currentStatus === 'pending') {
    $where[] = "l.status = 'pending'";
} elseif ($currentStatus === 'approved') {
    $where[] = "l.status = 'approved'";
} elseif ($currentStatus === 'refused') {
    $where[] = "l.status = 'rejected'";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY (CASE WHEN l.status = 'pending' THEN 0 ELSE 1 END), l.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Time Off</h1>
    <div class="flex items-center gap-2">
        <?php if (canManageLeaves()): ?>
        <a href="<?= BASE_URL ?>../backend/reports/email_demo_report.php" class="btn btn-secondary">
            <i data-lucide="mail" class="w-4 h-4 mr-1"></i> Email Leave Report
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>index.php?page=leave/apply" class="btn btn-primary">
            <i data-lucide="plus" class="w-4 h-4 mr-1"></i> New Request
        </a>
    </div>
</div>

<!-- Tab Bar per spec 5.5 -->
<div class="tab-bar mb-4">
    <a href="<?= BASE_URL ?>index.php?page=leave/manage&status=all" class="tab-item <?= $currentStatus === 'all' ? 'active' : '' ?>">All</a>
    <a href="<?= BASE_URL ?>index.php?page=leave/manage&status=pending" class="tab-item <?= $currentStatus === 'pending' ? 'active' : '' ?>">To Approve</a>
    <a href="<?= BASE_URL ?>index.php?page=leave/manage&status=approved" class="tab-item <?= $currentStatus === 'approved' ? 'active' : '' ?>">Approved</a>
    <a href="<?= BASE_URL ?>index.php?page=leave/manage&status=refused" class="tab-item <?= $currentStatus === 'refused' ? 'active' : '' ?>">Refused</a>
</div>

<div class="card !p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="data-table">
        <thead><tr>
            <th>Employee</th><th>Type</th><th>Duration</th><th>Reason</th><th>Status</th>
            <?php if (canManageLeaves()): ?><th class="text-right">Actions</th><?php endif; ?>
        </tr></thead>
        <tbody>
            <?php foreach ($leaves as $l):
                $status = $l['status'];
                $bcMap = ['pending'=>'badge-pending', 'approved'=>'badge-approved', 'rejected'=>'badge-refused'];
                $slMap = ['pending'=>'To Approve', 'approved'=>'Approved', 'rejected'=>'Refused'];
                
                $bc = isset($bcMap[$status]) ? $bcMap[$status] : 'badge-cancelled';
                $sl = isset($slMap[$status]) ? $slMap[$status] : 'Refused';
            ?>
            <tr>
                <td>
                    <div class="flex items-center gap-2">
                        <div class="kanban-avatar w-7 h-7 text-[10px]"><?= strtoupper(substr($l['name'],0,2)) ?></div>
                        <span class="font-medium"><?= $l['name'] ?></span>
                    </div>
                </td>
                <td class="text-muted"><?= htmlspecialchars($l['type']) ?></td>
                <td>
                    <span class="text-[13px]"><?= date('d M Y', strtotime($l['from_date'])) ?> – <?= date('d M Y', strtotime($l['to_date'])) ?></span><br>
                    <span class="caption"><?= floor($l['days']) ?> working day<?= $l['days'] > 1 ? 's' : '' ?></span>
                </td>
                <td class="text-muted max-w-[200px] truncate"><?= htmlspecialchars($l['reason']) ?></td>
                <td><span class="badge <?= $bc ?>"><?= $sl ?></span></td>
                <?php if (canManageLeaves()): ?>
                <td class="text-right">
                    <?php if ($l['status'] === 'pending'): ?>
                    <div class="flex items-center justify-end gap-1">
                        <form action="<?= BASE_URL ?>../backend/leave/approve_leave.php" method="POST" class="inline">
                            <input type="hidden" name="leave_id" value="<?= $l['id'] ?>"><input type="hidden" name="action" value="approve">
                            <button class="btn btn-ghost !p-1.5 text-success-text" title="Approve" aria-label="Approve leave"><i data-lucide="check" class="w-4 h-4"></i></button>
                        </form>
                        <form action="<?= BASE_URL ?>../backend/leave/approve_leave.php" method="POST" class="inline">
                            <input type="hidden" name="leave_id" value="<?= $l['id'] ?>"><input type="hidden" name="action" value="reject">
                            <button class="btn btn-ghost !p-1.5 text-danger-text" title="Refuse" aria-label="Refuse leave"><i data-lucide="x" class="w-4 h-4"></i></button>
                        </form>
                    </div>
                    <?php else: ?>
                    <span class="caption">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
