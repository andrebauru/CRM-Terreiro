// CRM Terreiro — Global JS Utilities

// Currency settings — initialized synchronously from PHP-embedded data.
// loadBrand() only updates brand name/logo, NOT currency (prevents race conditions).
let crmCurrency = { code: 'JPY', symbol: '¥', locale: 'ja-JP' };
let crmLanguage = 'pt';

// Hydrate from PHP-embedded settings (eliminates race condition)
if (window.__crmSettings && typeof window.__crmSettings === 'object') {
  if (window.__crmSettings.currency_code) {
    crmCurrency.code   = window.__crmSettings.currency_code;
    crmCurrency.symbol = window.__crmSettings.currency_symbol || (window.__crmSettings.currency_code === 'BRL' ? 'R$' : '¥');
    crmCurrency.locale = window.__crmSettings.currency_code === 'BRL' ? 'pt-BR' : 'ja-JP';
  }
  if (window.__crmSettings.language) {
    crmLanguage = window.__crmSettings.language;
  }
}

// Helper: return current currency symbol (useful for templates)
const crmSymbol = () => crmCurrency.symbol;

// Debug: log currency config so users can verify
console.log('[CRM] Currency:', crmCurrency.code, crmCurrency.symbol, '| Decimal:', crmCurrency.code === 'BRL');

// Helper: is current currency decimal-based (like BRL centavos)?
const isCurrencyDecimal = () => crmCurrency.code === 'BRL';

// Parse a raw value to integer — handles DB DECIMAL strings like "1500.00"
// and formatted strings like "¥1,500" or "R$ 15,00"
const _currInt = (v) => {
  const raw = String(v || '');
  // Plain numeric string (possibly from DECIMAL column): "1500" or "1500.00"
  if (/^\d+(\.\d+)?$/.test(raw)) return Math.round(parseFloat(raw));
  // Already formatted or has non-digit chars: strip and parse
  return parseInt(raw.replace(/\D+/g, '') || '0', 10);
};

// Locale-independent thousand-grouping (avoids browser locale fallback)
const _groupNum = (s) => s.replace(/\B(?=(\d{3})+(?!\d))/g, isCurrencyDecimal() ? '.' : ',');

// Currency formatting (supports JPY, BRL, and any future currency)
// JPY: stores integer yen (¥150 = 150 in DB)
// BRL: stores integer centavos (R$1,50 = 150 in DB)
const formatBRL = (v) => {
  const n = _currInt(v);
  if (!n) return '';
  if (isCurrencyDecimal()) {
    const abs = Math.abs(n);
    const whole = Math.floor(abs / 100);
    const dec = String(abs % 100).padStart(2, '0');
    return (n < 0 ? '-' : '') + crmCurrency.symbol + '\u00a0' + _groupNum(String(whole)) + ',' + dec;
  }
  return (n < 0 ? '-' : '') + crmCurrency.symbol + _groupNum(String(Math.abs(n)));
};

// Format with zero shown (for card displays that need to show "¥0" / "R$ 0,00")
const formatBRLOrZero = (v) => {
  const n = _currInt(v);
  if (isCurrencyDecimal()) {
    const abs = Math.abs(n);
    const whole = Math.floor(abs / 100);
    const dec = String(abs % 100).padStart(2, '0');
    return (n < 0 ? '-' : '') + crmCurrency.symbol + '\u00a0' + _groupNum(String(whole)) + ',' + dec;
  }
  return (n < 0 ? '-' : '') + crmCurrency.symbol + _groupNum(String(Math.abs(n)));
};

const parseBRL = (v) => _currInt(v);

// Parse user input (formatted currency string) to integer for DB storage
// BRL: "1.500,50" → 150050 (centavos)
// JPY: "1,500" → 1500 (yen)
const parseCurrencyInput = (str) => {
  if (!str) return 0;
  const clean = String(str).replace(/[^\d,\.]/g, '');
  if (isCurrencyDecimal()) {
    if (clean.includes(',')) {
      return Math.round(parseFloat(clean.replace(/\./g, '').replace(',', '.')) * 100);
    }
    return Math.round(parseFloat(clean || '0') * 100);
  }
  // JPY: integer only
  return parseInt(clean.replace(/[,\.]/g, '') || '0', 10);
};

// Format integer from DB back to input field value
// BRL: 150050 → "1500,50"
// JPY: 1500 → "1500"
const formatCurrencyInput = (value) => {
  const n = _currInt(value);
  if (isCurrencyDecimal()) {
    return (n / 100).toFixed(2).replace('.', ',');
  }
  return String(n);
};

const fmtDate = (d) => d ? d.split('T')[0].split('-').reverse().join('/') : '—';

const toggleModal = (el, show) => {
  el.classList.toggle('hidden', !show);
  el.classList.toggle('flex', show);
};

// Load brand name/logo/currency from settings
const loadBrand = async () => {
  try {
    const response = await fetch('api/settings.php?action=get', { cache: 'no-store' });
    const data = await response.json();
    if (data.ok && data.data) {
      const s = data.data;
      if (s.company_name) {
        document.querySelectorAll('#brandName').forEach(el => el.textContent = s.company_name);
      }
      if (s.logo_path) {
        document.querySelectorAll('#brandLogo').forEach(el => {
          el.innerHTML = `<img src="${s.logo_path}" class="h-10 w-10 rounded-xl object-cover" />`;
        });
      }
      // Currency and language are hydrated synchronously from __crmSettings
      // (set by PHP in tw-scripts.php).  We intentionally do NOT refresh them
      // here to avoid a race condition where loadBrand() resolves AFTER the
      // page has already rendered prices with the correct currency.
    }
  } catch (e) {}
};

document.addEventListener('DOMContentLoaded', loadBrand);
