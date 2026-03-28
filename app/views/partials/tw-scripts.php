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

// Derive currency code/symbol for inline JS
$_jsCurrCode   = $_crmSettings['currency_code']   ?? 'JPY';
$_jsCurrSymbol = $_crmSettings['currency_symbol']  ?? '¥';
$_jsLocale     = $_jsCurrCode === 'BRL' ? 'pt-BR' : 'ja-JP';
?>
<script>
// ── CRM Terreiro: Critical inline JS (always fresh from PHP) ──
window.__crmSettings = <?= json_encode($_crmSettings ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

// Currency config — hydrated from PHP DB settings (NOT from external JS)
var crmCurrency = { code: '<?= $_jsCurrCode ?>', symbol: '<?= $_jsCurrSymbol ?>', locale: '<?= $_jsLocale ?>' };
var crmLanguage = '<?= $_crmSettings['language'] ?? 'pt' ?>';

var crmSymbol = function() { return crmCurrency.symbol; };
var isCurrencyDecimal = function() { return crmCurrency.code === 'BRL'; };

// Parse raw value to integer
var _currInt = function(v) {
  var raw = String(v || '');
  if (/^\d+(\.\d+)?$/.test(raw)) return Math.round(parseFloat(raw));
  return parseInt(raw.replace(/\D+/g, '') || '0', 10);
};

// Locale-independent thousand grouping
var _groupNum = function(s) {
  return s.replace(/\B(?=(\d{3})+(?!\d))/g, isCurrencyDecimal() ? '.' : ',');
};

// Currency formatting (JPY=integer yen, BRL=integer centavos)
var formatBRL = function(v) {
  var n = _currInt(v);
  if (!n) return '';
  if (isCurrencyDecimal()) {
    var abs = Math.abs(n), whole = Math.floor(abs / 100), dec = String(abs % 100);
    if (dec.length < 2) dec = '0' + dec;
    return (n < 0 ? '-' : '') + crmCurrency.symbol + '\u00a0' + _groupNum(String(whole)) + ',' + dec;
  }
  return (n < 0 ? '-' : '') + crmCurrency.symbol + _groupNum(String(Math.abs(n)));
};

var formatBRLOrZero = function(v) {
  var n = _currInt(v);
  if (isCurrencyDecimal()) {
    var abs = Math.abs(n), whole = Math.floor(abs / 100), dec = String(abs % 100);
    if (dec.length < 2) dec = '0' + dec;
    return (n < 0 ? '-' : '') + crmCurrency.symbol + '\u00a0' + _groupNum(String(whole)) + ',' + dec;
  }
  return (n < 0 ? '-' : '') + crmCurrency.symbol + _groupNum(String(Math.abs(n)));
};

var parseBRL = function(v) { return _currInt(v); };

var parseCurrencyInput = function(str) {
  if (!str) return 0;
  var clean = String(str).replace(/[^\d,\.]/g, '');
  if (isCurrencyDecimal()) {
    if (clean.indexOf(',') >= 0) return Math.round(parseFloat(clean.replace(/\./g, '').replace(',', '.')) * 100);
    return Math.round(parseFloat(clean || '0') * 100);
  }
  return parseInt(clean.replace(/[,\.]/g, '') || '0', 10);
};

var formatCurrencyInput = function(value) {
  var n = _currInt(value);
  if (isCurrencyDecimal()) return (n / 100).toFixed(2).replace('.', ',');
  return String(n);
};

var fmtDate = function(d) { return d ? d.split('T')[0].split('-').reverse().join('/') : '\u2014'; };

var toggleModal = function(el, show) {
  el.classList.toggle('hidden', !show);
  el.classList.toggle('flex', show);
  document.body.style.overflow = show ? 'hidden' : '';
};

// Load brand (name/logo only, NOT currency)
var loadBrand = function() {
  fetch('api/settings.php?action=get', { cache: 'no-store' }).then(function(r) { return r.json(); }).then(function(data) {
    if (data.ok && data.data) {
      if (data.data.company_name) document.querySelectorAll('#brandName').forEach(function(el) { el.textContent = data.data.company_name; });
      if (data.data.logo_path) document.querySelectorAll('#brandLogo').forEach(function(el) { el.innerHTML = '<img src="' + data.data.logo_path + '" class="h-10 w-10 rounded-xl object-cover" />'; });
    }
  }).catch(function(){});
};
document.addEventListener('DOMContentLoaded', loadBrand);
</script>
<?php
// Also load external app.js (for backward compat, but inline takes precedence)
$_appJsPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3)) . '/public/assets/js/app.js';
if (file_exists($_appJsPath)):
  $_appJsVer  = @filemtime($_appJsPath) ?: time();
  $_appJsHash = @md5_file($_appJsPath) ?: '';
?>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/app.js?v=<?= $_appJsVer ?>&h=<?= substr($_appJsHash, 0, 8) ?>"></script>
<?php endif; ?>

<script type="module">
import { initializeApp, getApps, getApp } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js";
import { getAnalytics, isSupported as analyticsIsSupported } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-analytics.js";
import {
  getFirestore,
  collection,
  doc,
  addDoc,
  setDoc,
  updateDoc,
  deleteDoc,
  query,
  where,
  orderBy,
  limit,
  onSnapshot,
  serverTimestamp,
  Timestamp
} from "https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js";
import {
  getStorage,
  ref,
  uploadBytesResumable,
  getDownloadURL
} from "https://www.gstatic.com/firebasejs/10.12.5/firebase-storage.js";

const firebaseConfig = {
  apiKey: "AIzaSyCz1NVevA6aOCfIhGypLiV1ZqbyihV8SQw",
  authDomain: "crm-quimbanda-chat.firebaseapp.com",
  projectId: "crm-quimbanda-chat",
  storageBucket: "crm-quimbanda-chat.firebasestorage.app",
  messagingSenderId: "675701723904",
  appId: "1:675701723904:web:1284b4169bf82b9e7c59e5",
  measurementId: "G-QHKS3WVLJ5"
};

try {
  const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
  const db = getFirestore(app);
  const storage = getStorage(app);

  window.firebaseApp = app;
  window.db = db;
  window.storage = storage;
  window.firebaseFns = {
    collection,
    doc,
    addDoc,
    setDoc,
    updateDoc,
    deleteDoc,
    query,
    where,
    orderBy,
    limit,
    onSnapshot,
    serverTimestamp,
    Timestamp,
    ref,
    uploadBytesResumable,
    getDownloadURL
  };

  analyticsIsSupported().then((ok) => {
    if (!ok) return;
    try {
      const analytics = getAnalytics(app);
      window.firebaseAnalytics = analytics;
    } catch (_) {}
  }).catch(() => {});

  window.dispatchEvent(new CustomEvent('firebase-ready'));
} catch (e) {
  console.error('Falha ao inicializar Firebase:', e);
}
</script>
