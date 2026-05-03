<?php
$pageTitle = 'Attendance Map';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/geo_helpers.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';
require_once COMPONENTS_PATH . 'header.php';
require_once COMPONENTS_PATH . 'navbar.php';
require_once COMPONENTS_PATH . 'sidebar.php';

$db = getDBConnection();
$officeLat = getSetting('office_lat', 18.5204) ?: 18.5204;
$officeLng = getSetting('office_lng', 73.8567) ?: 73.8567;
$officeRadius = getSetting('office_radius', 50) ?: 50;

// Fetch today's attendance with location and job details
$stmt = $db->query("
    SELECT a.*, u.full_name, u.role, d.name as dept_name, des.name as job_title 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN designations des ON u.designation_id = des.id
    WHERE a.date = CURRENT_DATE
");
$attendees = $stmt->fetchAll();
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="flex flex-col h-full">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="page-title mb-1">Attendance Map</h1>
            <p class="caption">Real-time location tracking for today's attendance</p>
        </div>
        <div class="flex gap-2">
            <span class="badge badge-present">Office: <?= count(array_filter($attendees, fn($a) => $a['location_type'] === 'office')) ?></span>
            <span class="badge badge-draft">Remote: <?= count(array_filter($attendees, fn($a) => $a['location_type'] === 'remote')) ?></span>
        </div>
    </div>

    <div class="flex-1 grid grid-cols-1 lg:grid-cols-4 gap-6 min-h-[600px]">
        <!-- Data Sidebar -->
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 overflow-y-auto max-h-[600px] flex flex-col">
            <h3 class="text-[15px] font-semibold text-txt mb-4">Location Activity</h3>
            
            <div class="space-y-3 flex-1">
                <?php foreach ($attendees as $emp): 
                    $isOffice = $emp['location_type'] === 'office';
                    $iconColor = $isOffice ? 'text-[#10B981] bg-[#10B981]/10' : 'text-[#714B67] bg-[#714B67]/10';
                    $icon = $isOffice ? 'building-2' : 'home';
                ?>
                <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-surface-100 transition-colors border border-transparent hover:border-surface-200">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $iconColor ?>">
                        <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-bold text-txt truncate"><?= htmlspecialchars($emp['full_name']) ?></p>
                        <p class="text-[11px] text-muted capitalize"><?= htmlspecialchars($emp['location_type'] ?: 'Unknown') ?> • <?= $emp['check_in'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($attendees)): ?>
                    <div class="text-center py-8 text-muted">
                        <i data-lucide="map-pin-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="text-[13px]">No location data today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Map -->
        <div class="lg:col-span-3 rounded-2xl border border-surface-200 overflow-hidden relative shadow-sm bg-[#F8F8F8]">
            <div id="map" class="absolute inset-0 z-0"></div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('map', { zoomControl: false }).setView([<?= $officeLat ?>, <?= $officeLng ?>], 16);
    
    // Add modern zoom control to top-right
    L.control.zoom({ position: 'topright' }).addTo(map);

    // Use Carto Light for the requested old light UI look
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap contributors © CARTO',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // Office Location with a subtle glow
    const officeCircle = L.circle([<?= $officeLat ?>, <?= $officeLng ?>], {
        color: '#714B67',
        fillColor: '#714B67',
        fillOpacity: 0.08,
        weight: 1,
        dashArray: '4, 4',
        radius: <?= $officeRadius ?>
    }).addTo(map).bindPopup(`
        <div style="font-family: 'Outfit', sans-serif; text-align: center; color: #1A1A1A;">
            <b style="color: #714B67; font-size: 14px;">Main Office</b><br>
            <span style="font-size: 11px; color: #6E6C72;">Detection Radius: <?= $officeRadius ?>m</span>
        </div>
    `);

    const attendees = <?= json_encode($attendees) ?>;
    // Auto-fit bounds if markers exist
    const markerGroup = [];
    attendees.forEach(emp => {
        if (emp.latitude && emp.longitude) {
            const isAdmin = emp.role === 'admin';
            const isOffice = emp.location_type === 'office';
            
            let color = isOffice ? '#10B981' : '#714B67'; 
            if (isAdmin) color = '#F59E0B'; // Amber/Gold for Admin

            let shadow = isOffice ? 'rgba(16, 185, 129, 0.3)' : 'rgba(113, 75, 103, 0.3)';
            if (isAdmin) shadow = 'rgba(245, 158, 11, 0.4)';

            const animClass = isAdmin ? 'pulse-amber' : (isOffice ? 'pulse-green' : 'pulse-purple');
            
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `
                    <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                        <div class="${animClass}" style="position: absolute; width: 24px; height: 24px; border-radius: 50%; background: ${color}; opacity: 0.3;"></div>
                        <div style="background:${color}; width:12px; height:12px; border-radius:50%; border:2px solid #FFFFFF; box-shadow: 0 0 10px ${shadow}; z-index: 2;"></div>
                    </div>
                `,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const marker = L.marker([emp.latitude, emp.longitude], {icon: icon}).addTo(map);
            markerGroup.push(marker);
            
            marker.bindPopup(`
                <div class="glass-popup" style="font-family: 'Outfit', sans-serif; min-width: 170px; color: #1A1A1A;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: ${color}; box-shadow: 0 0 8px ${color};"></div>
                        <p style="margin:0; font-size:15px; font-weight:700; letter-spacing: 0.5px;">${emp.full_name}</p>
                    </div>
                    <p style="margin:0 0 10px 16px; font-size:12px; color:#6E6C72; font-weight:500;">${emp.job_title || 'Employee'}</p>
                    
                    <div style="background: rgba(0, 0, 0, 0.03); border-radius: 6px; padding: 10px; border: 1px solid rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size:11px; color:#6E6C72;">Time</span>
                            <span style="font-size:11px; color:#1A1A1A; font-weight: 600;">${emp.check_in}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size:11px; color:#6E6C72;">Dept</span>
                            <span style="font-size:11px; color:#1A1A1A; font-weight: 600;">${emp.dept_name || 'N/A'}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size:11px; color:#6E6C72;">Status</span>
                            <span style="font-size:10px; color:${color}; font-weight: 700; letter-spacing: 1px; background: ${color}15; padding: 2px 6px; border-radius: 4px;">
                                ${(emp.location_type || 'Unknown').toUpperCase()}
                            </span>
                        </div>
                    </div>
                </div>
            `);
        }
    });

    if (markerGroup.length > 0) {
        const group = new L.featureGroup([officeCircle, ...markerGroup]);
        map.fitBounds(group.getBounds().pad(0.1));
    }
</script>

<style>
    /* Premium Leaflet Overrides - Light Mode */
    .leaflet-container {
        background: #F8F8F8 !important;
        font-family: 'Outfit', sans-serif;
    }
    .leaflet-popup-content-wrapper { 
        background: rgba(255, 255, 255, 0.9) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 12px; 
        padding: 0; 
        overflow: hidden; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
    }
    .leaflet-popup-tip {
        background: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .leaflet-popup-content { margin: 16px; }
    
    /* Marker Animations */
    @keyframes pulse {
        0% { transform: scale(0.5); opacity: 0.8; }
        100% { transform: scale(2.5); opacity: 0; }
    }
    .pulse-green { animation: pulse 2s infinite ease-out; }
    .pulse-purple { animation: pulse 2s infinite ease-out; animation-delay: 1s; }
    .pulse-amber { animation: pulse 1.5s infinite ease-out; }
</style>

<?php require_once COMPONENTS_PATH . 'footer.php'; ?>
