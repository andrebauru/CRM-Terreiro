<?php
// Auto-bootstrap: garante que BASE_PATH e BASE_URL existam
// (necessário quando carregado por páginas legadas que não passam pelo index.php MVC)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}
if (!defined('BASE_URL')) {
    require_once BASE_PATH . '/app/config.php';
}

// ── Ensure session is active (same cookie as MVC login) ──
require_once BASE_PATH . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    safeSessionStart();
}

// ── Load CRM settings from DB (currency, language, brand) ──
// Available as $_crmSettings, $_crmCurrSymbol, $_crmCurrCode, $_crmLang
// in ALL legacy pages that include tw-head.php
$_crmSettings = [];
try {
    $_pdo = db();
    $_stmt = $_pdo->query('SELECT currency_code, currency_symbol, language, company_name, logo_path FROM settings LIMIT 1');
    $_crmSettings = $_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $_crmSettings = [];
}

// ── Auto-migration: cria tabelas/colunas que faltam (1x por sessão) ──
try {
    require_once BASE_PATH . '/api/auto_migrate.php';
    runAutoMigrate($_pdo ?? db());
} catch (Throwable $e) {
    error_log('[tw-head] AutoMigrate error: ' . $e->getMessage());
}
$_crmCurrCode   = $_crmSettings['currency_code']   ?? 'JPY';
$_crmCurrSymbol = $_crmSettings['currency_symbol']  ?? '¥';
$_crmLang       = ($_crmSettings['language'] ?? 'pt') === 'ja' ? 'ja' : 'pt-BR';

// $pageTitle - page title (string)
// $extraHead - optional extra head content (string)
?>
<!doctype html>
<html lang="<?= $_crmLang ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title><?= htmlspecialchars($pageTitle ?? 'CRM Terreiro') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet" />
  <!-- Use compiled Tailwind if available, otherwise CDN -->
  <?php
    $_appCssPath = BASE_PATH . '/public/assets/css/app.css';
    if (file_exists($_appCssPath)):
      $_cssVer  = @filemtime($_appCssPath) ?: time();
      $_cssHash = substr(@md5_file($_appCssPath) ?: '', 0, 8);
  ?>
  <link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/css/app.css?v=<?= $_cssVer ?>&h=<?= $_cssHash ?>" />
  <?php else: ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: { accent: '#dc2626' },
        },
      },
    };
  </script>
  <?php endif; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
  <!-- Critical inline CSS: garante sidebar responsiva e layout mesmo se app.css estiver desatualizado -->
  <style>
    /* Sidebar responsive (mobile=fixed hidden, desktop=static visible) */
    .fixed{position:fixed}.inset-y-0{top:0;bottom:0}.left-0{left:0}
    .-translate-x-full{--tw-translate-x:-100%}
    .-translate-x-full,.translate-x-0{transform:translate(var(--tw-translate-x,0),var(--tw-translate-y,0))}
    .translate-x-0{--tw-translate-x:0px}
    .transition-transform{transition-property:transform;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}
    .duration-200{transition-duration:.2s}.ease-in-out{transition-timing-function:cubic-bezier(.4,0,.2,1)}
    /* Z-index layers: FABs(30) < overlay(40) < sidebar(50) < modals(60) < lightbox(70) */
    .z-30{z-index:30}.z-40{z-index:40}.z-50{z-index:50}.z-\[60\]{z-index:60}.z-\[70\]{z-index:70}.z-\[100\]{z-index:100}
    /* Layout flex */
    .min-w-0{min-width:0}.overflow-x-hidden{overflow-x:hidden}.overflow-hidden{overflow:hidden}.overflow-y-auto{overflow-y:auto}
    .flex-1{flex:1 1 0%}.shrink-0{flex-shrink:0}
    .min-h-screen{min-height:100vh}.min-h-dvh{min-height:100dvh}.max-h-dvh{max-height:100dvh}
    .w-64{width:16rem}.w-\[85vw\]{width:85vw}.max-w-64{max-width:16rem}
    .p-4{padding:1rem}.p-6{padding:1.5rem}.pt-16{padding-top:4rem}
    /* Desktop overrides (md: ≥768px) */
    @media(min-width:768px){
      .md\:static{position:static!important}
      .md\:translate-x-0{--tw-translate-x:0px!important;transform:translate(0,0)!important}
      .md\:hidden{display:none!important}
      .md\:p-8{padding:2rem!important}
    }
  </style>
  <?= $extraHead ?? '' ?>
</head>
