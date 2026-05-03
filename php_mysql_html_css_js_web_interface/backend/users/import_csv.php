<?php
/**
 * EmPay HRMS - CSV Import Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../auth/login_check.php';
require_once __DIR__ . '/../../auth/role_check.php';

requireRole(ROLE_ADMIN, ROLE_HR);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header('Location: ' . BASE_URL . 'index.php?page=users/import');
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    setFlash('error', 'File upload failed.');
    header('Location: ' . BASE_URL . 'index.php?page=users/import');
    exit;
}

$db = getDBConnection();

// Pre-fetch departments and designations for mapping
$depts = $db->query("SELECT id, LOWER(name) as name FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);
$desigs = $db->query("SELECT id, LOWER(name) as name FROM designations")->fetchAll(PDO::FETCH_KEY_PAIR);

$importedIds = [];
$skippedCount = 0;
$existingNames = [];

if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ",");
    $headerMap = array_flip($headers);

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $fullName     = $data[$headerMap['full_name']] ?? '';
        $email        = $data[$headerMap['email']] ?? '';
        $username     = $data[$headerMap['username']] ?? '';
        // ... other fields as before
        $phone        = $data[$headerMap['phone']] ?? '';
        $deptName     = strtolower(trim($data[$headerMap['department']] ?? ''));
        $desigName    = strtolower(trim($data[$headerMap['designation']] ?? ''));
        $role         = $data[$headerMap['role']] ?? 'employee';
        $doj          = $data[$headerMap['date_of_join']] ?? date('Y-m-d');
        $dob          = $data[$headerMap['date_of_birth']] ?? null;
        $gender       = $data[$headerMap['gender']] ?? null;
        $address      = $data[$headerMap['address']] ?? '';
        $salary       = (float)($data[$headerMap['basic_salary']] ?? 0);

        if (empty($email) || empty($username)) {
            $skippedCount++;
            continue;
        }

        // Check if user exists
        $stmt = $db->prepare("SELECT full_name FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $existing = $stmt->fetch();
        if ($existing) {
            $skippedCount++;
            $existingNames[] = $fullName ?: $email;
            continue;
        }

        // Map Dept and Desig
        $deptId = array_search($deptName, $depts) ?: null;
        $desigId = array_search($desigName, $desigs) ?: null;

        // Create user
        try {
            $sql = "INSERT INTO users (
                        full_name, email, username, phone, department_id, 
                        designation_id, role, date_of_join, date_of_birth, 
                        gender, address, basic_salary, password, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            
            $defaultPassword = password_hash('Empay@123', PASSWORD_DEFAULT);
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $fullName, $email, $username, $phone, $deptId,
                $desigId, $role, $doj, $dob, $gender,
                $address, $salary, $defaultPassword
            ]);
            
            $importedIds[] = $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Import Error: " . $e->getMessage());
            $skippedCount++;
        }
    }
    fclose($handle);
}

if (empty($importedIds)) {
    $msg = "All records in the CSV already exist in the system.";
    if (!empty($existingNames)) {
        $msg = "The following employees already exist: " . implode(', ', array_slice($existingNames, 0, 5)) . (count($existingNames) > 5 ? '...' : '');
    }
    setFlash('warning', $msg);
    header('Location: ' . BASE_URL . 'index.php?page=users/import');
} else {
    $successMsg = count($importedIds) . " employees imported successfully.";
    if ($skippedCount > 0) {
        $successMsg .= " $skippedCount records already existed and were skipped.";
    }
    setFlash('success', $successMsg);
    header('Location: ' . BASE_URL . 'index.php?page=users/import&imported=' . implode(',', $importedIds));
}
exit;
