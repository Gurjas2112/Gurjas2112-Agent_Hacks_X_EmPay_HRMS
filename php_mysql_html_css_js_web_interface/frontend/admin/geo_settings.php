<?php
$pageTitle = 'Geo Settings';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/geo_helpers.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

$db = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = $_POST['office_lat'];
    $lng = $_POST['office_lng'];
    $radius = $_POST['office_radius'];

    $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->execute([$lat, 'office_lat']);
    $stmt->execute([$lng, 'office_lng']);
    $stmt->execute([$radius, 'office_radius']);
    
    $message = "Settings updated successfully!";
}

$officeLat = getSetting('office_lat', 18.5204);
$officeLng = getSetting('office_lng', 73.8567);
$officeRadius = getSetting('office_radius', 50);

require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';
?>

<div class="max-w-2xl">
    <h1 class="page-title mb-1">Geo Attendance Settings</h1>
    <p class="caption mb-6">Define your main office location and detection radius</p>

    <?php if ($message): ?>
    <div class="badge-present text-success-text p-3 rounded-lg mb-6 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        <?= $message ?>
    </div>
    <?php endif; ?>

    <div class="card p-6">
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="sidebar-section-heading !p-0 mb-1">Office Latitude</label>
                    <input type="text" name="office_lat" value="<?= $officeLat ?>" class="w-full p-2 border border-surface-200 rounded-lg text-[14px]">
                </div>
                <div>
                    <label class="sidebar-section-heading !p-0 mb-1">Office Longitude</label>
                    <input type="text" name="office_lng" value="<?= $officeLng ?>" class="w-full p-2 border border-surface-200 rounded-lg text-[14px]">
                </div>
            </div>

            <div>
                <label class="sidebar-section-heading !p-0 mb-1">Detection Radius (Meters)</label>
                <input type="number" name="office_radius" value="<?= $officeRadius ?>" class="w-full p-2 border border-surface-200 rounded-lg text-[14px]">
            </div>

            <div class="pt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="mt-8">
        <h2 class="section-heading mb-3">How it works</h2>
        <div class="p-4 bg-surface-100 rounded-xl border border-surface-200">
            <ul class="space-y-2 text-[13px] text-muted list-disc ml-4">
                <li>System uses the <strong>Haversine Formula</strong> to calculate the exact distance between an employee and the office.</li>
                <li>If the distance is within <strong><?= $officeRadius ?> meters</strong>, it's logged as <span class="font-bold">OFFICE</span>.</li>
                <li>Otherwise, it's logged as <span class="font-bold">REMOTE</span>.</li>
                <li>Employees must provide browser location permission for this to work.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
