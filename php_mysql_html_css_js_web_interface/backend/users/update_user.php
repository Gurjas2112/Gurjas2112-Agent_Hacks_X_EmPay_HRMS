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

$userId       = (int)($_POST['user_id'] ?? 0);
$fullName     = trim($_POST['full_name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$username     = trim($_POST['username'] ?? '');
$role         = $_POST['role'] ?? 'employee';
$phone        = trim($_POST['phone'] ?? '');
$gender       = $_POST['gender'] ?? null;
$dob          = $_POST['date_of_birth'] ?: null;
$address      = trim($_POST['address'] ?? '');
$salary       = (float)($_POST['basic_salary'] ?? 0);
$isActive     = (int)($_POST['is_active'] ?? 1);
$departmentId = (int)$_POST['department_id'] ?: null;
$designationId = (int)$_POST['designation_id'] ?: null;
$dateOfJoin   = $_POST['date_of_join'] ?: null;

if ($userId <= 0 || empty($fullName) || empty($email)) {
    setFlash('error', 'Invalid data provided.');
    header('Location: ' . BASE_URL . 'index.php?page=users');
    exit;
}

try {
    $db = getDBConnection();
    $sql = "UPDATE users SET 
            full_name=?, email=?, username=?, role=?, department_id=?, 
            designation_id=?, date_of_join=?, phone=?, gender=?, 
            date_of_birth=?, address=?, basic_salary=?, is_active=? 
            WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $fullName, $email, $username, $role, $departmentId, 
        $designationId, $dateOfJoin, $phone, $gender, 
        $dob, $address, $salary, $isActive, $userId
    ]);
} catch (PDOException $e) {
    error_log("DB Error update user: " . $e->getMessage());
    setFlash('error', 'Database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'index.php?page=users/form&id=' . $userId);
    exit;
}

setFlash('success', 'Employee profile updated successfully.');
header('Location: ' . BASE_URL . 'index.php?page=users');
exit;
