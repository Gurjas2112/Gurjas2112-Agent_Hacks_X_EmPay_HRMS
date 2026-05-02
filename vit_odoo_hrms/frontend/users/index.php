<?php
$pageTitle = 'Employees';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR);
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();

// Fetch employees with their department and today's attendance status
$sql = "
    SELECT u.id, u.full_name as name, u.email, u.phone, u.role, u.is_active, 
           d.name as dept,
           a.check_in, a.check_out, a.status as attendance_status,
           l.status as leave_status
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURRENT_DATE
    LEFT JOIN leaves l ON u.id = l.user_id AND CURRENT_DATE BETWEEN l.from_date AND l.to_date AND l.status = 'approved'
";

$stmt = $db->query($sql);
$employees = $stmt->fetchAll();

?>

<!-- Action Bar per spec -->
<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Employees</h1>
    <div class="flex items-center gap-2">
        <a href="<?= BASE_URL ?>index.php?page=users/form" class="btn btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> New
        </a>
    </div>
</div>

<!-- Search & Filters per spec 6 -->
<div class="flex items-center gap-3 mb-4">
    <div class="flex-1 relative">
        <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" placeholder="Search employees…" class="form-input !pl-9 !rounded-full !h-9">
    </div>
    <button class="btn btn-secondary text-[12px]"><i data-lucide="filter" class="w-3.5 h-3.5"></i> Filters</button>
    <button class="btn btn-secondary text-[12px]"><i data-lucide="layers" class="w-3.5 h-3.5"></i> Group By</button>
    <div class="flex items-center text-[12px] text-muted ml-2">
        1-6 / <?= count($employees) ?>
        <button class="ml-2 p-1 hover:bg-surface-50 rounded" aria-label="Previous"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
        <button class="p-1 hover:bg-surface-50 rounded" aria-label="Next"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
    </div>
</div>

<!-- Kanban Grid per spec 5.8 -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
    <?php foreach ($employees as $emp):
        // Determine presence
        $presence = 'none';
        if ($emp['leave_status'] === 'approved') {
            $presence = 'leave';
        } elseif ($emp['check_in']) {
            $presence = 'work';
        }

        $presenceIcon = match($presence) {
            'work'   => ['circle-dot','text-success-text','At Work'],
            'remote' => ['home','text-info-text','Remote'],
            'leave'  => ['plane','text-warning-text','On Leave'],
            default  => ['circle-dashed','text-muted','No Status'],
        };
        $chipClass = match($emp['role']) {
            'hr'      => 'badge-employee',
            'payroll' => 'badge-consultant',
            'admin'   => 'badge-approved',
            default   => 'badge-employee',
        };
        $statusLabel = $emp['is_active'] ? 'active' : 'inactive';
    ?>
    <div class="kanban-card">
        <div class="flex items-start gap-3">
            <div class="kanban-avatar"><?= strtoupper(substr($emp['name'],0,2)) ?></div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <a href="<?= BASE_URL ?>index.php?page=users/form&id=<?= $emp['id'] ?>" class="text-[13px] font-medium link truncate"><?= $emp['name'] ?></a>
                    <span class="<?= $presenceIcon[1] ?> flex-shrink-0" title="<?= $presenceIcon[2] ?>">
                        <i data-lucide="<?= $presenceIcon[0] ?>" class="w-4 h-4"></i>
                    </span>
                </div>
                <p class="caption"><?= $emp['dept'] ?></p>
                <div class="mt-2 space-y-1">
                    <p class="caption flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3"></i> <?= $emp['email'] ?></p>
                    <p class="caption flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> <?= $emp['phone'] ?></p>
                </div>
                <div class="mt-3 flex items-center gap-1.5">
                    <span class="badge <?= $chipClass ?> capitalize"><?= htmlspecialchars($emp['role']) ?></span>
                    <?php if (!$emp['is_active']): ?>
                    <span class="badge badge-cancelled">Archived</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
