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
        <button onclick="toggleMobileSidebar()" class="lg:hidden w-8 h-8 flex items-center justify-center mr-1 text-white hover:bg-white/10 rounded-md">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <a href="<?= BASE_URL ?>index.php?page=dashboard" class="flex items-center gap-2 mr-4 font-medium">
            <i data-lucide="hexagon" class="w-4 h-4"></i>
            <span class="hidden sm:inline">EmPay</span>
        </a>
        <div class="hidden lg:flex items-center gap-1">
            <?php foreach ($navLinks as $nav):
                if (!in_array($userRole, $nav['roles'])) continue;
                $navRoot = explode('/', $nav['page'])[0];
                $isActive = (substr($currentPage, 0, strlen($navRoot)) === $navRoot);
            ?>
            <a href="<?= BASE_URL ?>index.php?page=<?= $nav['page'] ?>" 
               class="px-3 py-1 rounded <?= $isActive ? 'text-white font-medium' : 'text-white/75 hover:text-white' ?> transition-colors text-[12px]">
                <?= $nav['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: Notifications + Avatar -->
    <div class="flex items-center gap-2">
        <?php
        // Fetch pending items for badge (HR/Admin only)
        $pendingCount = 0;
        $pendingLeaves = [];
        if ($userRole === ROLE_ADMIN || $userRole === ROLE_HR) {
            $db = getDBConnection();
            $stmt = $db->query("SELECT l.*, u.full_name as name, t.name as type 
                FROM leaves l 
                JOIN users u ON l.user_id = u.id 
                JOIN leave_types t ON l.leave_type_id = t.id 
                WHERE l.status = 'pending' 
                ORDER BY l.created_at DESC 
                LIMIT 10");
            $pendingLeaves = $stmt->fetchAll();
            $pendingCount = count($pendingLeaves);
        }
        ?>
        <div class="relative">
            <button onclick="toggleNotifMenu()" class="w-7 h-7 rounded-full bg-white/15 flex items-center justify-center hover:bg-white/25 transition-colors" aria-label="Notifications">
                <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                <?php if ($pendingCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-danger text-[8px] flex items-center justify-center rounded-full ring-1 ring-brand"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
            <div id="notif-dropdown" class="hidden absolute top-full right-0 mt-1 w-72 bg-white border border-surface-200 rounded-md shadow-lg z-[100] text-txt">
                <div class="px-4 py-3 border-b border-surface-100 flex items-center justify-between">
                    <p class="text-[13px] font-medium">Notifications</p>
                    <?php if ($pendingCount > 0): ?>
                    <span class="badge badge-pending"><?= $pendingCount ?> pending</span>
                    <?php endif; ?>
                </div>
                <div class="max-h-64 overflow-y-auto">
                    <?php if (empty($pendingLeaves)): ?>
                    <div class="px-4 py-6 text-center text-muted text-[12px]">
                        <i data-lucide="inbox" class="w-6 h-6 mx-auto mb-2 opacity-50"></i>
                        <p>No pending notifications</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($pendingLeaves as $pl): ?>
                    <div class="px-4 py-3 border-b border-surface-100 hover:bg-surface-50">
                        <div class="flex items-center gap-2">
                            <div class="kanban-avatar w-6 h-6 text-[9px]"><?= strtoupper(substr($pl['name'], 0, 2)) ?></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-medium truncate"><?= htmlspecialchars($pl['name']) ?></p>
                                <p class="text-[11px] text-muted"><?= htmlspecialchars($pl['type']) ?> · <?= floor($pl['days']) ?> day<?= $pl['days'] > 1 ? 's' : '' ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if ($pendingCount > 0): ?>
                <div class="border-t border-surface-100">
                    <a href="<?= BASE_URL ?>index.php?page=leave/manage&status=pending" class="block px-4 py-2 text-[12px] text-brand text-center hover:bg-surface-50 font-medium">View All Requests</a>
                </div>
                <?php endif; ?>
            </div>
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
function toggleNotifMenu() {
    document.getElementById('notif-dropdown').classList.toggle('hidden');
}
// Close dropdowns when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('#user-dropdown') && !e.target.closest('button[onclick="toggleUserMenu()"]')) {
        var dropdown = document.getElementById('user-dropdown');
        if (dropdown && !dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
        }
    }
    if (!e.target.closest('#notif-dropdown') && !e.target.closest('button[onclick="toggleNotifMenu()"]')) {
        var ndropdown = document.getElementById('notif-dropdown');
        if (ndropdown && !ndropdown.classList.contains('hidden')) {
            ndropdown.classList.add('hidden');
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
