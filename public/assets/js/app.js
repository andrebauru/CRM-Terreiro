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
      crmCurrency.code   = window.__crmSettings.currency_code;
      crmCurrency.symbol = window.__crmSettings.currency_symbol || (window.__crmSettings.currency_code === 'BRL' ? 'R$' : '\u00a5');
      crmCurrency.locale = window.__crmSettings.currency_code === 'BRL' ? 'pt-BR' : 'ja-JP';
    }
    if (window.__crmSettings.language) crmLanguage = window.__crmSettings.language;
  }
}

if (typeof crmSymbol === 'undefined') var crmSymbol = function() { return crmCurrency.symbol; };
if (typeof isCurrencyDecimal === 'undefined') var isCurrencyDecimal = function() { return crmCurrency.code === 'BRL'; };

if (typeof _currInt === 'undefined') {
  var _currInt = function(v) {
    var raw = String(v || '');
    if (/^\d+(\.\d+)?$/.test(raw)) return Math.round(parseFloat(raw));
    return parseInt(raw.replace(/\D+/g, '') || '0', 10);
  };
}

if (typeof _groupNum === 'undefined') {
  var _groupNum = function(s) { return s.replace(/\B(?=(\d{3})+(?!\d))/g, isCurrencyDecimal() ? '.' : ','); };
}

if (typeof formatBRL === 'undefined') {
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
}

if (typeof formatBRLOrZero === 'undefined') {
  var formatBRLOrZero = function(v) {
    var n = _currInt(v);
    if (isCurrencyDecimal()) {
      var abs = Math.abs(n), whole = Math.floor(abs / 100), dec = String(abs % 100);
      if (dec.length < 2) dec = '0' + dec;
      return (n < 0 ? '-' : '') + crmCurrency.symbol + '\u00a0' + _groupNum(String(whole)) + ',' + dec;
    }
    return (n < 0 ? '-' : '') + crmCurrency.symbol + _groupNum(String(Math.abs(n)));
  };
}

if (typeof parseBRL === 'undefined') var parseBRL = function(v) { return _currInt(v); };

if (typeof parseCurrencyInput === 'undefined') {
  var parseCurrencyInput = function(str) {
    if (!str) return 0;
    var clean = String(str).replace(/[^\d,\.]/g, '');
    if (isCurrencyDecimal()) {
      if (clean.indexOf(',') >= 0) return Math.round(parseFloat(clean.replace(/\./g, '').replace(',', '.')) * 100);
      return Math.round(parseFloat(clean || '0') * 100);
    }
    return parseInt(clean.replace(/[,\.]/g, '') || '0', 10);
  };
}

if (typeof formatCurrencyInput === 'undefined') {
  var formatCurrencyInput = function(value) {
    var n = _currInt(value);
    if (isCurrencyDecimal()) return (n / 100).toFixed(2).replace('.', ',');
    return String(n);
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
