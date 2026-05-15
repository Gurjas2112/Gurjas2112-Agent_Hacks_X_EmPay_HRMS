<?php
/**
 * Haversine formula to calculate distance between two points in meters
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // in meters

    $latFrom = deg2rad($lat1);
    $lngFrom = deg2rad($lng1);
    $latTo = deg2rad($lat2);
    $lngTo = deg2rad($lng2);

    $latDelta = $latTo - $latFrom;
    $lngDelta = $lngTo - $lngFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));

    return $angle * $earthRadius;
}

/**
 * Get system setting
 */
function getSetting($key, $default = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? $res['setting_value'] : $default;
}
