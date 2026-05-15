<?php
/**
 * EmPay HRMS - Leave Policies Management
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

requireRole(ROLE_ADMIN, ROLE_HR);

$db = getDBConnection();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $designation_id = $_POST['designation_id'] === 'default' ? null : (int)$_POST['designation_id'];
    $is_default = $_POST['designation_id'] === 'default' ? 1 : 0;
    $paid = (int)$_POST['paid_leaves'];
    $sick = (int)$_POST['sick_leaves'];
    $casual = (int)$_POST['casual_leaves'];

    if ($is_default) {
        $stmt = $db->prepare("UPDATE leave_policies SET paid_leaves = ?, sick_leaves = ?, casual_leaves = ? WHERE is_default = 1");
        $stmt->execute([$paid, $sick, $casual]);
    } else {
        $stmt = $db->prepare("SELECT id FROM leave_policies WHERE designation_id = ?");
        $stmt->execute([$designation_id]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE leave_policies SET paid_leaves = ?, sick_leaves = ?, casual_leaves = ? WHERE designation_id = ?");
            $stmt->execute([$paid, $sick, $casual, $designation_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO leave_policies (designation_id, is_default, paid_leaves, sick_leaves, casual_leaves) VALUES (?, 0, ?, ?, ?)");
            $stmt->execute([$designation_id, $paid, $sick, $casual]);
        }
    }
    setFlash('success', "Leave policy saved.");
    header("Location: " . BASE_URL . "index.php?page=admin/leave_policies");
    exit;
}

// Fetch data for editing
$edit_id = $_GET['edit_id'] ?? null;
$edit_data = null;
if ($edit_id) {
    $stmt = $db->prepare("SELECT * FROM leave_policies WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

$designations = $db->query("SELECT * FROM designations ORDER BY name ASC")->fetchAll();
$policies = $db->query("SELECT lp.*, d.name as designation_name FROM leave_policies lp LEFT JOIN designations d ON lp.designation_id = d.id ORDER BY lp.is_default DESC, d.name ASC")->fetchAll();

include COMPONENTS_PATH . 'header.php';
include COMPONENTS_PATH . 'navbar.php';
include COMPONENTS_PATH . 'sidebar.php';
?>

<div class="p-6">
    <div class="mb-8">
        <h1 class="page-title mb-1">Leave Policies</h1>
        <p class="text-muted text-[13px]">Configure annual leave quotas and balance policies.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Form -->
        <div class="kanban-card h-fit">
            <h3 class="text-[16px] font-semibold mb-4"><?= $edit_data ? 'Edit Policy' : 'Set New Policy' ?></h3>
            <form action="<?= BASE_URL ?>index.php?page=admin/leave_policies" method="POST" class="space-y-4">
                <div>
                    <label class="form-label">Apply To</label>
                    <select name="designation_id" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary" <?= $edit_data ? 'disabled' : '' ?>>
                        <option value="default" <?= ($edit_data && $edit_data['is_default']) ? 'selected' : '' ?>>Global Default</option>
                        <?php foreach ($designations as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($edit_data && $edit_data['designation_id'] == $d['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="designation_id" value="<?= $edit_data['is_default'] ? 'default' : $edit_data['designation_id'] ?>">
                    <?php endif; ?>
                </div>
                <div>
                    <label class="form-label">Paid Leaves / Year</label>
                    <input type="number" name="paid_leaves" value="<?= $edit_data['paid_leaves'] ?? '12' ?>" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                </div>
                <div>
                    <label class="form-label">Sick Leaves / Year</label>
                    <input type="number" name="sick_leaves" value="<?= $edit_data['sick_leaves'] ?? '6' ?>" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                </div>
                <div>
                    <label class="form-label">Casual Leaves / Year</label>
                    <input type="number" name="casual_leaves" value="<?= $edit_data['casual_leaves'] ?? '6' ?>" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Save Policy</button>
                <?php if ($edit_data): ?>
                    <a href="<?= BASE_URL ?>index.php?page=admin/leave_policies" class="btn btn-secondary w-full justify-center">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- List -->
        <div class="xl:col-span-3 table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Role / Target</th>
                        <th>Paid</th>
                        <th>Sick</th>
                        <th>Casual</th>
                        <th>Total</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($policies as $p): ?>
                    <tr class="<?= $p['is_default'] ? 'bg-primary/5' : '' ?>">
                        <td class="font-medium text-primary-light">
                            <?= $p['is_default'] ? 'Global System Default' : htmlspecialchars($p['designation_name']) ?>
                            <?php if ($p['is_default']): ?>
                                <p class="text-[10px] text-muted font-normal mt-0.5">Base annual quota for all staff.</p>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['paid_leaves'] ?> Days</td>
                        <td><?= $p['sick_leaves'] ?> Days</td>
                        <td><?= $p['casual_leaves'] ?> Days</td>
                        <td class="font-bold"><?= $p['paid_leaves'] + $p['sick_leaves'] + $p['casual_leaves'] ?> Days</td>
                        <td class="text-right">
                            <a href="<?= BASE_URL ?>index.php?page=admin/leave_policies&edit_id=<?= $p['id'] ?>" class="text-primary hover:underline text-[12px]">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include COMPONENTS_PATH . 'footer.php'; ?>
