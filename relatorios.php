<?php
$pageTitle = 'CRM Terreiro - Relatórios';
$activePage = 'relatorios';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Relatórios</h1>
          <p class="text-slate-500">Filtre por período, cliente e serviço</p>
        </div>
      </header>

      <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40 mb-6">
        <form id="reportForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Data Início</label>
            <input id="startDate" type="date" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Data Fim</label>
            <input id="endDate" type="date" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Cliente</label>
            <input id="clientName" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Nome do cliente" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Serviço</label>
            <select id="serviceFilter" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2"></select>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Origem</label>
            <select id="sourceFilter" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
              <option value="trabalhos">Trabalhos</option>
              <option value="mensalidades">Mensalidades</option>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Grau</label>
            <select id="gradeFilter" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
              <option value="">Todos</option>
              <option>Iniciação</option>
              <option>1º Grau</option>
              <option>2º Grau</option>
              <option>3º Grau</option>
              <option>Mestre</option>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Status</label>
            <select id="statusFilter" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
              <option value="">Todos</option>
              <option value="paid">Pago</option>
              <option value="delinquent">Inadimplente</option>
              <option value="reversed">Revertido</option>
            </select>
          </div>
          <div class="md:col-span-4 flex justify-end gap-2">
            <button type="button" id="resetFilters" class="px-4 py-2 rounded-xl border border-slate-200">Limpar</button>
            <button type="button" id="exportReport" class="px-4 py-2 rounded-xl border border-slate-200">Exportar CSV</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-accent text-white">Filtrar</button>
          </div>
        </form>
      </section>

      <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Data</th>
                <th class="text-left pb-3">Cliente</th>
                <th class="text-left pb-3">Serviços</th>
                <th class="text-left pb-3">Pagamento</th>
                <th class="text-right pb-3">Total</th>
              </tr>
            </thead>
            <tbody id="reportsTable">
              <tr><td class="py-3" colspan="5">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="mt-4 text-right text-lg font-semibold" id="totalSum">Total: <?= $_crmCurrSymbol ?>0</div>
      </section>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const reportsTable = document.getElementById('reportsTable');
    const totalSum = document.getElementById('totalSum');
    const reportForm = document.getElementById('reportForm');
    const resetFilters = document.getElementById('resetFilters');
    const serviceFilter = document.getElementById('serviceFilter');
    const statusFilter = document.getElementById('statusFilter');
    const exportReport = document.getElementById('exportReport');
    const sourceFilter = document.getElementById('sourceFilter');
    const gradeFilter = document.getElementById('gradeFilter');

    const formatBRLAmt = (value) => formatBRLOrZero(String(value || 0));

    const loadServices = async () => {
      const response = await fetch('api/reports.php?action=bootstrap', { cache: 'no-store' });
      const data = await response.json();
      serviceFilter.innerHTML = '<option value="0">Todos</option>' +
        (data.services || []).map((s) => `<option value="${s.id}">${s.name}</option>`).join('');
    };

    const loadReports = async () => {
      const params = new URLSearchParams({
        action: 'list',
        start: document.getElementById('startDate').value,
        end: document.getElementById('endDate').value,
        name: document.getElementById('clientName').value,
        service_id: serviceFilter.value,
        status: statusFilter.value,
        source: sourceFilter.value,
        grade: gradeFilter.value,
      });

      reportsTable.innerHTML = '<tr><td class="py-3" colspan="5">Carregando...</td></tr>';
      const response = await fetch(`api/reports.php?${params.toString()}`, { cache: 'no-store' });
      const data = await response.json();
      const rows = (data.data || []).map((row) => `
        <tr class="border-t border-slate-100">
          <td class="py-3">${row.date}</td>
          <td class="py-3">${row.client_name}</td>
          <td class="py-3">${row.services || '-'}</td>
          <td class="py-3">${row.payment_type === 'cash' ? 'À Vista' : 'Parcelado'}</td>
          <td class="py-3 text-right">${formatBRLAmt(row.total_amount)}</td>
        </tr>
      `);
      reportsTable.innerHTML = rows.length ? rows.join('') : '<tr><td class="py-3" colspan="5">Nenhum registro.</td></tr>';
      totalSum.textContent = `Total: ${formatBRLAmt(data.total || 0)}`;
    };

    reportForm.addEventListener('submit', (event) => {
      event.preventDefault();
      loadReports();
    });

    resetFilters.addEventListener('click', () => {
      reportForm.reset();
      loadReports();
    });

    exportReport.addEventListener('click', () => {
      const params = new URLSearchParams({
        action: 'export',
        start: document.getElementById('startDate').value,
        end: document.getElementById('endDate').value,
        name: document.getElementById('clientName').value,
        service_id: serviceFilter.value,
        status: statusFilter.value,
        source: sourceFilter.value,
        grade: gradeFilter.value,
      });
      window.location.href = `api/reports.php?${params.toString()}`;
    });

    loadServices();
    loadReports();
  </script>
</body>
</html>