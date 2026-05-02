<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL);
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="page-title">Reports</h1>
    <div class="flex items-center gap-3">
        <input type="month" value="<?= date('Y-m') ?>" class="form-input !w-auto">
        <button class="btn btn-secondary"><i data-lucide="download" class="w-4 h-4"></i> Export</button>
    </div>
</div>

<!-- Empty State -->
<div class="empty-state">
    <i data-lucide="bar-chart-3" class="empty-state-icon"></i>
    <p class="empty-state-title">No Data Available</p>
    <p class="empty-state-text">Reports will populate as your team becomes active. Try changing the date filter or check back after the next payroll run.</p>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
