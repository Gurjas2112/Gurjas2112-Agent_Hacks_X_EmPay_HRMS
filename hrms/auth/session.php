<?php
/**
 * EmPay HRMS - Session Management
 * Start session and provide session helper functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Set user session data after successful login
 */
function setUserSession(array $user): void
{
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['logged_in']  = true;
    $_SESSION['login_time'] = time();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user's role
 */
function getUserRole(): string
{
    return $_SESSION['role'] ?? '';
}

/**
 * Get current user's ID
 */
function getUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Get current user's full name
 */
function getUserName(): string
{
    return $_SESSION['full_name'] ?? 'Guest';
}

/**
 * Get current user's initials (for avatar)
 */
function getUserInitials(): string
{
    $name = getUserName();
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}

/**
 * Destroy user session (logout)
 */
function destroySession(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Set a flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
