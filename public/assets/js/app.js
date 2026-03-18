// CRM Terreiro — Global JS Utilities

// Currency settings — initialized synchronously from PHP-embedded data,
// then refreshed asynchronously by loadBrand()
let crmCurrency = { code: 'JPY', symbol: '¥', locale: 'ja-JP' };
let crmLanguage = 'pt';

// Hydrate from PHP-embedded settings (eliminates race condition)
if (window.__crmSettings && typeof window.__crmSettings === 'object') {
  if (window.__crmSettings.currency_code) {
    crmCurrency.code   = window.__crmSettings.currency_code;
    crmCurrency.symbol = window.__crmSettings.currency_symbol
      || (window.__crmSettings.currency_code === 'BRL' ? 'R$' : '¥');
    crmCurrency.locale = window.__crmSettings.currency_code === 'BRL' ? 'pt-BR' : 'ja-JP';
  }
  if (window.__crmSettings.language) {
    crmLanguage = window.__crmSettings.language;
  }
}

// Helper: return current currency symbol (useful for templates)
const crmSymbol = () => crmCurrency.symbol;

// Currency formatting (supports JPY and BRL)
// JPY: stores integer yen (¥150 = 150 in DB)
// BRL: stores integer centavos (R$1,50 = 150 in DB)
const formatBRL = (v) => {
  const n = parseInt(String(v || '').replace(/\D+/g, '') || '0', 10);
  if (!n) return '';
  if (crmCurrency.code === 'BRL') {
    return 'R$\u00a0' + (n / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  return '¥' + n.toLocaleString('ja-JP');
};

// Format with zero shown (for card displays that need to show "¥0" / "R$ 0")
const formatBRLOrZero = (v) => {
  const n = parseInt(String(v || '').replace(/\D+/g, '') || '0', 10);
  if (crmCurrency.code === 'BRL') {
    return 'R$\u00a0' + (n / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  return '¥' + n.toLocaleString('ja-JP');
};

const parseBRL = (v) => parseInt(String(v || '').replace(/\D+/g, '') || '0', 10);

// Parse user input (formatted currency string) to integer for DB storage
// BRL: "1.500,50" → 150050 (centavos)
// JPY: "1,500" → 1500 (yen)
const parseCurrencyInput = (str) => {
  if (!str) return 0;
  const clean = String(str).replace(/[^\d,\.]/g, '');
  if (crmCurrency.code === 'BRL') {
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
  const n = parseInt(String(value || 0).replace(/\D+/g, '') || '0', 10);
  if (crmCurrency.code === 'BRL') {
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
      if (s.currency_code) {
        crmCurrency.code = s.currency_code;
        crmCurrency.symbol = s.currency_symbol || (s.currency_code === 'BRL' ? 'R$' : '¥');
        crmCurrency.locale = s.currency_code === 'BRL' ? 'pt-BR' : 'ja-JP';
      }
      if (s.language) {
        crmLanguage = s.language;
        // Update HTML lang attribute dynamically
        document.documentElement.lang = s.language === 'ja' ? 'ja' : 'pt-BR';
      }
    }
  } catch (e) {}
};

document.addEventListener('DOMContentLoaded', loadBrand);
