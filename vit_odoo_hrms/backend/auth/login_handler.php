<?php
/**
 * EmPay HRMS - Login Handler
 * Processes login form submissions
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

// Get form data
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($email) || empty($password)) {
    setFlash('error', 'Please fill in all fields.');
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

/**
 * DATABASE QUERY PLACEHOLDER
 * In production, you would:
 * 1. Query the database for the user by email
 * 2. Verify the password hash using password_verify()
 * 3. Set session data on success
 *
 * Example:
 * $db = getDBConnection();
 * $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1");
 * $stmt->execute([':email' => $email]);
 * $user = $stmt->fetch();
 *
 * if ($user && password_verify($password, $user['password'])) {
 *     setUserSession($user);
 *     ...
 * }
 */

// Demo: Simulate user lookup for demo accounts
$demoUsers = [
    'admin@empay.com'   => ['id' => 1, 'username' => 'admin',   'full_name' => 'Admin User',    'email' => 'admin@empay.com',   'role' => 'admin',    'password' => 'admin123'],
    'hr@empay.com'      => ['id' => 2, 'username' => 'hruser',  'full_name' => 'Priya Sharma',  'email' => 'hr@empay.com',      'role' => 'hr',       'password' => 'hr123'],
    'emp@empay.com'     => ['id' => 3, 'username' => 'empuser', 'full_name' => 'Arjun Mehta',   'email' => 'emp@empay.com',     'role' => 'employee', 'password' => 'emp123'],
    'payroll@empay.com' => ['id' => 4, 'username' => 'payuser', 'full_name' => 'Vikram Singh',  'email' => 'payroll@empay.com', 'role' => 'payroll',  'password' => 'pay123'],
];

if (isset($demoUsers[$email]) && $demoUsers[$email]['password'] === $password) {
    $user = $demoUsers[$email];
    setUserSession($user);
    setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
    header('Location: ' . BASE_URL . 'index.php?page=dashboard');
    exit;
}

// Invalid credentials
setFlash('error', 'Invalid email or password. Try a demo account.');
header('Location: ' . BASE_URL . 'index.php?page=auth/login');
exit;
