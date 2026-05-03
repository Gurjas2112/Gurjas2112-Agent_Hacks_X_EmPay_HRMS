<?php
$pageTitle = 'Import Employees';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
requireRole(ROLE_ADMIN, ROLE_HR);

require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';
?>

<div class="flex items-center gap-4 mb-6">
    <a href="<?= BASE_URL ?>index.php?page=users" class="p-2 hover:bg-surface-100 rounded-full transition-colors">
        <i data-lucide="arrow-left" class="w-5 h-5 text-muted"></i>
    </a>
    <div>
        <h1 class="page-title !mb-0">Import Employees</h1>
        <p class="text-[12px] text-muted">Upload a CSV file to batch-add employees to the system.</p>
    </div>
</div>

<?php if ($msg = getFlash('success')): ?>
    <div class="mb-6 p-4 bg-success/10 border border-success/20 text-success rounded-xl flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        <span class="text-sm font-medium"><?= $msg ?></span>
    </div>
<?php endif; ?>

<?php if ($msg = getFlash('warning')): ?>
    <div class="mb-6 p-4 bg-warning/10 border border-warning/20 text-warning-text rounded-xl flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        <span class="text-sm font-medium"><?= $msg ?></span>
    </div>
<?php endif; ?>

<?php if ($msg = getFlash('error')): ?>
    <div class="mb-6 p-4 bg-danger/10 border border-danger/20 text-danger-text rounded-xl flex items-center gap-3">
        <i data-lucide="x-circle" class="w-5 h-5"></i>
        <span class="text-sm font-medium"><?= $msg ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Upload Section -->
    <div class="lg:col-span-1">
        <div class="card">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="file-up" class="w-5 h-5 text-brand"></i>
                <h2 class="text-[15px] font-semibold text-txt">Upload File</h2>
            </div>
            
            <form action="<?= BASE_URL ?>../backend/users/import_csv.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="border-2 border-dashed border-surface-200 rounded-xl p-8 text-center hover:border-brand/50 transition-colors group relative">
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" class="absolute inset-0 opacity-0 cursor-pointer" onchange="updateFileName(this)">
                    <div id="upload-placeholder">
                        <div class="w-12 h-12 bg-brand/10 text-brand rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm font-medium text-txt">Click to upload or drag and drop</p>
                        <p class="text-xs text-muted mt-1">CSV files only (max. 5MB)</p>
                    </div>
                    <div id="file-selected" class="hidden text-center">
                        <div class="w-12 h-12 bg-success/10 text-success rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="file-check" class="w-6 h-6"></i>
                        </div>
                        <p id="file-name" class="text-sm font-medium text-txt truncate"></p>
                        <button type="button" onclick="resetUpload()" class="text-xs text-danger mt-2 hover:underline">Remove file</button>
                    </div>
                </div>

                <div class="bg-surface-50 rounded-lg p-4">
                    <h3 class="text-xs font-semibold text-txt uppercase tracking-wider mb-2">Instructions</h3>
                    <ul class="text-[11px] text-muted space-y-1.5 list-disc pl-4">
                        <li>Download the <a href="<?= BASE_URL ?>../employees.csv" class="text-brand hover:underline font-medium">CSV Template</a></li>
                        <li>Ensure column headers match exactly</li>
                        <li>Existing usernames/emails will be skipped</li>
                        <li>Dates should be in YYYY-MM-DD format</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary w-full py-2.5">
                    <i data-lucide="play" class="w-4 h-4"></i>
                    Process Import
                </button>
            </form>
        </div>
    </div>

    <!-- Results Section -->
    <div class="lg:col-span-2">
        <?php if (isset($_GET['imported'])): 
            $importedIds = explode(',', $_GET['imported']);
            require_once __DIR__ . '/../../config/database.php';
            $db = getDBConnection();
            $placeholders = implode(',', array_fill(0, count($importedIds), '?'));
            $stmt = $db->prepare("SELECT u.*, d.name as dept FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id IN ($placeholders)");
            $stmt->execute($importedIds);
            $newEmployees = $stmt->fetchAll();
        ?>
        <div class="card !p-0 overflow-hidden border-brand/30 shadow-lg shadow-brand/5">
            <div class="p-4 bg-brand/5 border-b border-brand/10 flex items-center justify-between">
                <div>
                    <h3 class="text-[14px] font-semibold text-brand">Import Results</h3>
                    <p class="text-[11px] text-brand/70"><?= count($newEmployees) ?> employees successfully added</p>
                </div>
                <button type="button" onclick="sendBatchEmail()" class="btn btn-primary !h-8 !px-3 !text-[11px]">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> Send Account Emails
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="data-table !text-[12px]">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" id="select-all" checked class="form-checkbox" onchange="toggleAll(this)">
                            </th>
                            <th>Employee</th>
                            <th>Username</th>
                            <th>Dept / Role</th>
                            <th>Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newEmployees as $emp): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="emp_ids[]" value="<?= $emp['id'] ?>" checked class="emp-checkbox form-checkbox">
                            </td>
                            <td>
                                <div class="font-medium text-txt"><?= htmlspecialchars($emp['full_name']) ?></div>
                                <div class="text-[11px] text-muted"><?= htmlspecialchars($emp['email']) ?></div>
                            </td>
                            <td class="font-mono text-muted"><?= htmlspecialchars($emp['username']) ?></td>
                            <td>
                                <div class="text-txt"><?= htmlspecialchars($emp['dept'] ?? 'N/A') ?></div>
                                <div class="text-[11px] text-muted capitalize"><?= htmlspecialchars($emp['role']) ?></div>
                            </td>
                            <td class="font-medium text-txt">₹<?= number_get_formatted($emp['basic_salary']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card flex flex-col items-center justify-center py-20 text-center border-dashed">
            <div class="w-16 h-16 bg-surface-100 text-muted rounded-full flex items-center justify-center mb-4">
                <i data-lucide="list-checks" class="w-8 h-8"></i>
            </div>
            <h3 class="text-[15px] font-medium text-txt">No active import session</h3>
            <p class="text-sm text-muted mt-1 max-w-xs">Upload a CSV file to see the imported employees and manage account creation.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFileName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('upload-placeholder').classList.add('hidden');
        document.getElementById('file-selected').classList.remove('hidden');
        document.getElementById('file-name').textContent = input.files[0].name;
    }
}

function resetUpload() {
    document.getElementById('csv_file').value = '';
    document.getElementById('upload-placeholder').classList.remove('hidden');
    document.getElementById('file-selected').classList.add('hidden');
}

function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function sendBatchEmail() {
    const selectedIds = Array.from(document.querySelectorAll('.emp-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        alert('Please select at least one employee.');
        return;
    }
    
    if (!confirm(`Send account creation emails to ${selectedIds.length} employees?`)) return;
    
    // Redirect to backend email handler
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= BASE_URL ?>../backend/users/send_welcome_emails.php';
    
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php 
function number_get_formatted($n) {
    return number_format($n, 2);
}
require_once COMPONENTS_PATH . 'footer.php'; 
?>
