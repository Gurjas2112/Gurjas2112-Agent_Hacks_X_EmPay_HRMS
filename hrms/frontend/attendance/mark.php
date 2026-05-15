<?php
$pageTitle = 'Mark Attendance';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$db = getDBConnection();
$userId = getUserId();
$date = date('Y-m-d');

// Fetch today's record
$stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$userId, $date]);
$todayRecord = $stmt->fetch();

$checkIn = $todayRecord['check_in'] ?? '--:--';
$checkOut = $todayRecord['check_out'] ?? '--:--';
$status = $todayRecord['status'] ?? 'No Status';
if ($checkIn !== '--:--') $checkIn = date('H:i', strtotime($checkIn));
if ($checkOut !== '--:--') $checkOut = date('H:i', strtotime($checkOut));

$statusClass = match($status) {
    'present' => 'text-success-text',
    'late' => 'text-warning-text',
    'absent' => 'text-danger-text',
    default => 'text-muted'
};

$locationType = $todayRecord['location_type'] ?? null;
$lat = $todayRecord['latitude'] ?? null;
$lng = $todayRecord['longitude'] ?? null;
?>

<div class="max-w-lg mx-auto">
    <h1 class="page-title text-center mb-1">Mark Attendance</h1>
    <p class="caption text-center mb-6"><?= date('l, d M Y') ?></p>

    <!-- Live Clock -->
    <div class="card text-center mb-6">
        <p class="text-[36px] font-medium text-txt" id="live-clock">--:--:--</p>
        <p class="caption mt-1">Current time</p>

        <div class="flex items-center justify-center gap-8 mt-6 mb-6">
            <div><p class="text-[14px] font-medium"><?= $checkIn ?></p><p class="caption">Check in</p></div>
            <div class="w-px h-8 bg-surface-200"></div>
            <div><p class="text-[14px] font-medium <?= $checkOut === '--:--' ? 'text-muted' : '' ?>"><?= $checkOut ?></p><p class="caption">Check out</p></div>
            <div class="w-px h-8 bg-surface-200"></div>
            <div><p class="text-[14px] font-medium <?= $statusClass ?> capitalize"><?= $status ?></p><p class="caption">Status</p></div>
        </div>

        <form action="<?= BASE_URL ?>../backend/attendance/mark_attendance.php" method="POST" class="flex items-center justify-center gap-3">
            <input type="hidden" name="user_id" value="<?= getUserId() ?>">
            <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
            <button type="submit" name="action" value="checkin" class="btn btn-secondary">
                <i data-lucide="log-in" class="w-4 h-4"></i> Check In
            </button>
            <button type="submit" name="action" value="checkout" class="btn btn-danger">
                <i data-lucide="log-out" class="w-4 h-4"></i> Check Out
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-surface-200">
            <button id="geo-btn" onclick="markGeoAttendance()" class="btn btn-primary w-full py-3" <?= $todayRecord ? 'disabled' : '' ?>>
                <i data-lucide="map-pin" class="w-4 h-4"></i> 
                <span id="geo-btn-text">Mark Attendance with Location</span>
            </button>
            
            <?php if ($locationType): ?>
            <div class="mt-3 flex items-center justify-center gap-2">
                <span class="badge <?= $locationType === 'office' ? 'badge-present' : 'badge-draft' ?> capitalize">
                    <i data-lucide="<?= $locationType === 'office' ? 'building' : 'home' ?>" class="w-3 h-3"></i>
                    <?= $locationType ?>
                </span>
                <span class="text-[11px] text-muted"><?= $lat ?>, <?= $lng ?></span>
            </div>
            <?php endif; ?>
            <p id="geo-msg" class="text-[12px] mt-2 hidden"></p>
        </div>
    </div>

    <!-- Week Summary — stat cards -->
    <h2 class="section-heading mb-3">This week</h2>
    <div class="grid grid-cols-5 gap-2">
        <?php
        // Fetch last 5 days
        $startDate = date('Y-m-d', strtotime('-4 days'));
        $stmt = $db->prepare("SELECT date, status FROM attendance WHERE user_id = ? AND date >= ? ORDER BY date ASC");
        $stmt->execute([$userId, $startDate]);
        $weekData = [];
        while ($row = $stmt->fetch()) {
            $weekData[$row['date']] = $row['status'];
        }

        for ($i = 4; $i >= 0; $i--) {
            $dDate = date('Y-m-d', strtotime("-$i days"));
            $dName = date('D', strtotime($dDate));
            $st = $weekData[$dDate] ?? '';
            $bc = match($st) { 'present'=>'badge-present','late'=>'badge-late','absent'=>'badge-absent',default=>'badge-draft' };
            $icon = match($st) { 'present'=>'check-circle-2','late'=>'clock','absent'=>'x-circle',default=>'circle-dashed' };
        ?>
        <div class="stat-card text-center !p-3">
            <p class="text-[11px] font-medium text-muted mb-1"><?= $dName ?></p>
            <i data-lucide="<?= $icon ?>" class="w-5 h-5 mx-auto <?= $st==='present'?'text-success-text':($st==='late'?'text-warning-text':($st==='absent'?'text-danger-text':'text-surface-200')) ?>"></i>
        </div>
        <?php } ?>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('live-clock').textContent = now.toLocaleTimeString('en-GB', {hour12:false});
}
setInterval(updateClock, 1000);
updateClock();

async function markGeoAttendance() {
    const btn = document.getElementById('geo-btn');
    const btnText = document.getElementById('geo-btn-text');
    const msg = document.getElementById('geo-msg');

    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    btn.disabled = true;
    btnText.innerText = "Fetching location...";
    msg.classList.remove('hidden');
    msg.innerText = "Waiting for GPS...";
    msg.className = "text-[12px] mt-2 text-muted";

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const { latitude, longitude } = position.coords;
            btnText.innerText = "Submitting...";
            
            try {
                const formData = new FormData();
                formData.append('latitude', latitude);
                formData.append('longitude', longitude);

                const response = await fetch('<?= BASE_URL ?>../backend/attendance_geo.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    msg.innerText = `${result.message} (${result.location_type})`;
                    msg.className = "text-[12px] mt-2 text-success-text";
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    msg.innerText = result.message;
                    msg.className = "text-[12px] mt-2 text-danger-text";
                    btn.disabled = false;
                    btnText.innerText = "Mark Attendance with Location";
                }
            } catch (error) {
                msg.innerText = "Error submitting attendance";
                msg.className = "text-[12px] mt-2 text-danger-text";
                btn.disabled = false;
                btnText.innerText = "Mark Attendance with Location";
            }
        },
        (error) => {
            msg.innerText = "Permission denied or location unavailable";
            msg.className = "text-[12px] mt-2 text-danger-text";
            btn.disabled = false;
            btnText.innerText = "Mark Attendance with Location";
        },
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
    );
}
</script>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
