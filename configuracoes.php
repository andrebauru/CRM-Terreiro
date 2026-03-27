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
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-slate-700">E-mail para notificações</label>
              <input id="notificationEmail" type="email" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="seu@email.com" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">E-mail remetente (fallback)</label>
              <input id="sendgridFromEmail" type="email" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="no-reply@seu-dominio.com" />
              <p class="text-xs text-slate-500 mt-1">O envio usa <strong>EMAIL_FROM</strong> do .env.</p>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Nome remetente</label>
              <input id="sendgridFromName" type="text" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="CRM Terreiro" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">API Key SendGrid</label>
              <input id="sendgridApiKey" type="password" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="SG.xxxxx" autocomplete="new-password" />
              <p id="sendgridInfo" class="text-xs text-slate-500 mt-1 hidden">API Key já cadastrada. Preencha apenas para substituir.</p>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Porta SMTP</label>
              <input id="sendgridPort" type="number" min="1" max="65535" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="2525" />
              <p class="text-xs text-slate-500 mt-1">2525 = SMTP (smtp.sendgrid.net). 443 (ou vazio) = API v3.</p>
            </div>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-xl bg-accent text-white">Salvar</button>
            <button type="button" id="testSendgridBtn" class="px-4 py-2 rounded-xl border border-emerald-300 text-emerald-700">Testar Configuração</button>
            <a href="api/backup.php" class="px-4 py-2 rounded-xl border border-slate-200">Backup SQL</a>
          </div>
          <div id="sendgridTestResult" class="hidden rounded-xl border px-3 py-2 text-sm"></div>
        </form>
      </section>

      <!-- Logs de prints/cópias -->
      <section class="bg-white border border-amber-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-amber-700">Logs de Prints/Cópias</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Usuário</th>
                <th class="text-left pb-3">Evento</th>
                <th class="text-left pb-3">Página</th>
                <th class="text-left pb-3">IP</th>
                <th class="text-left pb-3">Data</th>
                <th class="text-left pb-3">User Agent</th>
              </tr>
            </thead>
            <tbody id="logsEventosTable">
              <tr><td class="py-3" colspan="6">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="bg-white border border-emerald-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-emerald-700">Logs de E-mail (SendGrid)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Data</th>
                <th class="text-left pb-3">Usuário</th>
                <th class="text-left pb-3">Seção</th>
                <th class="text-left pb-3">Ação</th>
                <th class="text-left pb-3">Status</th>
                <th class="text-left pb-3">Mensagem</th>
                <th class="text-left pb-3">Resposta SendGrid</th>
              </tr>
            </thead>
            <tbody id="sendgridLogsTable">
              <tr><td class="py-3" colspan="7">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
      <script>
        function escapeHtml(value) {
          return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        }

        async function loadLogsEventos() {
          const response = await fetch('api/settings.php?action=get_logs_eventos');
          const data = await response.json();
          const rows = (data.data || []).map(log => `
            <tr class="border-t border-slate-100">
              <td class="py-3">${escapeHtml(log.user_name || '-')}</td>
              <td class="py-3">${escapeHtml(log.event || '-')}</td>
              <td class="py-3">${escapeHtml(log.page || '-')}</td>
              <td class="py-3">${escapeHtml(log.ip || '-')}</td>
              <td class="py-3">${escapeHtml(log.created_at || '-')}</td>
              <td class="py-3 text-xs">${escapeHtml(log.user_agent ? log.user_agent.slice(0,40)+'...' : '-')}</td>
            </tr>
          `).join('');
          document.getElementById('logsEventosTable').innerHTML = rows || '<tr><td class="py-3" colspan="6">Nenhum log encontrado.</td></tr>';
        }

        async function loadSendgridLogs() {
          const response = await fetch('api/settings.php?action=get_sendgrid_logs');
          const data = await response.json();
          const rows = (data.data || []).map(log => `
            <tr class="border-t border-slate-100">
              <td class="py-3">${escapeHtml(log.created_at || '-')}</td>
              <td class="py-3">${escapeHtml(log.user_name || '-')}</td>
              <td class="py-3">${escapeHtml(log.section || '-')}</td>
              <td class="py-3">${escapeHtml(log.action_name || '-')}</td>
              <td class="py-3 ${Number(log.success) === 1 ? 'text-emerald-700' : 'text-red-700'}">${Number(log.success) === 1 ? 'Sucesso' : 'Falha'} (${log.status_code || 0})</td>
              <td class="py-3 text-xs">${escapeHtml((log.message || '-').slice(0, 180))}</td>
              <td class="py-3 text-xs">${escapeHtml((log.provider_response || '-').slice(0, 220))}</td>
            </tr>
          `).join('');
          document.getElementById('sendgridLogsTable').innerHTML = rows || '<tr><td class="py-3" colspan="7">Nenhum log encontrado.</td></tr>';
        }

        document.addEventListener('DOMContentLoaded', () => {
          loadLogsEventos();
          loadSendgridLogs();
        });
      </script>
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
    const notificationEmail = document.getElementById('notificationEmail');
    const sendgridFromEmail = document.getElementById('sendgridFromEmail');
    const sendgridFromName = document.getElementById('sendgridFromName');
    const sendgridPort = document.getElementById('sendgridPort');
    const sendgridApiKey = document.getElementById('sendgridApiKey');
    const sendgridInfo = document.getElementById('sendgridInfo');
    const testSendgridBtn = document.getElementById('testSendgridBtn');
    const sendgridTestResult = document.getElementById('sendgridTestResult');

    const loadSettings = async () => {
      const response = await fetch('api/settings.php?action=get', { cache: 'no-store' });
      const data = await response.json();
      if (!data.ok) return;
      companyName.value = data.data.company_name || 'CRM Terreiro';
      currencyCode.value = data.data.currency_code || 'JPY';
      language.value = data.data.language || 'pt';
      notificationEmail.value = data.data.notification_email || '';
      sendgridFromEmail.value = data.data.sendgrid_from_email || '';
      sendgridFromName.value = data.data.sendgrid_from_name || '';
      sendgridPort.value = data.data.sendgrid_port || '';
      if (data.data.has_sendgrid_api_key) {
        sendgridInfo.classList.remove('hidden');
      }
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
      formData.append('notification_email', notificationEmail.value);
      formData.append('sendgrid_from_email', sendgridFromEmail.value);
      formData.append('sendgrid_from_name', sendgridFromName.value);
      formData.append('sendgrid_port', sendgridPort.value);
      formData.append('sendgrid_api_key', sendgridApiKey.value);
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

    testSendgridBtn.addEventListener('click', async () => {
      testSendgridBtn.disabled = true;
      const originalText = testSendgridBtn.textContent;
      testSendgridBtn.textContent = 'Testando...';
      try {
        const response = await fetch('api/settings.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'test_sendgrid' }),
        });
        const data = await response.json();

        const mode = data?.data?.mode || '-';
        const port = data?.data?.port || '-';
        const statusCode = data?.data?.status_code || 0;
        const providerResponse = (data?.data?.provider_response || '').slice(0, 350);

        sendgridTestResult.classList.remove('hidden');
        sendgridTestResult.className = `rounded-xl border px-3 py-2 text-sm ${data.ok ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-300 bg-red-50 text-red-800'}`;
        sendgridTestResult.innerHTML = `
          <div><strong>${escapeHtml(data.message || (data.ok ? 'Teste enviado com sucesso.' : 'Falha no teste de envio.'))}</strong></div>
          <div class="mt-1">Modo: ${escapeHtml(String(mode))} | Porta: ${escapeHtml(String(port))} | Status: ${escapeHtml(String(statusCode))}</div>
          <div class="mt-1 text-xs">Resposta: ${escapeHtml(providerResponse || '-')}</div>
        `;

        if (typeof loadSendgridLogs === 'function') {
          loadSendgridLogs();
        }
      } catch (error) {
        sendgridTestResult.classList.remove('hidden');
        sendgridTestResult.className = 'rounded-xl border px-3 py-2 text-sm border-red-300 bg-red-50 text-red-800';
        sendgridTestResult.textContent = 'Erro ao testar SendGrid.';
      } finally {
        testSendgridBtn.disabled = false;
        testSendgridBtn.textContent = originalText;
      }
    });

    loadSettings();
  </script>
</body>
</html>