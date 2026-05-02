<?php
/**
 * EmPay HRMS - Designations Management
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

requireRole(ROLE_ADMIN, ROLE_HR);

$db = getDBConnection();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            if (!empty($name)) {
                $stmt = $db->prepare("INSERT IGNORE INTO designations (name) VALUES (?)");
                $stmt->execute([$name]);
                setFlash('success', "Designation '$name' added successfully.");
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("DELETE FROM designations WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', "Designation deleted.");
        }
    }
    header("Location: " . BASE_URL . "index.php?page=admin/designations");
    exit;
}

$designations = $db->query("SELECT * FROM designations ORDER BY name ASC")->fetchAll();

include COMPONENTS_PATH . 'header.php';
include COMPONENTS_PATH . 'navbar.php';
include COMPONENTS_PATH . 'sidebar.php';
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="page-title mb-1">Designations Management</h1>
            <p class="text-muted text-[13px]">Define job titles like Sales, Engineering, etc.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Add Form -->
        <div class="kanban-card h-fit">
            <h3 class="text-[16px] font-semibold mb-4">Add New Designation</h3>
            <form action="<?= BASE_URL ?>index.php?page=admin/designations" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="form-label">Designation Name</label>
                    <input type="text" name="name" placeholder="e.g. Sales Executive" 
                           class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary" required>
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Create Designation</button>
            </form>
        </div>

        <!-- List -->
        <div class="xl:col-span-2 table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($designations as $d): ?>
                    <tr>
                        <td class="font-medium"><?= htmlspecialchars($d['name']) ?></td>
                        <td><span class="badge badge-active">Active</span></td>
                        <td class="text-right">
                            <form action="<?= BASE_URL ?>index.php?page=admin/designations" method="POST" class="inline" onsubmit="return confirm('Delete this?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button type="submit" class="text-danger hover:underline text-[12px]">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include COMPONENTS_PATH . 'footer.php'; ?>
