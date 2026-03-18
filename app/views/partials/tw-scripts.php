<?php
// Pre-load currency/language settings so JS has them synchronously (no race condition)
$_crmInlineSettings = null;
try {
    $_dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $_pdo = new PDO($_dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $_stmt = $_pdo->query('SELECT currency_code, currency_symbol, language, company_name, logo_path FROM settings LIMIT 1');
    $_crmInlineSettings = $_stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $_pdo = null;
} catch (Throwable $e) {
    $_crmInlineSettings = null;
}
?>
<script>window.__crmSettings = <?= json_encode($_crmInlineSettings ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/app.js" defer></script>
