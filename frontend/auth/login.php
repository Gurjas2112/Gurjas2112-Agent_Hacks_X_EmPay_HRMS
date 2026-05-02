<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../auth/session.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=dashboard'); exit; }
$pageTitle = 'Sign In';
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
    <div class="w-full max-w-sm px-6">
        
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-[#714B67] mb-4">
                <i data-lucide="hexagon" class="w-6 h-6 text-white"></i>
            </div>
            <h1 class="text-[22px] font-medium text-txt">Welcome to EmPay</h1>
            <p class="text-[13px] text-muted mt-1"><?= APP_TAGLINE ?></p>
        </div>

        <?php if ($flash): 
            $fc = $flash['type'] === 'error' ? 'flash-error' : ($flash['type'] === 'success' ? 'flash-success' : 'flash-warning');
        ?>
        <div class="mb-5 flash-message <?= $fc ?>" role="alert">
            <span class="text-[13px]"><?= htmlspecialchars($flash['message']) ?></span>
        </div>
        <?php endif; ?>

        <!-- Login Card -->
        <div class="card">
            <form action="<?= BASE_URL ?>../backend/auth/login_handler.php" method="POST" class="space-y-4">
                <div>
                    <label for="email" class="form-label block">Email address</label>
                    <input type="email" id="email" name="email" required placeholder="you@company.com" class="form-input">
                </div>
                <div>
                    <label for="password" class="form-label block">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required placeholder="Enter your password" class="form-input pr-9">
                        <button type="button" onclick="togglePw()" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted hover:text-txt" aria-label="Toggle password visibility">
                            <i data-lucide="eye" class="w-4 h-4" id="pw-icon"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[12px]">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-surface-200 text-brand focus:ring-brand/30">
                        <span class="text-muted">Remember me</span>
                    </label>
                    <a href="#" class="link">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-primary w-full justify-center">Sign In</button>
            </form>
        </div>

        <p class="text-center mt-6 text-[12px] text-muted">
            Don't have an account? <a href="<?= BASE_URL ?>index.php?page=auth/register" class="link">Create one</a>
        </p>

        <!-- Demo Accounts -->
        <div class="mt-6 card !p-4">
            <p class="form-label mb-3">Demo accounts</p>
            <div class="grid grid-cols-2 gap-2">
                <button onclick="fillDemo('admin@empay.com','admin123')" class="text-left px-3 py-2 rounded border border-surface-200 hover:bg-surface-50 text-[12px] transition-colors">
                    <span class="block font-medium text-brand">Admin</span>
                    <span class="text-muted">admin@empay.com</span>
                </button>
                <button onclick="fillDemo('hr@empay.com','hr123')" class="text-left px-3 py-2 rounded border border-surface-200 hover:bg-surface-50 text-[12px] transition-colors">
                    <span class="block font-medium text-success-text">HR</span>
                    <span class="text-muted">hr@empay.com</span>
                </button>
                <button onclick="fillDemo('emp@empay.com','emp123')" class="text-left px-3 py-2 rounded border border-surface-200 hover:bg-surface-50 text-[12px] transition-colors">
                    <span class="block font-medium text-warning-text">Employee</span>
                    <span class="text-muted">emp@empay.com</span>
                </button>
                <button onclick="fillDemo('payroll@empay.com','pay123')" class="text-left px-3 py-2 rounded border border-surface-200 hover:bg-surface-50 text-[12px] transition-colors">
                    <span class="block font-medium text-info-text">Payroll</span>
                    <span class="text-muted">payroll@empay.com</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function togglePw() {
            const f = document.getElementById('password');
            const i = document.getElementById('pw-icon');
            if (f.type === 'password') { f.type = 'text'; i.setAttribute('data-lucide','eye-off'); }
            else { f.type = 'password'; i.setAttribute('data-lucide','eye'); }
            lucide.createIcons();
        }
        function fillDemo(e, p) {
            document.getElementById('email').value = e;
            document.getElementById('password').value = p;
        }
    </script>
</body>
</html>
