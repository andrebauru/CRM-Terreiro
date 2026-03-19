<?php
$pageTitle = 'CRM Terreiro - Configurações';
$activePage = 'configuracoes';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Configurações do Sistema</h1>
          <p class="text-slate-500">Atualize o nome do terreiro e a logo</p>
        </div>
      </header>

      <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40 max-w-2xl">
        <form id="settingsForm" class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Nome do Terreiro</label>
            <input id="companyName" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Logo</label>
            <input id="logoInput" type="file" accept="image/*" class="mt-2 w-full" />
            <div class="mt-3" id="logoPreview"></div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Moeda</label>
              <select id="currencyCode" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
                <option value="JPY">¥ Iene Japonês (JPY)</option>
                <option value="BRL">R$ Real Brasileiro (BRL)</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Idioma</label>
              <select id="language" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
                <option value="pt">🇧🇷 Português</option>
                <option value="ja">🇯🇵 日本語</option>
              </select>
            </div>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-xl bg-accent text-white">Salvar</button>
            <a href="api/backup.php" class="px-4 py-2 rounded-xl border border-slate-200">Backup SQL</a>
          </div>
        </form>
      </section>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const settingsForm = document.getElementById('settingsForm');
    const companyName = document.getElementById('companyName');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    const currencyCode = document.getElementById('currencyCode');
    const language = document.getElementById('language');

    const loadSettings = async () => {
      const response = await fetch('api/settings.php?action=get', { cache: 'no-store' });
      const data = await response.json();
      if (!data.ok) return;
      companyName.value = data.data.company_name || 'CRM Terreiro';
      currencyCode.value = data.data.currency_code || 'JPY';
      language.value = data.data.language || 'pt';
      if (data.data.logo_path) {
        logoPreview.innerHTML = `<img src="${data.data.logo_path}" class="h-16 rounded-xl border border-slate-200" />`;
      }
    };

    settingsForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData();
      formData.append('action', 'update');
      formData.append('company_name', companyName.value);
      formData.append('currency_code', currencyCode.value);
      formData.append('language', language.value);
      if (logoInput.files.length) {
        formData.append('logo', logoInput.files[0]);
      }
      const response = await fetch('api/settings.php', { method: 'POST', body: formData });
      const data = await response.json();
      if (!data.ok) {
        alert(data.message || 'Erro ao salvar');
        return;
      }
      if (data.data.logo_path) {
        logoPreview.innerHTML = `<img src="${data.data.logo_path}" class="h-16 rounded-xl border border-slate-200" />`;
      }
      alert('Configurações salvas! A página será recarregada para aplicar as alterações.');
      location.reload();
    });

    loadSettings();
  </script>
</body>
</html>