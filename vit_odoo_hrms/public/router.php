<?php
/**
 * EmPay HRMS - Simple Page Router
 * Reads `page` param and loads the correct file from /frontend
 */

// Get the requested page (default: dashboard)
$page = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';

// Sanitize: allow only alphanumeric, slashes, underscores, hyphens
$page = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $page);

// Prevent directory traversal
$page = str_replace('..', '', $page);

// Public pages that don't require authentication
$publicPages = [
    'auth/login',
    'auth/register',
];

// Build file path
$filePath = FRONTEND_PATH . $page;

// Check for index.php in directory or direct .php file
if (is_dir($filePath)) {
    $filePath .= '/index.php';
} else {
    $filePath .= '.php';
}

// Verify the file exists
if (file_exists($filePath)) {
    require_once $filePath;
} else {
    // 404 Page
    http_response_code(404);
    require_once __DIR__ . '/../config/app.php';
    $pageTitle = '404 - Page Not Found';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?> | <?= APP_NAME ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>body { font-family: 'Inter', sans-serif; }</style>
    </head>
    <body class="bg-surface-50 text-txt min-h-screen flex items-center justify-center">
        <div class="text-center px-6">
            <div class="mb-4">
                <span class="text-[80px] font-medium text-muted opacity-50">404</span>
            </div>
            <h1 class="page-title mb-2">Page Not Found</h1>
            <p class="caption text-[13px] mb-8 max-w-md mx-auto">The page you're looking for doesn't exist or has been moved.</p>
            <a href="<?= BASE_URL ?>index.php?page=dashboard" class="btn btn-primary">
                <i data-lucide="home" class="w-4 h-4"></i> Go to Dashboard
            </a>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        </script>
    </body>
    </html>
    <?php
}
