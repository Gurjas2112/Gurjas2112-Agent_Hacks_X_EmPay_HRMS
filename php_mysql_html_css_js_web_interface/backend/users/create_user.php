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
if (empty($fullName) || empty($email) || empty($username)) {
    setFlash('error', 'All required fields must be filled.');
    header('Location: ' . BASE_URL . 'index.php?page=users/form');
    exit;
}

$departmentId = (int)$_POST['department_id'] ?: null;
$designation = trim($_POST['designation'] ?? '');
$dateOfJoin = $_POST['date_of_join'] ?? null;

try {
    $db = getDBConnection();
    
    // Check for duplicate email or username
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $stmt->execute([':email' => $email, ':username' => $username]);
    if ($stmt->fetch()) {
        setFlash('error', 'Username or Email already exists.');
        header('Location: ' . BASE_URL . 'index.php?page=users/form');
        exit;
    }

    // Use a temporary password for initial insert
    $tempPassword = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, username, password, role, department_id, designation, date_of_join, phone) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$fullName, $email, $username, $tempPassword, $role, $departmentId, $designation, $dateOfJoin, $phone]);
    
    $newUserId = $db->lastInsertId();
    $empIdPassword = "EMP" . $newUserId . "2002";
    $finalHashedPassword = password_hash($empIdPassword, PASSWORD_DEFAULT);
    
    // Update with pattern-based password (CONCATENATED WITH 2002)
    $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$finalHashedPassword, $newUserId]);

    // Send Welcome Email
    require_once __DIR__ . '/../../utils/mailer.php';
    
    $subject = "Welcome to EmPay — Your Account Credentials";
    $body = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2 style='color: #714B67;'>Welcome to the Team, $fullName!</h2>
            <p>We are excited to have you join us. Your employee profile has been successfully created in the EmPay HRMS portal.</p>
            
            <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #714B67; margin: 20px 0;'>
                <p style='margin: 0;'><strong>Account Credentials:</strong></p>
                <p style='margin: 5px 0;'>Username: <code>$username</code></p>
                <p style='margin: 5px 0;'>Password: <code>$empIdPassword</code></p>
                <p style='margin: 10px 0 0 0; font-size: 12px; color: #666;'><em>Please change your password after your first login for better security.</em></p>
            </div>

            <h3>Acknowledgement of Employment</h3>
            <p>This email serves as an official acknowledgement of your onboarding. You can now access the portal to:</p>
            <ul>
                <li>Mark your daily attendance</li>
                <li>View your designation and department details</li>
                <li>Apply for leaves and track status</li>
                <li>Access your monthly payslips</li>
            </ul>
            
            <p>To get started, please visit the portal at: <a href='" . BASE_URL . "'>" . BASE_URL . "</a></p>
            
            <br>
            <p>Best Regards,<br><strong>EmPay Human Resources</strong></p>
        </div>
    ";
    
    sendEmPayEmail($email, $subject, $body, 'hr');

    setFlash('success', 'Employee "' . htmlspecialchars($fullName) . '" created successfully. Credentials sent to ' . $email);
} catch (PDOException $e) {
    error_log("DB Error create user: " . $e->getMessage());
    setFlash('error', 'Database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'index.php?page=users/form');
    exit;
}

header('Location: ' . BASE_URL . 'index.php?page=users');
exit;
