<?php
// Emit CRM settings to JS — uses $_crmSettings loaded by tw-head.php
// Fallback: if not set (edge case), query DB directly
if (!isset($_crmSettings) || empty($_crmSettings)) {
    try {
        if (!function_exists('db')) {
            require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/db.php';
        }
        $_pdo = db();
        $_stmt = $_pdo->query('SELECT currency_code, currency_symbol, language, company_name, logo_path FROM settings LIMIT 1');
        $_crmSettings = $_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $_crmSettings = [];
    }
}
?>
<?php
$_appJsPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/public/assets/js/app.js';
$_appJsVer  = @filemtime($_appJsPath) ?: time();
$_appJsHash = @md5_file($_appJsPath) ?: '';
?>
<script>window.__crmSettings = <?= json_encode($_crmSettings ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/app.js?v=<?= $_appJsVer ?>&h=<?= substr($_appJsHash, 0, 8) ?>"></script>
