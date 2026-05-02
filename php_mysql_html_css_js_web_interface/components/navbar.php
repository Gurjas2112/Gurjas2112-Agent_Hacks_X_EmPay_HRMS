<?php
/**
 * EmPay HRMS — Top Navigation Bar
 * Purple #714B67 bar per Odoo design system (40px height)
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$breadcrumbs = explode('/', $currentPage);

// Nav links per design system
$navLinks = [
    ['label' => 'Dashboard',    'page' => 'dashboard',       'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Employees',    'page' => 'users',           'roles' => [ROLE_ADMIN, ROLE_HR]],
    ['label' => 'Attendance',   'page' => 'attendance/mark',  'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR]],
    ['label' => 'Time Off',     'page' => 'leave/apply',      'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR]],
    ['label' => 'Schedule',     'page' => 'schedule/index',   'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR]],
    ['label' => 'Payroll',      'page' => 'payroll',          'roles' => [ROLE_ADMIN, ROLE_PAYROLL]],
    ['label' => 'My Payslips',  'page' => 'payroll/my_payslips', 'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Reports',      'page' => 'reports',          'roles' => [ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL]],
];
$userRole = getUserRole();
?>

<!-- Top Navigation — 40px, #714B67 -->
<nav class="h-10 bg-brand flex items-center justify-between px-4 text-white text-[13px]">
    <!-- Left: Logo + Nav Links -->
    <div class="flex items-center gap-1">
        <a href="<?= BASE_URL ?>index.php?page=dashboard" class="flex items-center gap-2 mr-4 font-medium">
            <i data-lucide="hexagon" class="w-4 h-4"></i>
            <span>EmPay</span>
        </a>
        <?php foreach ($navLinks as $nav):
            if (!in_array($userRole, $nav['roles'])) continue;
            $isActive = str_starts_with($currentPage, explode('/', $nav['page'])[0]);
        ?>
        <a href="<?= BASE_URL ?>index.php?page=<?= $nav['page'] ?>" 
           class="px-3 py-1 rounded <?= $isActive ? 'text-white font-medium' : 'text-white/75 hover:text-white' ?> transition-colors text-[12px]">
            <?= $nav['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Right: Notifications + Avatar -->
    <div class="flex items-center gap-2">
        <?php
        // Fetch pending items for badge (HR/Admin only)
        $pendingCount = 0;
        if ($userRole === ROLE_ADMIN || $userRole === ROLE_HR) {
            $db = getDBConnection();
            $pendingCount = (int)$db->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
        }
        ?>
        <div class="relative">
            <button class="w-7 h-7 rounded-full bg-white/15 flex items-center justify-center hover:bg-white/25 transition-colors" aria-label="Notifications">
                <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                <?php if ($pendingCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-danger text-[8px] flex items-center justify-center rounded-full ring-1 ring-brand"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
        </div>
        <button onclick="window.open('https://odoo.com/help', '_blank')" class="w-7 h-7 rounded-full bg-white/15 flex items-center justify-center hover:bg-white/25 transition-colors" aria-label="Help">
            <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
        </button>
        
        <div class="relative">
            <button onclick="toggleUserMenu()" class="w-7 h-7 rounded-full bg-brand-dark flex items-center justify-center text-[10px] font-medium ml-1 hover:ring-1 hover:ring-white/50 transition-all" title="<?= htmlspecialchars(getUserName()) ?>">
                <?= getUserInitials() ?>
            </button>
            <div id="user-dropdown" class="hidden absolute top-full right-0 mt-1 w-48 bg-white border border-surface-200 rounded-md shadow-lg z-[100] text-txt">
                <div class="px-4 py-3 border-b border-surface-100">
                    <p class="font-medium truncate"><?= htmlspecialchars(getUserName()) ?></p>
                    <p class="text-[11px] text-muted truncate"><?= htmlspecialchars($userRole) ?></p>
                </div>
                <?php if ($userRole === ROLE_ADMIN): ?>
                <a href="<?= BASE_URL ?>index.php?page=admin/settings" class="block px-4 py-2 hover:bg-surface-50">Settings</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>index.php?page=payroll/my_payslips" class="block px-4 py-2 hover:bg-surface-50">My Profile</a>
                <div class="border-t border-surface-100"></div>
                <a href="<?= BASE_URL ?>../backend/auth/logout.php" class="block px-4 py-2 text-danger hover:bg-danger-light">Logout</a>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleUserMenu() {
    document.getElementById('user-dropdown').classList.toggle('hidden');
}
// Close dropdown when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('#user-dropdown') && !e.target.closest('button[onclick="toggleUserMenu()"]')) {
        var dropdown = document.getElementById('user-dropdown');
        if (dropdown && !dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
        }
    }
});
</script>

<!-- Breadcrumb Strip — 32px, gray-50 bg -->
<div class="h-8 bg-surface-50 border-b border-surface-200 flex items-center px-4 text-[12px]">
    <div class="flex items-center gap-1.5">
        <a href="<?= BASE_URL ?>index.php?page=dashboard" class="text-brand hover:underline">Home</a>
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <span class="text-muted">›</span>
            <?php if ($i === count($breadcrumbs) - 1): ?>
                <span class="text-txt font-medium capitalize"><?= htmlspecialchars($crumb) ?></span>
            <?php else: ?>
                <a href="<?= BASE_URL ?>index.php?page=<?= implode('/', array_slice($breadcrumbs, 0, $i + 1)) ?>" class="text-brand hover:underline capitalize"><?= htmlspecialchars($crumb) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="ml-auto flex items-center gap-2 text-muted">
        <span class="capitalize"><?= htmlspecialchars(getUserRole()) ?></span>
        <span>·</span>
        <a href="<?= BASE_URL ?>../backend/auth/logout.php" class="text-brand hover:underline">Logout</a>
    </div>
</div>

<!-- Main Content Container -->
<div class="flex min-h-[calc(100vh-72px)]">
