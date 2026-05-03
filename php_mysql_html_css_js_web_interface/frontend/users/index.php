<?php
$pageTitle = 'Employees';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL, ROLE_EMPLOYEE);
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

require_once __DIR__ . '/../../config/database.php';

$db = getDBConnection();
$role = getUserRole();
$canManage = $role === ROLE_ADMIN || $role === ROLE_HR;

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

<?php
// Pagination, Search and Grouping Logic
$search = $_GET['q'] ?? '';
$filterStatus = $_GET['status'] ?? 'all';

// Re-filter the employees array based on search and status
$filteredEmployees = array_filter($employees, function($emp) use ($search, $filterStatus) {
    $matchesSearch = empty($search) || 
                     stripos($emp['name'], $search) !== false || 
                     stripos($emp['email'], $search) !== false;
    
    $matchesStatus = $filterStatus === 'all' || 
                     ($filterStatus === 'active' && $emp['is_active']) || 
                     ($filterStatus === 'archived' && !$emp['is_active']);
    
    return $matchesSearch && $matchesStatus;
});

$totalEmployees = count($filteredEmployees);
$limit = 1000; // Show all employees on a single page
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;
$groupBy = $_GET['group'] ?? 'none';

if ($groupBy === 'dept') {
    usort($filteredEmployees, function($a, $b) { return strcmp($a['dept'], $b['dept']); });
}

$displayEmployees = array_slice($filteredEmployees, $offset, $limit);
$startRange = $totalEmployees > 0 ? $offset + 1 : 0;
$endRange = min($offset + $limit, $totalEmployees);
?>

<!-- Action Bar per spec -->
<div class="flex items-center justify-between mb-4">
    <h1 class="page-title">Employees</h1>
    <div class="flex items-center gap-2">
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>index.php?page=users/form" class="btn btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> New
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Filters per spec 6 -->
<div class="flex items-center gap-3 mb-4">
    <form action="<?= BASE_URL ?>index.php" method="GET" class="flex-1 relative">
        <input type="hidden" name="page" value="users">
        <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search employees…" class="form-input !pl-9 !rounded-full !h-9">
    </form>
    <div class="relative">
        <button onclick="toggleFilterMenu()" class="btn btn-secondary text-[12px]"><i data-lucide="filter" class="w-3.5 h-3.5"></i> Filters</button>
        <div id="filter-menu" class="hidden absolute top-full left-0 mt-1 w-48 bg-white border border-surface-200 rounded-md shadow-lg z-50">
            <a href="<?= BASE_URL ?>index.php?page=users&status=all" class="block px-4 py-2 text-[12px] hover:bg-surface-50">All Staff</a>
            <a href="<?= BASE_URL ?>index.php?page=users&status=active" class="block px-4 py-2 text-[12px] hover:bg-surface-50">Active Only</a>
            <a href="<?= BASE_URL ?>index.php?page=users&status=archived" class="block px-4 py-2 text-[12px] hover:bg-surface-50">Archived</a>
        </div>
    </div>
    <div class="relative">
        <button onclick="toggleGroupMenu()" class="btn btn-secondary text-[12px]"><i data-lucide="layers" class="w-3.5 h-3.5"></i> Group By</button>
        <div id="group-menu" class="hidden absolute top-full left-0 mt-1 w-48 bg-white border border-surface-200 rounded-md shadow-lg z-50">
            <a href="<?= BASE_URL ?>index.php?page=users&group=none" class="block px-4 py-2 text-[12px] hover:bg-surface-50">None</a>
            <a href="<?= BASE_URL ?>index.php?page=users&group=dept" class="block px-4 py-2 text-[12px] hover:bg-surface-50">Department</a>
        </div>
    </div>
    <div class="flex items-center text-[12px] text-muted ml-2">
        <?= $startRange ?>-<?= $endRange ?> / <?= $totalEmployees ?>
        <a href="<?= $page > 1 ? BASE_URL . 'index.php?page=users&p=' . ($page-1) . '&q=' . urlencode($search) : '#' ?>" class="ml-2 p-1 hover:bg-surface-50 rounded <?= $page <= 1 ? 'opacity-30 cursor-not-allowed' : '' ?>" aria-label="Previous"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
        <a href="<?= $endRange < $totalEmployees ? BASE_URL . 'index.php?page=users&p=' . ($page+1) . '&q=' . urlencode($search) : '#' ?>" class="p-1 hover:bg-surface-50 rounded <?= $endRange >= $totalEmployees ? 'opacity-30 cursor-not-allowed' : '' ?>" aria-label="Next"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
    </div>
</div>

<!-- Kanban Grid per spec 5.8 -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
    <?php foreach ($displayEmployees as $emp):
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
    ?>
    <a href="<?= BASE_URL ?>index.php?page=users/form&id=<?= $emp['id'] ?>" class="kanban-card hover:border-brand/40 hover:shadow-md transition-all block group">
        <div class="flex items-start gap-3">
            <div class="kanban-avatar group-hover:bg-brand group-hover:text-white transition-colors"><?= strtoupper(substr($emp['name'],0,2)) ?></div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-medium text-txt group-hover:text-brand truncate transition-colors"><?= $emp['name'] ?></span>
                    <span class="<?= $presenceIcon[1] ?> flex-shrink-0" title="<?= $presenceIcon[2] ?>">
                        <i data-lucide="<?= $presenceIcon[0] ?>" class="w-4 h-4"></i>
                    </span>
                </div>
                <p class="caption"><?= htmlspecialchars($emp['dept'] ?? 'Unassigned') ?></p>
                <div class="mt-2 space-y-1">
                    <p class="caption flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3 text-muted/60"></i> <?= htmlspecialchars($emp['email']) ?></p>
                    <?php if ($emp['phone']): ?>
                    <p class="caption flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3 text-muted/60"></i> <?= htmlspecialchars($emp['phone']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="badge <?= $chipClass ?> capitalize"><?= htmlspecialchars($emp['role']) ?></span>
                        <?php if (!$emp['is_active']): ?>
                        <span class="badge badge-cancelled">Archived</span>
                        <?php endif; ?>
                    </div>
                    <i data-lucide="chevron-right" class="w-3 h-3 text-muted opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<script>
function toggleFilterMenu() {
    document.getElementById('filter-menu').classList.toggle('hidden');
}
function toggleGroupMenu() {
    document.getElementById('group-menu').classList.toggle('hidden');
}
// Close menu when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.btn-secondary') && !event.target.closest('.btn-secondary')) {
        var fMenu = document.getElementById('filter-menu');
        var gMenu = document.getElementById('group-menu');
        if (fMenu && !fMenu.classList.contains('hidden')) fMenu.classList.add('hidden');
        if (gMenu && !gMenu.classList.contains('hidden')) gMenu.classList.add('hidden');
    }
}
</script>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
