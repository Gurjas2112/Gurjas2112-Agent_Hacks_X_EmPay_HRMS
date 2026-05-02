<?php
/**
 * EmPay HRMS - Create User Handler
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

$fullName   = trim($_POST['full_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$username   = trim($_POST['username'] ?? '');
$password   = $_POST['password'] ?? '';
$role       = $_POST['role'] ?? 'employee';
$department = $_POST['department'] ?? '';
$phone      = trim($_POST['phone'] ?? '');

// Validation
if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
    setFlash('error', 'All required fields must be filled.');
    header('Location: ' . BASE_URL . 'index.php?page=users/form');
    exit;
}

$departmentId = (int)$_POST['department_id'] ?: null;
$designation = trim($_POST['designation'] ?? '');
$dateOfJoin = $_POST['date_of_join'] ?? null;

try {
    $db = getDBConnection();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, username, password, role, department_id, designation, date_of_join, phone) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$fullName, $email, $username, $hashedPassword, $role, $departmentId, $designation, $dateOfJoin, $phone]);
} catch (PDOException $e) {
    error_log("DB Error create user: " . $e->getMessage());
    setFlash('error', 'Database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'index.php?page=users/form');
    exit;
}

setFlash('success', 'Employee "' . htmlspecialchars($fullName) . '" created successfully.');
header('Location: ' . BASE_URL . 'index.php?page=users');
exit;
