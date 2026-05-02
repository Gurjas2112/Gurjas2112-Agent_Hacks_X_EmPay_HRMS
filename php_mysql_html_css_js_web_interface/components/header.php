<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> | <?= APP_NAME ?></title>
    <meta name="description" content="<?= APP_NAME ?> — <?= APP_TAGLINE ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand:    { DEFAULT: '#714B67', light: '#9A7596', dark: '#4C3254', bg: '#F3EEF1' },
                        surface:  { 50: '#F8F8F8', 100: '#F1F1F1', 200: '#E5E5E5' },
                        muted:    '#6E6C72',
                        txt:      '#1A1A1A',
                        success:  { DEFAULT: '#28A745', bg: '#E8F5E9', text: '#2E7D32' },
                        danger:   { DEFAULT: '#DC3545', bg: '#FFEBEE', text: '#C62828' },
                        warning:  { DEFAULT: '#F0AD4E', bg: '#FFF3E0', text: '#E65100' },
                        info:     { DEFAULT: '#017E84', bg: '#E3F2FD', text: '#1565C0' },
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts — Inter 400 & 500 only -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= ASSET_URL ?>css/custom.css">
</head>
<body class="bg-surface-50 text-txt min-h-screen">

<?php
// Flash message display
$flash = getFlash();
if ($flash): 
    $flashClass = match($flash['type']) {
        'success' => 'flash-success',
        'error'   => 'flash-error',
        'warning' => 'flash-warning',
        default   => 'flash-info',
    };
    $flashIcon = match($flash['type']) {
        'success' => 'check-circle-2',
        'error'   => 'alert-circle',
        'warning' => 'alert-triangle',
        default   => 'info',
    };
?>
<div id="flash-message" class="fixed top-4 right-4 z-[9999] max-w-sm" role="alert">
    <div class="flash-message <?= $flashClass ?>">
        <i data-lucide="<?= $flashIcon ?>" class="w-4 h-4 flex-shrink-0"></i>
        <span><?= htmlspecialchars($flash['message']) ?></span>
        <button onclick="this.closest('#flash-message').remove()" class="ml-auto opacity-60 hover:opacity-100" aria-label="Close">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    </div>
</div>
<script>setTimeout(() => { const el = document.getElementById('flash-message'); if(el) el.remove(); }, 5000);</script>
<?php endif; ?>
