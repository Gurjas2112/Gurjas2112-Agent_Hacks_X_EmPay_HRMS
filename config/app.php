<?php
/**
 * EmPay HRMS - Application Configuration
 * Core constants and application settings
 */

// Application Info
define('APP_NAME', 'EmPay HRMS');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'Human Resource Management System');

// Base URL - adjust based on your environment
define('BASE_URL', '/vit_odoo_hrms/public/');
define('ASSET_URL', '/vit_odoo_hrms/public/assets/');

// Root path
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('AUTH_PATH', ROOT_PATH . 'auth' . DIRECTORY_SEPARATOR);
define('BACKEND_PATH', ROOT_PATH . 'backend' . DIRECTORY_SEPARATOR);
define('FRONTEND_PATH', ROOT_PATH . 'frontend' . DIRECTORY_SEPARATOR);
define('COMPONENTS_PATH', ROOT_PATH . 'components' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');
define('ROLE_HR', 'hr');
define('ROLE_PAYROLL', 'payroll');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Pagination
define('ITEMS_PER_PAGE', 15);

// Date/Time
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Timezone
date_default_timezone_set('Asia/Kolkata');
