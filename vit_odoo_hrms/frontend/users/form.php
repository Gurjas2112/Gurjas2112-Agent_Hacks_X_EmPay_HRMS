<?php
$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? 'Edit Employee' : 'Create Employee';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR);
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$user = [
    'full_name' => '', 'email' => '', 'username' => '', 'phone' => '',
    'department_id' => '', 'designation' => '', 'role' => 'employee', 'date_of_join' => ''
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

$departments = $db->query("SELECT id, name FROM departments")->fetchAll();
?>

<!-- Action Bar -->
<div class="flex items-center justify-between mb-6">
    <h1 class="page-title"><?= $pageTitle ?></h1>
    <div class="flex items-center gap-2">
        <a href="<?= BASE_URL ?>index.php?page=users" class="btn btn-secondary">Cancel</a>
        <button type="submit" form="emp-form" class="btn btn-primary"><?= $isEdit ? 'Save' : 'Create Employee' ?></button>
    </div>
</div>

<div class="card max-w-3xl">
    <p class="caption mb-5">Fields marked * are required</p>
    <form id="emp-form" action="<?= BASE_URL ?>../backend/users/<?= $isEdit ? 'update_user' : 'create_user' ?>.php" method="POST">
        <?php if ($isEdit): ?><input type="hidden" name="user_id" value="<?= (int)$_GET['id'] ?>"><?php endif; ?>

        <!-- 2-column layout per spec 5.6 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-4">
            <div>
                <label class="form-label block">Full name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" placeholder="John Doe" class="form-input">
            </div>
            <div>
                <label class="form-label block">Email address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" placeholder="john@company.com" class="form-input">
            </div>
            <div>
                <label class="form-label block">Username *</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>" placeholder="johndoe" class="form-input">
            </div>
            <div>
                <label class="form-label block">Phone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="+91 98765 43210" class="form-input">
            </div>
            <div>
                <label class="form-label block">Department</label>
                <select name="department_id" class="form-input">
                    <option value="">Select department</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $user['department_id'] == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label block">Designation</label>
                <input type="text" name="designation" value="<?= htmlspecialchars($user['designation']) ?>" placeholder="Software Engineer" class="form-input">
            </div>
            <div>
                <label class="form-label block">Role *</label>
                <select name="role" class="form-input">
                    <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="hr" <?= $user['role'] === 'hr' ? 'selected' : '' ?>>HR</option>
                    <option value="payroll" <?= $user['role'] === 'payroll' ? 'selected' : '' ?>>Payroll</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div>
                <label class="form-label block">Date of joining</label>
                <input type="date" name="date_of_join" value="<?= htmlspecialchars($user['date_of_join']) ?>" class="form-input" placeholder="DD/MM/YYYY">
            </div>
            <?php if (!$isEdit): ?>
            <div class="sm:col-span-2">
                <label class="form-label block">Password *</label>
                <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" class="form-input max-w-xs">
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
