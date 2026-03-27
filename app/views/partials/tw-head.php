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

// ── SECURITY: Prevent access after logout (browser back button) ──
// Send HTTP headers that prevent caching of authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// ── SECURITY: Session check - redirect to login if not authenticated ──
$_currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($_currentUserId <= 0) {
    // Allow auto_migrate to run before redirecting
    // But redirect unauthenticated users to login
    $loginUrl = (defined('ROUTE_BASE') ? ROUTE_BASE : '') . '/login';
    header('Location: ' . $loginUrl);
    exit;
}

// ── SECURITY: Page access control ──
// Check if user has permission to access this page
$_userAllowedPages = $_SESSION['user_allowed_pages'] ?? null;
$_userRole = $_SESSION['user_role'] ?? 'user';
if ($_userRole !== 'admin' && $_userAllowedPages !== null && $_userAllowedPages !== '') {
    $__allowedList = array_map('trim', explode(',', $_userAllowedPages));
    $__currentPage = $activePage ?? '';
    // dashboard is always allowed
    if ($__currentPage !== '' && $__currentPage !== 'dashboard' && !in_array($__currentPage, $__allowedList)) {
        header('Location: dashboard.php');
        exit;
    }
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
$_crmCurrentUserLabel = (string)(
  $_SESSION['user_name']
  ?? $_SESSION['user_email']
  ?? ('user#' . $_currentUserId)
);

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
    .crm-watermark-layer{
      position:fixed;
      inset:0;
      pointer-events:none;
      z-index:45;
      opacity:.13;
      display:none;
      user-select:none;
      overflow:hidden;
    }
    .crm-watermark-layer .wm{
      position:absolute;
      color:#dc2626;
      font-weight:700;
      font-size:16px;
      transform:rotate(-26deg);
      white-space:nowrap;
      text-shadow:0 1px 0 rgba(255,255,255,.45);
    }
  </style>
  <script>
    window.__crmSensitiveProtection = window.__crmSensitiveProtection || { boundPages: {} };
    window.__crmCurrentUserLabel = <?= json_encode($_crmCurrentUserLabel, JSON_UNESCAPED_UNICODE) ?>;

    window.initSensitivePageProtection = function initSensitivePageProtection(pageName) {
      const overlay = document.getElementById('printBlockOverlay');
      if (!overlay || !pageName || window.__crmSensitiveProtection.boundPages[pageName]) {
        return;
      }

      const logEvent = (eventName) => {
        try {
          fetch('api/settings.php', {
            method: 'POST',
            body: new URLSearchParams({
              action: 'log_event',
              event: eventName,
              page: pageName,
              user_agent: navigator.userAgent,
            }),
          }).catch(() => {});
        } catch (_) {}
      };

      const showOverlay = (eventName) => {
        overlay.style.display = 'flex';
        logEvent(eventName || 'capture_attempt');
      };

      const mountWatermark = () => {
        const id = 'crmWatermarkLayer';
        let layer = document.getElementById(id);
        if (!layer) {
          layer = document.createElement('div');
          layer.id = id;
          layer.className = 'crm-watermark-layer';
          document.body.appendChild(layer);
        }
        layer.innerHTML = '';

        const user = String(window.__crmCurrentUserLabel || 'user');
        const stamp = new Date().toLocaleString('pt-BR');
        const text = `${pageName} • ${user} • ${stamp}`;

        const cols = 6;
        const rows = 6;
        const stepX = Math.ceil(window.innerWidth / cols);
        const stepY = Math.ceil(window.innerHeight / rows);

        for (let y = 0; y < rows; y++) {
          for (let x = 0; x < cols; x++) {
            const wm = document.createElement('div');
            wm.className = 'wm';
            wm.textContent = text;
            wm.style.left = `${x * stepX - 40}px`;
            wm.style.top = `${y * stepY + 20}px`;
            layer.appendChild(wm);
          }
        }

        layer.style.display = 'block';
      };

      window.hidePrintBlockOverlay = function hidePrintBlockOverlay() {
        overlay.style.display = 'none';
      };

      window.showPrintBlockOverlay = function showPrintBlockOverlay() {
        showOverlay('capture_attempt');
      };

      const isMacShotShortcut = (event) => event.metaKey && event.shiftKey && ['3', '4', '5'].includes(event.key);
      const isWindowsSnippingShortcut = (event) => event.metaKey && event.shiftKey && String(event.key || '').toLowerCase() === 's';
      const isPrintShortcut = (event) => (event.ctrlKey || event.metaKey) && String(event.key || '').toLowerCase() === 'p';
      const isPrintScreenKey = (event) => event.key === 'PrintScreen' || event.code === 'PrintScreen';

      const onKeyCapture = (event) => {
        if (isPrintShortcut(event) || isPrintScreenKey(event) || isMacShotShortcut(event) || isWindowsSnippingShortcut(event)) {
          event.preventDefault();
          showOverlay(isPrintShortcut(event) ? 'print_attempt' : 'screenshot_attempt');
        }
      };

      const onClipboardCapture = (event) => {
        event.preventDefault();
        showOverlay('clipboard_attempt');
      };

      document.addEventListener('keydown', onKeyCapture, true);
      document.addEventListener('keyup', onKeyCapture, true);
      document.addEventListener('copy', onClipboardCapture, true);
      document.addEventListener('cut', onClipboardCapture, true);
      document.addEventListener('contextmenu', onClipboardCapture, true);
      document.addEventListener('dragstart', onClipboardCapture, true);
      document.addEventListener('selectstart', (event) => event.preventDefault(), true);
      window.addEventListener('beforeprint', () => showOverlay('print_attempt'));
      window.addEventListener('resize', mountWatermark);

      const refreshWatermark = () => mountWatermark();
      mountWatermark();
      setInterval(refreshWatermark, 30000);

      if (window.matchMedia) {
        const printMedia = window.matchMedia('print');
        if (printMedia && typeof printMedia.addEventListener === 'function') {
          printMedia.addEventListener('change', (event) => {
            if (event.matches) {
              showOverlay('print_attempt');
            }
          });
        }
      }

      window.__crmSensitiveProtection.boundPages[pageName] = true;
    };
  </script>
  <?= $extraHead ?? '' ?>
</head>
