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
$leaveTypes = $db->query("SELECT * FROM leave_types")->fetchAll();

// Mock balance for now (needs proper calculation logic)
// In a real app, calculate days taken per leave_type and subtract from max_days
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="page-title">Request Time Off</h1>
        <a href="<?= BASE_URL ?>index.php?page=leave/manage" class="btn btn-secondary">Back</a>
    </div>

    <!-- Leave Balance -->
    <div class="grid grid-cols-3 gap-2 mb-6">
        <div class="stat-card"><p class="stat-label">Casual leave</p><p class="stat-value stat-value-neutral">8 <span class="text-[13px] font-normal text-muted">/ 12</span></p></div>
        <div class="stat-card"><p class="stat-label">Sick leave</p><p class="stat-value stat-value-neutral">5 <span class="text-[13px] font-normal text-muted">/ 7</span></p></div>
        <div class="stat-card"><p class="stat-label">Annual leave</p><p class="stat-value stat-value-neutral">10 <span class="text-[13px] font-normal text-muted">/ 15</span></p></div>
    </div>

    <div class="card">
        <p class="caption mb-4">Fields marked * are required</p>
        <form action="<?= BASE_URL ?>../backend/leave/apply_leave.php" method="POST" class="space-y-4">
            <input type="hidden" name="user_id" value="<?= getUserId() ?>">
            <div>
                <label class="form-label block">Leave type *</label>
                <select name="leave_type_id" required class="form-input">
                    <option value="">Select leave type</option>
                    <?php foreach ($leaveTypes as $lt): ?>
                    <option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['name']) ?> (Max <?= $lt['max_days'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
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
