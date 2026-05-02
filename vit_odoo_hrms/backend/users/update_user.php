<?php
/**
 * EmPay HRMS - Update User Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

requireRole(ROLE_ADMIN, ROLE_HR);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

$userId     = (int)($_POST['user_id'] ?? 0);
$fullName   = trim($_POST['full_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$username   = trim($_POST['username'] ?? '');
$role       = $_POST['role'] ?? 'employee';

$phone      = trim($_POST['phone'] ?? '');

if ($userId <= 0 || empty($fullName) || empty($email)) {
    setFlash('error', 'Invalid data provided.');
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

$departmentId = (int)$_POST['department_id'] ?: null;
$designation = trim($_POST['designation'] ?? '');
$dateOfJoin = $_POST['date_of_join'] ?? null;

try {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, username=?, role=?, department_id=?, designation=?, date_of_join=?, phone=? WHERE id=?");
    $stmt->execute([$fullName, $email, $username, $role, $departmentId, $designation, $dateOfJoin, $phone, $userId]);
} catch (PDOException $e) {
    error_log("DB Error update user: " . $e->getMessage());
    setFlash('error', 'Database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'index.php?page=users/form&id=' . $userId);
    exit;
}

setFlash('success', 'Employee updated successfully.');
header('Location: ' . BASE_URL . 'index.php?page=users');
exit;
