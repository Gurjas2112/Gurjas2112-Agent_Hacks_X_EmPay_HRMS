<?php
/**
 * EmPay HRMS - NFC Attendance (Public)
 * Example: /nfc_attendance.php?uid=42
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

function renderPage(string $title, string $message, string $name = '', string $status = 'ok'): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $accent = $status === 'ok' ? '#1a4a5e' : '#b42318';

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . $safeTitle . '</title>';
    echo '<style>';
    echo 'body{font-family:Arial, sans-serif;background:#f7f8fb;margin:0;padding:24px;}';
    echo '.card{max-width:420px;margin:40px auto;background:#fff;border:1px solid #e6e8ee;border-radius:10px;padding:20px;box-shadow:0 6px 20px rgba(0,0,0,0.06);}';
    echo '.title{font-size:20px;margin:0 0 8px;color:' . $accent . ';}';
    echo '.msg{font-size:16px;margin:0 0 12px;}';
    echo '.meta{font-size:13px;color:#6b7280;margin:0;}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="card">';
    echo '<h1 class="title">' . $safeTitle . '</h1>';
    echo '<p class="msg">' . $safeMessage . '</p>';
    if ($safeName !== '') {
        echo '<p class="meta">Employee: ' . $safeName . '</p>';
    }
    echo '<p class="meta">' . date('l, d M Y H:i') . '</p>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

$uidRaw = $_GET['uid'] ?? '';
$uid = ctype_digit($uidRaw) ? (int)$uidRaw : 0;
if ($uid <= 0) {
    http_response_code(400);
    renderPage('Attendance Error', 'Invalid or missing employee id.', '', 'error');
    exit;
}

$db = getDBConnection();
if (!$db) {
    http_response_code(500);
    renderPage('Attendance Error', 'Database connection failed.', '', 'error');
    exit;
}

$stmt = $db->prepare('SELECT id, full_name, is_active FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user || (int)$user['is_active'] !== 1) {
    http_response_code(404);
    renderPage('Attendance Error', 'Employee not found or inactive.', '', 'error');
    exit;
}

$today = date('d-m-Y');
$time = date('H:i:s');

$stmt = $db->prepare('SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND date = ?');
$stmt->execute([$uid, $today]);
$attendance = $stmt->fetch();

if ($attendance && $attendance['check_out'] === null) {
    $stmt = $db->prepare('UPDATE attendance SET check_out = ? WHERE id = ?');
    $stmt->execute([$time, $attendance['id']]);
    renderPage('Attendance Updated', 'Checked out at ' . date('h:i A') . '.', $user['full_name']);
    exit;
}

if ($attendance && $attendance['check_out'] !== null) {
    renderPage('Attendance Info', 'Already checked out today.', $user['full_name'], 'error');
    exit;
}

$status = $time > '09:15:00' ? 'late' : 'present';
$stmt = $db->prepare('INSERT INTO attendance (user_id, date, check_in, status, ip_address) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([
    $uid,
    $today,
    $time,
    $status,
    $_SERVER['REMOTE_ADDR'] ?? '',
]);

renderPage('Attendance Updated', 'Checked in at ' . date('h:i A') . '.', $user['full_name']);
