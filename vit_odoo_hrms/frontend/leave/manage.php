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

$sql = "SELECT l.*, u.full_name as name, t.name as type 
        FROM leaves l 
        JOIN users u ON l.user_id = u.id 
        JOIN leave_types t ON l.leave_type_id = t.id 
        ORDER BY l.created_at DESC";

$leaves = $db->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Time Off</h1>
    <a href="<?= BASE_URL ?>index.php?page=leave/apply" class="btn btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> New Request
    </a>
</div>

<!-- Tab Bar per spec 5.5 -->
<div class="tab-bar mb-4">
    <button class="tab-item active">All</button>
    <button class="tab-item">To Approve</button>
    <button class="tab-item">Approved</button>
    <button class="tab-item">Refused</button>
</div>

<div class="card !p-0">
    <table class="data-table">
        <thead><tr>
            <th>Employee</th><th>Type</th><th>Duration</th><th>Reason</th><th>Status</th>
            <?php if (canManageLeaves()): ?><th class="text-right">Actions</th><?php endif; ?>
        </tr></thead>
        <tbody>
            <?php foreach ($leaves as $l):
                $bc = match($l['status']) { 'pending'=>'badge-pending','approved'=>'badge-approved',default=>'badge-cancelled' };
                $sl = match($l['status']) { 'pending'=>'To Approve','approved'=>'Approved',default=>'Refused' };
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

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
