// Currency formatting (JPY - Iene Japonês)
const formatBRL = (v) => {
  const n = String(v || '').replace(/\D+/g, '');
  if (!n) return '';
  return '¥' + Math.round(parseInt(n, 10) / 100).toLocaleString('ja-JP');
};
const parseBRL = (v) => parseInt(String(v || '').replace(/\D+/g, '') || '0', 10);
const fmtDate = (d) => d ? d.split('T')[0].split('-').reverse().join('/') : '—';
const toggleModal = (el, show) => {
  el.classList.toggle('hidden', !show);
  el.classList.toggle('flex', show);
};

// Load brand name/logo from settings
const loadBrand = async () => {
  try {
    const response = await fetch('api/settings.php?action=get', { cache: 'no-store' });
    const data = await response.json();
    if (data.ok && data.data?.company_name) {
      document.querySelectorAll('#brandName').forEach(el => el.textContent = data.data.company_name);
    }
    if (data.ok && data.data?.logo_path) {
      document.querySelectorAll('#brandLogo').forEach(el => {
        el.innerHTML = `<img src="${data.data.logo_path}" class="h-10 w-10 rounded-xl object-cover" />`;
      });
    }
  } catch (e) {}
};

document.addEventListener('DOMContentLoaded', loadBrand);
