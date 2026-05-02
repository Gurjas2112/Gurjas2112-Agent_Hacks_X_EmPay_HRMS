<?php
/**
 * EmPay HRMS - Register Handler
 * Processes registration form submissions
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=auth/register');
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'employee';

// Validation
$errors = [];
if (empty($fullName)) $errors[] = 'Full name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (empty($username) || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if (!in_array($role, ['admin', 'employee', 'hr', 'payroll'])) $errors[] = 'Invalid role.';

if (!empty($errors)) {
    setFlash('error', implode(' ', $errors));
    header('Location: ' . BASE_URL . 'index.php?page=auth/register');
    exit;
}

/**
 * DATABASE QUERY PLACEHOLDER
 *
 * $db = getDBConnection();
 * 
 * // Check if email/username already exists
 * $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
 * $stmt->execute([':email' => $email, ':username' => $username]);
 * if ($stmt->fetch()) { ... duplicate error ... }
 *
 * // Insert new user
 * $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
 * $stmt = $db->prepare("INSERT INTO users (full_name, email, username, password, role) VALUES (?,?,?,?,?)");
 * $stmt->execute([$fullName, $email, $username, $hashedPassword, $role]);
 */

setFlash('success', 'Account created successfully! Please log in.');
header('Location: ' . BASE_URL . 'index.php?page=auth/login');
exit;
