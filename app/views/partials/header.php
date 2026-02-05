<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
<meta http-equiv="X-UA-Compatible" content="ie=edge"/>
<title><?= htmlspecialchars($title ?? APP_NAME) ?></title>
<!-- Bootstrap Icons (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Tabler Core CSS -->
<link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler.min.css" rel="stylesheet"/>
<link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler-flags.min.css" rel="stylesheet"/>
<link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler-payments.min.css" rel="stylesheet"/>
<link href="<?= BASE_URL ?>/static/tabler/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
<!-- Custom CSS -->
<link href="<?= BASE_URL ?>/static/css/custom.css" rel="stylesheet"/>
<link href="<?= BASE_URL ?>/static/css/demo.min.css" rel="stylesheet"/>
<!-- Google Fonts -->
<style>
    @import url('https://rsms.me/inter/inter.css');
    :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }
    body {
        font-feature-settings: "cv03", "cv04", "cv11";
    }
</style>
<!-- Tabler Core JS -->
<script src="<?= BASE_URL ?>/static/tabler/dist/js/tabler.min.js" defer></script>
