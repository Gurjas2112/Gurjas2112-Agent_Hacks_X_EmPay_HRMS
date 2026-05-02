<?php
/**
 * EmPay HRMS — Sidebar (Department Filter Style)
 * Light, 180px, white bg, 0.5px right border per design system spec 5.9
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$userRole = getUserRole();

// Sidebar nav items
$sidebarItems = [
    ['label' => 'Dashboard',     'icon' => 'layout-dashboard', 'page' => 'dashboard',        'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Employees',     'icon' => 'users',            'page' => 'users',             'roles' => [ROLE_ADMIN, ROLE_HR]],
    ['label' => 'Add Employee',  'icon' => 'user-plus',        'page' => 'users/user_form',   'roles' => [ROLE_ADMIN, ROLE_HR]],
    ['label' => 'Mark Attendance','icon' => 'clock',            'page' => 'attendance/mark',   'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Attendance Log','icon' => 'calendar-days',     'page' => 'attendance/history','roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Apply Leave',   'icon' => 'calendar-plus',    'page' => 'leave/apply',       'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Manage Leaves', 'icon' => 'calendar-off',     'page' => 'leave/manage',      'roles' => [ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Schedule',      'icon' => 'calendar-clock',   'page' => 'schedule/index',    'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Payroll',       'icon' => 'wallet',           'page' => 'payroll',            'roles' => [ROLE_ADMIN, ROLE_PAYROLL]],
    ['label' => 'My Payslip',    'icon' => 'file-text',        'page' => 'payroll/my_payslips', 'roles' => [ROLE_ADMIN, ROLE_EMPLOYEE, ROLE_HR, ROLE_PAYROLL]],
    ['label' => 'Reports',       'icon' => 'bar-chart-3',      'page' => 'reports/index',      'roles' => [ROLE_ADMIN, ROLE_PAYROLL]],
    ['label' => 'Settings',      'icon' => 'settings',         'page' => 'admin/settings',     'roles' => [ROLE_ADMIN]],
];
?>

<!-- Sidebar — 180px, white bg, right border -->
<aside class="w-[180px] flex-shrink-0 bg-white border-r border-surface-200 hidden lg:block overflow-y-auto">
    
    <div class="py-3">
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
    </div>

    <?php if (canManageUsers()): ?>
    <div class="py-3 border-t border-surface-200">
        <p class="sidebar-section-heading">Departments</p>
        <?php 
        $depts = [
            ['name' => 'All', 'count' => 248],
            ['name' => 'Engineering', 'count' => 82],
            ['name' => 'Human Resources', 'count' => 15],
            ['name' => 'Marketing', 'count' => 34],
            ['name' => 'Design', 'count' => 28],
            ['name' => 'Finance', 'count' => 22],
            ['name' => 'Operations', 'count' => 67],
        ];
        foreach ($depts as $dept): ?>
        <a href="#" class="sidebar-item <?= $dept['name'] === 'All' ? 'active' : '' ?>">
            <span><?= $dept['name'] ?></span>
            <span class="sidebar-count"><?= $dept['count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</aside>

<!-- Main Content Area -->
<main class="flex-1 min-w-0 p-6">
