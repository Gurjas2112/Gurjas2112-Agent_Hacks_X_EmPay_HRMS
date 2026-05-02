<?php
/**
 * EmPay HRMS - Role-Based Access Control
 * Functions to restrict pages based on user role.
 */

require_once __DIR__ . '/session.php';

/**
 * Check if user has a specific role
 * Redirects to dashboard with error if unauthorized
 */
function requireRole(string ...$roles): void
{
    $currentRole = getUserRole();

    if (!in_array($currentRole, $roles)) {
        setFlash('error', 'Access denied. You do not have permission to view this page.');
        header('Location: ' . BASE_URL . 'index.php?page=dashboard');
        exit;
    }
}

/**
 * Check if current user has admin role
 */
function isAdmin(): bool
{
    return getUserRole() === ROLE_ADMIN;
}

/**
 * Check if current user has HR role
 */
function isHR(): bool
{
    return getUserRole() === ROLE_HR;
}

/**
 * Check if current user has payroll role
 */
function isPayroll(): bool
{
    return getUserRole() === ROLE_PAYROLL;
}

/**
 * Check if current user has employee role
 */
function isEmployee(): bool
{
    return getUserRole() === ROLE_EMPLOYEE;
}

/**
 * Check if user can manage users (admin or HR)
 */
function canManageUsers(): bool
{
    return in_array(getUserRole(), [ROLE_ADMIN, ROLE_HR]);
}

/**
 * Check if user can manage payroll (admin or payroll)
 */
function canManagePayroll(): bool
{
    return in_array(getUserRole(), [ROLE_ADMIN, ROLE_PAYROLL]);
}

/**
 * Check if user can manage leaves (admin or HR)
 */
function canManageLeaves(): bool
{
    return in_array(getUserRole(), [ROLE_ADMIN, ROLE_HR, ROLE_PAYROLL]);
}
