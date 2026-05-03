<?php
$pageTitle = 'Apply Leave';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';
require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();
$userId = getUserId();

// Fetch leave types
// Fetch leave balances and types for the user
$balances = $db->prepare("
    SELECT lt.id, lt.name, lt.max_days, 
           COALESCE(SUM(CASE WHEN l.status = 'approved' THEN l.days ELSE 0 END), 0) as taken
    FROM leave_types lt
    LEFT JOIN leaves l ON lt.id = l.leave_type_id AND l.user_id = ?
    GROUP BY lt.id
");
$balances->execute([$userId]);
$leaveStats = $balances->fetchAll();
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="page-title">Request Time Off</h1>
        <a href="<?= BASE_URL ?>index.php?page=leave/manage" class="btn btn-secondary">Back</a>
    </div>

    <!-- Leave Balance -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 mb-6">
        <?php foreach ($leaveStats as $stat): 
            $remaining = $stat['max_days'] - $stat['taken'];
        ?>
        <div class="stat-card">
            <p class="stat-label"><?= htmlspecialchars($stat['name']) ?></p>
            <p class="stat-value stat-value-neutral">
                <?= floor($remaining) ?> 
                <span class="text-[13px] font-normal text-muted">/ <?= floor($stat['max_days']) ?></span>
            </p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <p class="caption mb-4">Fields marked * are required</p>
        <form action="<?= BASE_URL ?>../backend/leave/apply_leave.php" method="POST" class="space-y-4">
            <input type="hidden" name="user_id" value="<?= getUserId() ?>">
            <div>
                <label class="form-label block">Leave type *</label>
                <select name="leave_type_id" required class="form-input">
                    <option value="">Select leave type</option>
                    <?php foreach ($leaveStats as $lt): ?>
                    <option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['name']) ?> (Max <?= floor($lt['max_days']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="form-label block">Period start *</label>
                    <input type="date" name="from_date" required class="form-input" placeholder="DD/MM/YYYY">
                </div>
                <div>
                    <label class="form-label block">Period end *</label>
                    <input type="date" name="to_date" required class="form-input" placeholder="DD/MM/YYYY">
                </div>
            </div>
            <div>
                <label class="form-label block">Reason *</label>
                <textarea name="reason" rows="3" required placeholder="Please describe your reason for leave…" class="form-input"></textarea>
            </div>
            <div class="flex items-center gap-2 pt-3 border-t border-surface-200">
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="<?= BASE_URL ?>index.php?page=leave/manage" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
