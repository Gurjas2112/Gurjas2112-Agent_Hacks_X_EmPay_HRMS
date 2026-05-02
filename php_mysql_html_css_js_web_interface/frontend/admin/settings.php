<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

// Only Admin can access
requireRole(ROLE_ADMIN);

require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$db = getDBConnection();
$users = $db->query("SELECT id, full_name, username, role, email FROM users ORDER BY full_name ASC")->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="page-title">System Settings</h1>
    <button class="btn btn-primary">Save All Changes</button>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    
    <!-- General Settings -->
    <div class="xl:col-span-1 space-y-6">
        <div class="card">
            <h2 class="section-heading mb-4">Organization Profile</h2>
            <div class="space-y-4">
                <div>
                    <label class="form-label block">Company Name</label>
                    <input type="text" value="<?= APP_NAME ?>" class="form-input" placeholder="EmPay HRMS">
                </div>
                <div>
                    <label class="form-label block">Tagline</label>
                    <input type="text" value="<?= APP_TAGLINE ?>" class="form-input" placeholder="Smart HR Management">
                </div>
                <div>
                    <label class="form-label block">Base URL</label>
                    <input type="text" value="<?= BASE_URL ?>" class="form-input" readonly>
                    <p class="caption mt-1">Managed via config/app.php</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-heading mb-4">Security Policies</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[13px]">Force Password Change</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-8 h-4 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[13px]">Two-Factor Authentication</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-8 h-4 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-primary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- User Role Management (Requirement 1.2) -->
    <div class="xl:col-span-2 card !p-0">
        <div class="px-6 py-4 border-b border-surface-200">
            <h2 class="section-heading">Manage User Roles</h2>
            <p class="caption mt-1">Control system access by assigning roles to personnel</p>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Current Role</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="kanban-avatar w-7 h-7 text-[10px]"><?= strtoupper(substr($u['full_name'],0,2)) ?></div>
                            <div>
                                <p class="font-medium text-[13px]"><?= htmlspecialchars($u['full_name']) ?></p>
                                <p class="caption"><?= htmlspecialchars($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <select class="form-input !w-32 !h-7 !text-[11px] font-medium uppercase">
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="hr" <?= $u['role'] === 'hr' ? 'selected' : '' ?>>HR Officer</option>
                            <option value="payroll" <?= $u['role'] === 'payroll' ? 'selected' : '' ?>>Payroll</option>
                            <option value="employee" <?= $u['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                        </select>
                    </td>
                    <td class="text-right">
                        <button class="btn btn-ghost !p-1 text-primary" title="Update Role">
                            <i data-lucide="save" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
