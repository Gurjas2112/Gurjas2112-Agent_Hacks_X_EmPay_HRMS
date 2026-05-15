<?php
/**
 * EmPay HRMS - Work Policies Management
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
    $working_days = (int)$_POST['working_days_per_week'];
    $off_days = trim($_POST['weekly_off_days']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($is_default) {
        $stmt = $db->prepare("UPDATE work_policies SET working_days_per_week = ?, weekly_off_days = ?, start_time = ?, end_time = ? WHERE is_default = 1");
        $stmt->execute([$working_days, $off_days, $start_time, $end_time]);
    } else {
        $stmt = $db->prepare("SELECT id FROM work_policies WHERE designation_id = ?");
        $stmt->execute([$designation_id]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE work_policies SET working_days_per_week = ?, weekly_off_days = ?, start_time = ?, end_time = ? WHERE designation_id = ?");
            $stmt->execute([$working_days, $off_days, $start_time, $end_time, $designation_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO work_policies (designation_id, is_default, working_days_per_week, weekly_off_days, start_time, end_time) VALUES (?, 0, ?, ?, ?, ?)");
            $stmt->execute([$designation_id, $working_days, $off_days, $start_time, $end_time]);
        }
    }
    setFlash('success', "Work policy saved.");
    header("Location: " . BASE_URL . "index.php?page=admin/work_policies");
    exit;
}

// Fetch data for editing
$edit_id = $_GET['edit_id'] ?? null;
$edit_data = null;
if ($edit_id) {
    $stmt = $db->prepare("SELECT * FROM work_policies WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}

$designations = $db->query("SELECT * FROM designations ORDER BY name ASC")->fetchAll();
$policies = $db->query("SELECT wp.*, d.name as designation_name FROM work_policies wp LEFT JOIN designations d ON wp.designation_id = d.id ORDER BY wp.is_default DESC, d.name ASC")->fetchAll();

include COMPONENTS_PATH . 'header.php';
include COMPONENTS_PATH . 'navbar.php';
include COMPONENTS_PATH . 'sidebar.php';
?>

<div class="p-6">
    <div class="mb-8">
        <h1 class="page-title mb-1">Work Policies</h1>
        <p class="text-muted text-[13px]">Define working hours and weekly off-days for your staff.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Upsert Form -->
        <div class="kanban-card h-fit">
            <h3 class="text-[16px] font-semibold mb-4"><?= $edit_data ? 'Edit Policy' : 'Set New Policy' ?></h3>
            <form action="<?= BASE_URL ?>index.php?page=admin/work_policies" method="POST" class="space-y-4">
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
                    <label class="form-label">Working Days / Week</label>
                    <input type="number" name="working_days_per_week" value="<?= $edit_data['working_days_per_week'] ?? '5' ?>" min="1" max="7" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                </div>
                <div>
                    <label class="form-label">Weekly Off Days</label>
                    <input type="text" name="weekly_off_days" value="<?= htmlspecialchars($edit_data['weekly_off_days'] ?? 'Sunday') ?>" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" value="<?= substr($edit_data['start_time'] ?? '09:00', 0, 5) ?>" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" value="<?= substr($edit_data['end_time'] ?? '18:00', 0, 5) ?>" class="w-full border border-border rounded-md px-3 py-2 text-[13px] outline-none focus:border-primary">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Save Policy</button>
                <?php if ($edit_data): ?>
                    <a href="<?= BASE_URL ?>index.php?page=admin/work_policies" class="btn btn-secondary w-full justify-center">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- List -->
        <div class="xl:col-span-3 table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Role / Target</th>
                        <th>Working Days</th>
                        <th>Off Days</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($policies as $p): ?>
                    <tr class="<?= $p['is_default'] ? 'bg-primary/5' : '' ?>">
                        <td class="font-medium text-primary-light">
                            <?= $p['is_default'] ? 'Global System Default' : htmlspecialchars($p['designation_name']) ?>
                            <?php if ($p['is_default']): ?>
                                <p class="text-[10px] text-muted font-normal mt-0.5">Applies to all employees without a specific override.</p>
                            <?php endif; ?>
                        </td>
                        <td><?= $p['working_days_per_week'] ?> Days</td>
                        <td><?= htmlspecialchars($p['weekly_off_days']) ?></td>
                        <td><?= date('H:i', strtotime($p['start_time'])) ?> - <?= date('H:i', strtotime($p['end_time'])) ?></td>
                        <td>
                            <?php if ($p['is_default']): ?>
                                <span class="badge badge-draft">System Wide</span>
                            <?php else: ?>
                                <span class="badge badge-active">Override</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= BASE_URL ?>index.php?page=admin/work_policies&edit_id=<?= $p['id'] ?>" class="text-primary hover:underline text-[12px]">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include COMPONENTS_PATH . 'footer.php'; ?>
