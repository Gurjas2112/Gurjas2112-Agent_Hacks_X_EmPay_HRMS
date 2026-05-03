<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/geo_helpers.php';
require_once __DIR__ . '/../auth/login_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = getUserId();
$lat = $_POST['latitude'] ?? null;
$lng = $_POST['longitude'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Location data is missing']);
    exit;
}

$db = getDBConnection();

// Check if already marked today
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
$stmt->execute([$userId, $today]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Attendance already marked for today']);
    exit;
}

// Get Office Location
$officeLat = getSetting('office_lat', 18.5204);
$officeLng = getSetting('office_lng', 73.8567);
$officeRadius = getSetting('office_radius', 50);

// Calculate Distance
$distance = calculateDistance($lat, $lng, $officeLat, $officeLng);
$locationType = ($distance <= $officeRadius) ? 'office' : 'remote';

// Log Attendance
$time = date('H:i:s');
$status = $time > '09:15:00' ? 'late' : 'present';

try {
    $stmt = $db->prepare("INSERT INTO attendance (user_id, date, check_in, status, latitude, longitude, location_type, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $today,
        $time,
        $status,
        $lat,
        $lng,
        $locationType,
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Attendance marked successfully',
        'location_type' => $locationType,
        'distance' => round($distance, 2) . ' meters'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
