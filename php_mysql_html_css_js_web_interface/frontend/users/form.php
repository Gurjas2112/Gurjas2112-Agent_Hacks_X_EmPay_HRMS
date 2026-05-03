<?php
$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? 'Edit Employee' : 'Create Employee';
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
    'department_id' => '', 'designation_id' => '', 'role' => 'employee', 'date_of_join' => ''
];

$db = getDBConnection();
if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $fetchedUser = $stmt->fetch();
    if ($fetchedUser) {
        $user = $fetchedUser;
    }
}

$departments = $db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();
$designations = $db->query("SELECT id, name FROM designations ORDER BY name ASC")->fetchAll();
?>




<div class="flex items-center justify-between mb-6">
    <h1 class="page-title"><?= $pageTitle ?></h1>
    <div class="flex items-center gap-2">
        <a href="<?= BASE_URL ?>index.php?page=users" class="btn btn-secondary"><?= $canEdit ? 'Cancel' : 'Back' ?></a>
        <?php if ($canEdit) { ?>
        <button type="submit" form="emp-form" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create Employee' ?></button>
        <?php } ?>
    </div>
</div>

<div class="card max-w-3xl">
    <p class="caption mb-5"><?= $canEdit ? 'Fields marked * are required' : 'View-only access' ?></p>
    <form id="emp-form" action="<?= BASE_URL ?>../backend/users/<?= $isEdit ? 'update_user' : 'create_user' ?>.php" method="POST">
        <?php if ($isEdit) { ?><input type="hidden" name="user_id" value="<?= (int)$_GET['id'] ?>"><?php } ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-4">
            <div>
                <label class="form-label block">Full name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" placeholder="John Doe" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
            </div>
            <div>
                <label class="form-label block">Email address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" placeholder="john@company.com" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
            </div>
            <div>
                <label class="form-label block">Username *</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>" placeholder="johndoe" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
            </div>
            <div>
                <label class="form-label block">Phone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="+91 98765 43210" class="form-input" <?= !$canEdit ? 'readonly' : '' ?>>
            </div>
            <div>
                <label class="form-label block">Department</label>
                <select name="department_id" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                    <option value="">Select department</option>
                    <?php foreach ($departments as $dept) { ?>
                    <option value="<?= $dept['id'] ?>" <?= $user['department_id'] == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label class="form-label block">Designation</label>
                <select name="designation_id" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                    <option value="">Select designation</option>
                    <?php foreach ($designations as $desig) { ?>
                    <option value="<?= $desig['id'] ?>" <?= ($user['designation_id'] ?? '') == $desig['id'] ? 'selected' : '' ?>><?= htmlspecialchars($desig['name']) ?></option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label class="form-label block">Role *</label>
                <select name="role" class="form-input" <?= !$canEdit ? 'disabled' : '' ?>>
                    <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="hr" <?= $user['role'] === 'hr' ? 'selected' : '' ?>>HR</option>
                    <option value="payroll" <?= $user['role'] === 'payroll' ? 'selected' : '' ?>>Payroll</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div>
                <label class="form-label block">Date of joining</label>
                <input type="date" name="date_of_join" value="<?= htmlspecialchars($user['date_of_join']) ?>" class="form-input" placeholder="DD/MM/YYYY" <?= !$canEdit ? 'readonly' : '' ?>>
            </div>
            

        </div>
    </form>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
