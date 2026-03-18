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

const parseBRL = (v) => parseInt(String(v || '').replace(/\D+/g, '') || '0', 10);

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
      }
    }
  } catch (e) {}
};

document.addEventListener('DOMContentLoaded', loadBrand);
