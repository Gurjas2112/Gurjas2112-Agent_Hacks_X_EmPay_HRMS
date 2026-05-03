<?php
/**
 * EmPay HRMS - NFC Attendance (Premium UI)
 * High-end "Tap & Go" interface for kiosk deployment.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/geo_helpers.php';

header('Content-Type: text/html; charset=UTF-8');

// Define the landing page for redirection
$idleUrl = 'nfc_attendance.php';

function renderPage(
    string $title,
    string $message,
    string $name = '',
    string $status = 'ok',
    string $checkIn = '',
    string $checkOut = '',
    string $redirectUrl = '',
    int $redirectSeconds = 5
): void {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeCheckIn = htmlspecialchars($checkIn, ENT_QUOTES, 'UTF-8');
    $safeCheckOut = htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8');
    $safeRedirect = htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8');
    
    // Design tokens from EmPay System
    $primary = '#714B67';
    $success = '#2E7D32';
    $danger = '#C62828';
    $bg_color = match($status) {
        'error' => '#FFEBEE',
        'ready' => '#F3EEF1',
        default => '#E8F5E9'
    };
    $accent = match($status) {
        'error' => $danger,
        'ready' => $primary,
        default => $success
    };

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
    if ($safeRedirect !== '') {
        echo '<meta http-equiv="refresh" content="' . $redirectSeconds . ';url=' . $safeRedirect . '">';
    }
    echo '<title>' . $safeTitle . ' | EmPay NFC</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo '<script src="https://unpkg.com/lucide@latest"></script>';
    echo '<style>';
    echo 'body{font-family:"Outfit", sans-serif; background:#F8F8F8; margin:0; display:flex; align-items:center; justify-content:center; height:100vh; overflow:hidden;}';
    echo '.card{width:90%; max-width:440px; background:#fff; border-radius:32px; padding:48px 40px; box-shadow:0 30px 60px rgba(113, 75, 103, 0.1); border:1px solid rgba(0,0,0,0.04); text-align:center; position:relative; overflow:hidden;}';
    echo '.card::before{content:""; position:absolute; top:0; left:0; width:100%; height:8px; background:' . $accent . ';}';
    
    echo '.icon-wrap{width:110px; height:110px; border-radius:50%; background:' . $bg_color . '; color:' . $accent . '; display:flex; align-items:center; justify-content:center; margin:0 auto 32px; position:relative;}';
    
    if ($status === 'ready') {
        echo '.icon-wrap::after{content:""; position:absolute; width:100%; height:100%; border-radius:50%; border:2px solid ' . $primary . '; animation:pulse 2s infinite;}';
    }
    
    echo '@keyframes pulse{0%{transform:scale(1); opacity:0.8;} 100%{transform:scale(1.6); opacity:0;}}';
    
    echo '.title{font-size:32px; font-weight:700; margin:0 0 12px; color:#1A1A1A; letter-spacing:-0.02em;}';
    echo '.msg{font-size:18px; color:#6E6C72; margin:0 0 40px; line-height:1.6; font-weight:400;}';
    echo '.meta-group{background:#FDFDFD; border:1.5px solid #F1F1F1; border-radius:24px; padding:24px; text-align:left; margin-bottom:32px;}';
    echo '.meta-row{display:flex; justify-content:between; align-items:center; margin-bottom:12px;}';
    echo '.meta-row:last-child{margin-bottom:0;}';
    echo '.meta-lbl{font-size:13px; color:#B0ADB5; font-weight:500; text-transform:uppercase; letter-spacing:0.04em; flex:1;}';
    echo '.meta-val{font-size:15px; color:#1A1A1A; font-weight:600;}';
    
    echo '.badge-status{display:inline-flex; align-items:center; gap:6px; padding:6px 16px; border-radius:100px; font-size:12px; font-weight:600; background:' . $bg_color . '; color:' . $accent . '; margin-bottom:20px;}';
    
    echo '.footer-info{font-size:12px; color:#B0ADB5; font-weight:500; letter-spacing:0.02em;}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="card">';
    
    $icon = match($status) {
        'error' => 'alert-circle',
        'ready' => 'nfc',
        default => 'check-circle-2'
    };
    
    echo '<div class="icon-wrap"><i data-lucide="' . $icon . '" style="width:50px; height:50px;"></i></div>';
    
    if ($status !== 'ready' && $status !== 'error') {
        echo '<div class="badge-status"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i> VERIFIED</div>';
    }

    echo '<h1 class="title">' . $safeTitle . '</h1>';
    echo '<p class="msg">' . $safeMessage . '</p>';
    
    if ($safeName !== '' || $safeCheckIn !== '' || $safeCheckOut !== '') {
        echo '<div class="meta-group">';
        if ($safeName !== '') echo '<div class="meta-row"><span class="meta-lbl">Employee</span><span class="meta-val">' . $safeName . '</span></div>';
        if ($safeCheckIn !== '') echo '<div class="meta-row"><span class="meta-lbl">Check In</span><span class="meta-val">' . $safeCheckIn . '</span></div>';
        if ($safeCheckOut !== '') echo '<div class="meta-row"><span class="meta-lbl">Check Out</span><span class="meta-val">' . $safeCheckOut . '</span></div>';
        echo '</div>';
    }
    
    if ($safeRedirect !== '') {
        echo '<p class="footer-info">READY FOR NEXT TAP IN ' . $redirectSeconds . 's</p>';
    } else {
        echo '<p class="footer-info">' . date('l, d F Y') . '</p>';
    }
    
    echo '</div>';
    echo '<script>lucide.createIcons();</script>';
    echo '</body>';
    echo '</html>';
}

// Logic Start
$uidRaw = trim($_GET['uid'] ?? '');

if ($uidRaw === '') {
    renderPage('Tap Your Card', 'Position your ID card near the scanner to record your attendance instantly.', '', 'ready');
    exit;
}

$uid = ctype_digit($uidRaw) ? (int)$uidRaw : 0;
if ($uid <= 0) {
    http_response_code(400);
    renderPage('Invalid Tag', 'This ID tag is not recognized. Please contact your HR department.', '', 'error', '', '', $idleUrl);
    exit;
}

$db = getDBConnection();
if (!$db) {
    http_response_code(500);
    renderPage('System Offline', 'Connectivity issue. Please try again or mark attendance manually.', '', 'error', '', '', $idleUrl);
    exit;
}

$stmt = $db->prepare('SELECT id, full_name, is_active FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user || (int)$user['is_active'] !== 1) {
    http_response_code(404);
    renderPage('Unknown ID', 'This card is not associated with an active employee profile.', '', 'error', '', '', $idleUrl);
    exit;
}

$today = date('Y-m-d');
$time = date('H:i:s');

// Check existing attendance for today
$stmt = $db->prepare('SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND date = ?');
$stmt->execute([$uid, $today]);
$attendance = $stmt->fetch();

// CHECK-OUT LOGIC
if ($attendance && $attendance['check_out'] === null) {
    $checkInText = $attendance['check_in'] ? date('h:i A', strtotime($attendance['check_in'])) : '';
    $checkOutText = date('h:i A');
    
    $stmt = $db->prepare('UPDATE attendance SET check_out = ? WHERE id = ?');
    $stmt->execute([$time, $attendance['id']]);
    
    renderPage('Check-Out Success', 'Goodbye, ' . explode(' ', $user['full_name'])[0] . '! Have a safe trip home.', $user['full_name'], 'ok', $checkInText, $checkOutText, $idleUrl);
    exit;
}

// ALREADY CHECKED OUT
if ($attendance && $attendance['check_out'] !== null) {
    $checkInText = $attendance['check_in'] ? date('h:i A', strtotime($attendance['check_in'])) : '';
    $checkOutText = $attendance['check_out'] ? date('h:i A', strtotime($attendance['check_out'])) : '';
    
    renderPage('Already Logged', 'You have already completed your shifts for today. See you tomorrow!', $user['full_name'], 'error', $checkInText, $checkOutText, $idleUrl);
    exit;
}

// CHECK-IN LOGIC
$status = $time > '09:15:00' ? 'late' : 'present';

// Get Office Location for Map Sync
$officeLat = getSetting('office_lat', 18.5204);
$officeLng = getSetting('office_lng', 73.8567);

$stmt = $db->prepare('INSERT INTO attendance (user_id, date, check_in, status, latitude, longitude, location_type, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([
    $uid,
    $today,
    $time,
    $status,
    $officeLat,
    $officeLng,
    'office',
    $_SERVER['REMOTE_ADDR'] ?? '',
]);

$checkInText = date('h:i A');
renderPage('Check-In Success', 'Good morning, ' . explode(' ', $user['full_name'])[0] . '! Enjoy your productive day.', $user['full_name'], 'ok', $checkInText, '', $idleUrl);
