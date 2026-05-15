<?php
/**
 * EmPay HRMS - Map Data Seeder
 * Generates dummy attendance data with locations for map visualization.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/geo_helpers.php';

$db = getDBConnection();
$today = date('Y-m-d');

// Clear existing today's geo attendance for demo purposes
$db->exec("DELETE FROM attendance WHERE date = '$today'");

// Office Location (Pune, India example from settings)
$officeLat = (float)getSetting('office_lat', 18.5204);
$officeLng = (float)getSetting('office_lng', 73.8567);
$officeRadius = (int)getSetting('office_radius', 50);

// Get all users
$users = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 LIMIT 15")->fetchAll();

$count = 0;
foreach ($users as $user) {
    // Randomize location
    // Inside office (approx 0.0002 deg is roughly 20-30m)
    // Outside office (remote)
    $isRemote = rand(0, 10) > 6;
    
    if ($isRemote) {
        $lat = $officeLat + (rand(-500, 500) / 10000);
        $lng = $officeLng + (rand(-500, 500) / 10000);
        $locType = 'remote';
    } else {
        $lat = $officeLat + (rand(-2, 2) / 10000);
        $lng = $officeLng + (rand(-2, 2) / 10000);
        $locType = 'office';
    }

    $time = date('H:i:s', strtotime('09:00:00') + rand(0, 7200));
    $status = $time > '09:15:00' ? 'late' : 'present';

    $stmt = $db->prepare("INSERT INTO attendance (user_id, date, check_in, status, latitude, longitude, location_type, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user['id'],
        $today,
        $time,
        $status,
        $lat,
        $lng,
        $locType,
        '127.0.0.1'
    ]);
    $count++;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Data Seeded</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Outfit', sans-serif; 
            background: #F8F8F8; 
            color: #1A1A1A;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            position: relative;
            z-index: 10;
        }
        .glass-card { 
            background: #FFFFFF; 
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 40px; 
            border-radius: 24px; 
            text-align: center;
            max-width: 480px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: #F8F8F8;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        h1 { font-size: 28px; font-weight: 600; margin-bottom: 12px; letter-spacing: -0.5px; }
        p { color: #6E6C72; line-height: 1.6; margin-bottom: 16px; font-size: 15px; }
        strong { color: #10B981; font-weight: 600; }
        .btn { 
            background: #714B67; 
            color: #FFFFFF; 
            padding: 14px 28px; 
            text-decoration: none; 
            border-radius: 12px; 
            display: inline-block; 
            margin-top: 16px; 
            font-weight: 600; 
            transition: all 0.2s;
            border: 1px solid transparent;
            box-shadow: 0 4px 12px rgba(113, 75, 103, 0.2);
        }
        .btn:hover { 
            background: #5a3a51; 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(113, 75, 103, 0.3);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <div class="icon-wrapper">
                🗺️
            </div>
            <h1>Data Seeded Successfully</h1>
            <p>Generated <strong><?= $count ?></strong> dummy attendance records with dynamic geo-locations for today.</p>
            <p style="margin-bottom: 30px;">The map will now showcase employees dynamically scattered across the office and remote areas with live pulse animations.</p>
            <a href="index.php?page=attendance/map" class="btn">View Attendance Map</a>
        </div>
    </div>
</body>
</html>
