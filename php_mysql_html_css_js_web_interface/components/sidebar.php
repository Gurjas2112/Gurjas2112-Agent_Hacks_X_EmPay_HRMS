<?php
/**
 * EmPay HRMS — Sidebar (Department Filter Style)
 * Light, 180px, white bg, 0.5px right border per design system spec 5.9
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$currentDept = $_GET['dept'] ?? 'All';
$userRole = getUserRole();

// Sidebar nav items
$sidebarItems = [
    ['label' => 'Dashboard',     'icon' => 'layout-dashboard', 'page' => 'dashboard',        'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Employees',     'icon' => 'users',            'page' => 'users',             'roles' => [ROLE_ADMIN, ROLE_HR, ROLE_EMPLOYEE, ROLE_PAYROLL]],
    ['label' => 'Add Employee',  'icon' => 'user-plus',        'page' => 'users/form',        'roles' => [ROLE_ADMIN, ROLE_HR]],
    
    // Integrated Policy Management (Restored for Admin & HR)
    ['label' => 'Designations',  'icon' => 'user-cog',         'page' => 'admin/designations', 'roles' => [ROLE_ADMIN, ROLE_HR]],
    ['label' => 'Work Policy',   'icon' => 'clock',            'page' => 'admin/work_policies','roles' => [ROLE_ADMIN, ROLE_HR]],
    ['label' => 'Leave Policy',  'icon' => 'calendar-heart',   'page' => 'admin/leave_policies','roles' => [ROLE_ADMIN, ROLE_HR]],

    ['label' => 'Mark Attendance','icon' => 'clock',            'page' => 'attendance/mark',   'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Attendance Log','icon' => 'calendar-days',     'page' => 'attendance/history','roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Overall Log',   'icon' => 'table',             'page' => 'attendance/log',     'roles' => [ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Attendance Map','icon' => 'map',              'page' => 'attendance/map',     'roles' => [ROLE_ADMIN, ROLE_HR]],
    ['label' => 'Apply Leave',   'icon' => 'calendar-plus',    'page' => 'leave/apply',       'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Manage Leaves', 'icon' => 'calendar-off',     'page' => 'leave/manage',      'roles' => [ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Schedule',      'icon' => 'calendar-clock',   'page' => 'schedule/index',    'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Payroll',       'icon' => 'wallet',           'page' => 'payroll',            'roles' => [ROLE_ADMIN, ROLE_PAYROLL]],
    ['label' => 'My Payslip',    'icon' => 'file-text',        'page' => 'payroll/my_payslips', 'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Reports',       'icon' => 'bar-chart-3',      'page' => 'reports/index',      'roles' => [ROLE_ADMIN, ROLE_PAYROLL]],
    ['label' => 'Settings',      'icon' => 'settings',         'page' => 'admin/settings',     'roles' => [ROLE_ADMIN]],
    ['label' => 'Geo Settings',  'icon' => 'map-pin',          'page' => 'admin/geo_settings', 'roles' => [ROLE_ADMIN]],
];

// Fetch Real Dept Counts for Sidebar
$db = getDBConnection();
$deptCounts = $db->query("
    SELECT d.name, COUNT(u.id) as count 
    FROM departments d 
    LEFT JOIN users u ON d.id = u.department_id AND u.is_active = 1
    GROUP BY d.id
")->fetchAll();
$totalCount = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
?>

<!-- Mobile Sidebar Backdrop -->
<div id="sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-black/50 z-[90] hidden lg:hidden"></div>

<!-- Sidebar — 180px, white bg, right border -->
<aside id="main-sidebar" class="fixed lg:static inset-y-0 left-0 w-[180px] flex-shrink-0 bg-white border-r border-surface-200 -translate-x-full lg:translate-x-0 transition-transform duration-300 z-[100] lg:z-auto flex flex-col h-full overflow-hidden">
    
    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto py-3">
        <div class="flex items-center justify-between px-3 mb-2 lg:hidden">
            <span class="font-bold text-brand">EmPay Menu</span>
            <button onclick="toggleMobileSidebar()" class="p-1 hover:bg-surface-100 rounded">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <p class="sidebar-section-heading">Navigation</p>
        <?php foreach ($sidebarItems as $item):
            if (!in_array($userRole, $item['roles'])) continue;
            $isActive = $currentPage === $item['page'];
        ?>
        <a href="<?= BASE_URL ?>index.php?page=<?= $item['page'] ?>" 
           class="sidebar-item <?= $isActive ? 'active' : '' ?>">
            <span class="flex items-center gap-2">
                <i data-lucide="<?= $item['icon'] ?>" class="w-3.5 h-3.5"></i>
                <?= $item['label'] ?>
            </span>
        </a>
        <?php endforeach; ?>

        <!-- Departments -->
        <?php if (canManageUsers()): ?>
        <div class="py-3 border-t border-surface-200 mt-4">
            <p class="sidebar-section-heading">Departments</p>
            <a href="<?= BASE_URL ?>index.php?page=users&dept=All" class="sidebar-item <?= $currentDept === 'All' ? 'active' : '' ?>">
                <span>All Staff</span>
                <span class="sidebar-count"><?= $totalCount ?></span>
            </a>
            <?php foreach ($deptCounts as $dept): ?>
            <a href="<?= BASE_URL ?>index.php?page=users&dept=<?= urlencode($dept['name']) ?>" 
               class="sidebar-item <?= $currentDept === $dept['name'] ? 'active' : '' ?>">
                <span class="truncate pr-2"><?= htmlspecialchars($dept['name']) ?></span>
                <span class="sidebar-count"><?= $dept['count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</aside>

<script>
function toggleMobileSidebar() {
    const sidebar = document.getElementById('main-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const isHidden = sidebar.classList.contains('-translate-x-full');
    
    if (isHidden) {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }
}
</script>

<!-- Main Content Area -->
<main class="flex-1 min-w-0 p-4 lg:p-6 overflow-y-auto bg-white">
