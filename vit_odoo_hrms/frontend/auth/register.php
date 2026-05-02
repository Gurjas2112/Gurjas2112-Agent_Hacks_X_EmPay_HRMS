<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/session.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=dashboard'); exit; }
$pageTitle = 'Create Account';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= ASSET_URL ?>css/custom.css">
</head>
<body class="bg-surface-50 text-txt min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm px-6 py-10">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-[#714B67] mb-4">
                <i data-lucide="hexagon" class="w-6 h-6 text-white"></i>
            </div>
            <h1 class="text-[22px] font-medium text-txt">Create Account</h1>
            <p class="text-[13px] text-muted mt-1">Join <?= APP_NAME ?></p>
        </div>

        <?php if ($flash): ?>
        <div class="mb-5 flash-message <?= $flash['type']==='error'?'flash-error':'flash-success' ?>" role="alert">
            <span class="text-[13px]"><?= htmlspecialchars($flash['message']) ?></span>
        </div>
        <?php endif; ?>

        <div class="card">
            <p class="caption mb-4">Fields marked * are required</p>
            <form action="<?= BASE_URL ?>../backend/auth/register_handler.php" method="POST" class="space-y-4">
                <div>
                    <label class="form-label block">Full name *</label>
                    <input type="text" name="full_name" required placeholder="John Doe" class="form-input">
                </div>
                <div>
                    <label class="form-label block">Email address *</label>
                    <input type="email" name="email" required placeholder="you@company.com" class="form-input">
                </div>
                <div>
                    <label class="form-label block">Username *</label>
                    <input type="text" name="username" required placeholder="johndoe" class="form-input">
                </div>
                <div>
                    <label class="form-label block">Password *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" class="form-input">
                </div>
                <div>
                    <label class="form-label block">Register as *</label>
                    <select name="role" class="form-input">
                        <option value="hr">HR Officer (Personnel Management)</option>
                        <option value="payroll">Payroll Officer (Finance & Time-off)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Create Account</button>
            </form>
        </div>

        <p class="text-center mt-6 text-[12px] text-muted">
            Already have an account? <a href="<?= BASE_URL ?>index.php?page=auth/login" class="link">Sign in</a>
        </p>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
