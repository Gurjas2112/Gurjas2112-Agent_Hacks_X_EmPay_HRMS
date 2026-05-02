<?php
/**
 * EmPay HRMS - Logout Handler
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/session.php';

destroySession();

session_start();
setFlash('success', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . 'index.php?page=auth/login');
exit;
