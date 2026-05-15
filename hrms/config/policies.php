<?php
/**
 * EmPay HRMS - Policy Resolution Helper
 */

require_once __DIR__ . '/database.php';

/**
 * Resolves the work policy for a given designation_id.
 * Falls back to default if no specific policy exists.
 */
function getWorkPolicy($designation_id) {
    $db = getDBConnection();
    if (!$db) return null;

    $stmt = $db->prepare("SELECT * FROM work_policies WHERE designation_id = ?");
    $stmt->execute([$designation_id]);
    $policy = $stmt->fetch();

    if (!$policy) {
        $stmt = $db->query("SELECT * FROM work_policies WHERE is_default = 1 LIMIT 1");
        $policy = $stmt->fetch();
    }
    return $policy;
}

/**
 * Resolves the leave policy for a given designation_id.
 * Falls back to default if no specific policy exists.
 */
function getLeavePolicy($designation_id) {
    $db = getDBConnection();
    if (!$db) return null;

    $stmt = $db->prepare("SELECT * FROM leave_policies WHERE designation_id = ?");
    $stmt->execute([$designation_id]);
    $policy = $stmt->fetch();

    if (!$policy) {
        $stmt = $db->query("SELECT * FROM leave_policies WHERE is_default = 1 LIMIT 1");
        $policy = $stmt->fetch();
    }
    return $policy;
}
