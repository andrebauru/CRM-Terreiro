<?php
// Ensure Session helper is loaded
use App\Helpers\Session;

$title = "Login - " . APP_NAME;
$csrfToken = Session::generateCsrfToken(); // Ensure CSRF token is available
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= htmlspecialchars($title) ?></title>
    <!-- Tabler Core -->
    <link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler-payments.min.css" rel="stylesheet"/>
    <link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    <link href="<?= BASE_URL ?>/static/css/demo.min.css" rel="stylesheet"/>
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
    </style>
</head>
<body class=" d-flex flex-column">
<script src="<?= BASE_URL ?>/static/tabler/dist/js/demo-theme.min.js"></script>
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="." class="navbar-brand navbar-brand-autodark"><img src="<?= BASE_URL ?>/static/logo-quimbanda.png" height="64" alt="Logo"></a>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-4">Login to your account</h2>
                <?php $flashSuccess = Session::getFlash('success'); ?>
                <?php if (!empty($flashSuccess)): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($flashSuccess) ?></div>
                <?php endif; ?>
                <?php $flashError = Session::getFlash('error'); ?>
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($flashError) ?></div>
                <?php endif; ?>
                <form action="/login" method="POST" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" autocomplete="off">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">
                            Password
                        </label>
                        <div class="input-group input-group-flat">
                            <input type="password" name="password" class="form-control" placeholder="Your password" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Tabler Core -->
<script src="<?= BASE_URL ?>/static/tabler/dist/js/tabler.min.js" defer></script>
<script src="<?= BASE_URL ?>/static/js/demo.min.js" defer></script>
</body>
</html>
