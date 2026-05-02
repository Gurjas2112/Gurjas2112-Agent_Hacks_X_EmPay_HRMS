<?php
/**
 * EmPay HRMS - Login Check Guard
 * Include this file at the top of any protected page.
 * Redirects to login if the user is not authenticated.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/role_check.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Please log in to access this page.');
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

// Check session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
    destroySession();
    // Restart session for flash message
    session_start();
    setFlash('warning', 'Your session has expired. Please log in again.');
    header('Location: ' . BASE_URL . 'index.php?page=auth/login');
    exit;
}

// Refresh session timestamp
$_SESSION['login_time'] = time();
