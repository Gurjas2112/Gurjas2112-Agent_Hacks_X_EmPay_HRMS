<?php
$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? 'Employee Profile' : 'Create Employee';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL, ROLE_EMPLOYEE);
$userRole = getUserRole();
$canEdit = ($userRole === ROLE_ADMIN || $userRole === ROLE_HR);

if (!$canEdit && !$isEdit) {
    setFlash('error', 'Unauthorized access.');
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$user = [
    'full_name' => '', 'email' => '', 'username' => '', 'phone' => '',
    'department_id' => '', 'designation_id' => '', 'role' => 'employee', 
    'date_of_join' => '', 'date_of_birth' => '', 'gender' => '', 
    'address' => '', 'basic_salary' => 0, 'is_active' => 1
];

$db = getDBConnection();
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $fetchedUser = $stmt->fetch();
    if ($fetchedUser) {
        $user = $fetchedUser;
    } else {
        setFlash('error', 'Employee not found.');
        header('Location: ' . BASE_URL . 'index.php?page=users');
        exit;
    }
}

$departments = $db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();
$designations = $db->query("SELECT id, name FROM designations ORDER BY name ASC")->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="<?= BASE_URL ?>index.php?page=users" class="p-2 hover:bg-surface-100 rounded-full transition-colors">
            <i data-lucide="arrow-left" class="w-5 h-5 text-muted"></i>
        </a>
        <div>
            <h1 class="page-title !mb-0"><?= $pageTitle ?></h1>
            <p class="text-[12px] text-muted"><?= $isEdit ? 'Manage and update employee information' : 'Register a new staff member' ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <?php if (!$isEdit && $canEdit): ?>
        <a href="<?= BASE_URL ?>index.php?page=users/import" class="btn btn-secondary">
            <i data-lucide="upload" class="w-4 h-4"></i> Import CSV
        </a>
        <?php endif; ?>
        <?php if ($canEdit) { ?>
        <button type="submit" form="emp-form" class="btn btn-primary">
            <i data-lucide="save" class="w-4 h-4"></i>
            <?= $isEdit ? 'Save Changes' : 'Create Employee' ?>
        </button>
        <?php } ?>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <!-- Left Column: Main Info -->
    <div class="xl:col-span-2 space-y-6">
        <?php if (!$isEdit && $canEdit): ?>
        <div class="card border-dashed bg-brand/5 border-brand/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand/10 text-brand rounded-full flex items-center justify-center">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-brand">Prefer Batch Upload?</h3>
                    <p class="text-[11px] text-brand/70">Skip manual entry and upload multiple employees via CSV.</p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>index.php?page=users/import" class="btn btn-primary !bg-brand !h-9">
                Go to Import
            </a>
        </div>
        <?php endif; ?>

        <form id="emp-form" action="<?= BASE_URL ?>../backend/users/<?= $isEdit ? 'update_user' : 'create_user' ?>.php" method="POST">
            <?php if ($isEdit) { ?><input type="hidden" name="user_id" value="<?= (int)$_GET['id'] ?>"><?php } ?>

            <!-- Personal Information -->
            <div class="card">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-surface-200">
                    <i data-lucide="user" class="w-5 h-5 text-brand"></i>
                    <h2 class="text-[15px] font-semibold text-txt">Personal Information</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
                    </div>
                    <div>
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
                    </div>
                    <div>
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                            <option value="">Select Gender</option>
                            <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Residential Address</label>
                        <textarea name="address" rows="3" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="card mt-6">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-surface-200">
                    <i data-lucide="briefcase" class="w-5 h-5 text-brand"></i>
                    <h2 class="text-[15px] font-semibold text-txt">Employment Details</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $dept) { ?>
                            <option value="<?= $dept['id'] ?>" <?= $user['department_id'] == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Designation</label>
                        <select name="designation_id" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                            <option value="">Select designation</option>
                            <?php foreach ($designations as $desig) { ?>
                            <option value="<?= $desig['id'] ?>" <?= ($user['designation_id'] ?? '') == $desig['id'] ? 'selected' : '' ?>><?= htmlspecialchars($desig['name']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Date of Joining</label>
                        <input type="date" name="date_of_join" value="<?= htmlspecialchars($user['date_of_join'] ?? '') ?>" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
                    </div>
                    <div>
                        <label class="form-label">Basic Salary</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-xs">₹</span>
                            <input type="number" step="0.01" name="basic_salary" value="<?= htmlspecialchars($user['basic_salary'] ?? '0.00') ?>" class="form-input !pl-7" <?= !$canEdit ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Right Column: Account & Security -->
    <div class="space-y-6">
        <div class="card">
            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-surface-200">
                <i data-lucide="shield-check" class="w-5 h-5 text-brand"></i>
                <h2 class="text-[15px] font-semibold text-txt">Account Settings</h2>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="form-label">Username *</label>
                    <input type="text" form="emp-form" name="username" required value="<?= htmlspecialchars($user['username']) ?>" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label class="form-label">Work Email *</label>
                    <input type="email" form="emp-form" name="email" required value="<?= htmlspecialchars($user['email']) ?>" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label class="form-label">Phone Number</label>
                    <input type="tel" form="emp-form" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label class="form-label">System Role *</label>
                    <select name="role" form="emp-form" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                        <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                        <option value="hr" <?= $user['role'] === 'hr' ? 'selected' : '' ?>>HR Manager</option>
                        <option value="payroll" <?= $user['role'] === 'payroll' ? 'selected' : '' ?>>Payroll Officer</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Account Status</label>
                    <select name="is_active" form="emp-form" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                        <option value="1" <?= $user['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $user['is_active'] == 0 ? 'selected' : '' ?>>Archived / Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if ($isEdit): ?>
        <div class="card bg-surface-50 border-dashed">
            <div class="text-center py-4">
                <div class="w-16 h-16 bg-brand/10 text-brand rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-xl font-bold"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                </div>
                <h3 class="font-semibold text-txt"><?= htmlspecialchars($user['full_name']) ?></h3>
                <p class="text-xs text-muted mt-1">ID: #EMP-<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
