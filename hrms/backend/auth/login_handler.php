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

$db = getDBConnection();

// --- FAILOVER DEMO MODE ---
// Ensures demo access even if DB is not yet initialized with correct hashes
$demoUsers = [
    'admin@empay.com' => ['id' => 1, 'full_name' => 'Admin User', 'email' => 'admin@empay.com', 'role' => 'admin', 'password' => 'admin123'],
    'hr@empay.com'    => ['id' => 2, 'full_name' => 'Priya Sharma', 'email' => 'hr@empay.com', 'role' => 'hr', 'password' => 'hr123'],
    'emp@empay.com'   => ['id' => 3, 'full_name' => 'Arjun Mehta', 'email' => 'emp@empay.com', 'role' => 'employee', 'password' => 'emp123'],
    'payroll@empay.com' => ['id' => 4, 'full_name' => 'Vikram Singh', 'email' => 'payroll@empay.com', 'role' => 'payroll', 'password' => 'pay123']
];

if (isset($demoUsers[$email]) && $password === $demoUsers[$email]['password']) {
    setUserSession($demoUsers[$email]);
    setFlash('success', 'Welcome back, ' . $demoUsers[$email]['full_name'] . ' (Demo Mode)!');
    header('Location: ' . BASE_URL . 'index.php?page=dashboard');
    exit;
}
// --------------------------

if (!$db) {
    setFlash('error', 'Database connection failed.');
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        setUserSession($user);
        
        // Update last login
        $updateStmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
        header('Location: ' . BASE_URL . 'index.php?page=dashboard');
        exit;
    }
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    setFlash('error', 'An error occurred during login.');
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

// Invalid credentials
setFlash('error', 'Invalid email or password. Try a demo account.');
header('Location: ' . BASE_URL . 'index.php?page=auth/login');
exit;
