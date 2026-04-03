// CRM Terreiro — Global JS Utilities (external fallback)
// ══════════════════════════════════════════════════════════════
// IMPORTANT: All critical functions (formatBRL, parseBRL, toggleModal, etc.)
// are now defined INLINE in tw-scripts.php (always fresh from PHP/DB).
// This file runs AFTER the inline script and only fills in any gaps.
// Uses `var` + typeof guards so it never throws on re-declaration.
// ══════════════════════════════════════════════════════════════

if (typeof crmCurrency === 'undefined') {
  var crmCurrency = { code: 'JPY', symbol: '\u00a5', locale: 'ja-JP' };
  var crmLanguage = 'pt';
  if (window.__crmSettings && typeof window.__crmSettings === 'object') {
    if (window.__crmSettings.currency_code) {
      crmCurrency.code = String(window.__crmSettings.currency_code || 'JPY').toUpperCase();
      crmCurrency.symbol = window.__crmSettings.currency_symbol || (crmCurrency.code === 'BRL' ? 'R$' : '\u00a5');
      crmCurrency.locale = crmCurrency.code === 'BRL' ? 'pt-BR' : 'ja-JP';
    }
    if (window.__crmSettings.language) crmLanguage = window.__crmSettings.language;
  }
}

if (typeof crmSymbol === 'undefined') var crmSymbol = function() { return crmCurrency.symbol; };
if (typeof isCurrencyDecimal === 'undefined') var isCurrencyDecimal = function() { return crmCurrency.code === 'BRL'; };

if (typeof _currInt === 'undefined') {
  var _currInt = function(v) {
    var raw = String(v || '');
    if (/^-?\d+(\.\d+)?$/.test(raw)) return Math.round(parseFloat(raw));
    return parseInt(raw.replace(/[^\d-]+/g, '') || '0', 10);
  };
}

if (typeof _groupNum === 'undefined') {
  var _groupNum = function(s) { return s.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
}

if (typeof window.formatCurrency === 'undefined') {
  window.formatCurrency = function(v) {
    var n = _currInt(v);
    if (isCurrencyDecimal()) {
      var abs = Math.abs(n);
      var whole = Math.floor(abs / 100);
      var dec = String(abs % 100).padStart(2, '0');
      return (n < 0 ? '-' : '') + crmSymbol() + '\u00a0' + _groupNum(String(whole)) + ',' + dec;
    }
    return (n < 0 ? '-' : '') + crmSymbol() + _groupNum(String(Math.abs(n)));
  };
}
if (typeof formatCurrency === 'undefined') var formatCurrency = window.formatCurrency;

if (typeof parseCurrencyInput === 'undefined') {
  var parseCurrencyInput = function(str) {
    if (!str) return 0;
    if (isCurrencyDecimal()) {
      var clean = String(str).replace(/[^\d,\.\-]/g, '');
      if (clean === '' || clean === '-' || clean === ',' || clean === '.') return 0;
      if (clean.indexOf(',') >= 0) {
        return Math.round(parseFloat(clean.replace(/\./g, '').replace(',', '.')) * 100);
      }
      return Math.round(parseFloat(clean || '0') * 100);
    }
    return _currInt(str);
  };
}

if (typeof formatCurrencyInput === 'undefined') {
  var formatCurrencyInput = function(value) {
    var n = _currInt(value);
    return window.formatCurrency(n);
  };
}

if (typeof fmtDate === 'undefined') {
  var fmtDate = function(d) { return d ? d.split('T')[0].split('-').reverse().join('/') : '\u2014'; };
}

if (typeof toggleModal === 'undefined') {
  var toggleModal = function(el, show) {
    el.classList.toggle('hidden', !show);
    el.classList.toggle('flex', show);
    document.body.style.overflow = show ? 'hidden' : '';
  };
}

if (typeof loadBrand === 'undefined') {
  var loadBrand = function() {
    fetch('api/settings.php?action=get', { cache: 'no-store' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok && data.data) {
          if (data.data.company_name) {
            document.querySelectorAll('#brandName').forEach(function(el) { el.textContent = data.data.company_name; });
          }
          if (data.data.logo_path) {
            document.querySelectorAll('#brandLogo').forEach(function(el) {
              el.innerHTML = '<img src="' + data.data.logo_path + '" class="h-10 w-10 rounded-xl object-cover" />';
            });
          }
        }
      }).catch(function() {});
  };
  document.addEventListener('DOMContentLoaded', loadBrand);
}
