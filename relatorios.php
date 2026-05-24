<?php
$pageTitle = 'CRM Terreiro - Relatórios';
$activePage = 'relatorios';
require_once __DIR__ . '/app/views/partials/tw-head.php';

session_start();
$currentUserRole = $_SESSION['user_role'] ?? 'staff';
$currentUserName = $_SESSION['nome'] ?? $_SESSION['name'] ?? 'Usuário';
$isAdmin = $currentUserRole === 'admin';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
    <!-- MARCA D'ÁGUA DE SEGURANÇA -->
    <style>
        body::before {
            content: '<?= htmlspecialchars($currentUserName) ?>';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 5;
            opacity: 0.08;
            font-size: 4rem;
            font-weight: 300;
            color: rgb(100, 116, 139);
            transform: rotate(-45deg);
            white-space: nowrap;
            overflow: hidden;
            line-height: 1.5;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>

    <!-- Overlay de segurança para print/captura -->
    <div id="printBlockOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;color:#fff;font-size:1.3rem;align-items:center;justify-content:center;text-align:center;backdrop-filter:blur(2px);">
      <div>
        <i class="fa-solid fa-shield-halved" style="font-size:2.5rem;margin-bottom:16px;"></i><br>
        <b>Tentativa de captura, impressão ou cópia detectada.</b><br>
        Por segurança, o conteúdo foi ocultado temporariamente.<br>
        <button onclick="hidePrintBlockOverlay()" style="margin-top:24px;padding:12px 24px;border-radius:8px;background:#dc2626;color:#fff;font-weight:bold;border:none;">Voltar ao conteúdo</button>
      </div>
    </div>
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Relatórios</h1>
          <p class="text-slate-500">Visualize seus dados financeiros e operacionais</p>
        </div>
      </header>

      <!-- Abas de Navegação -->
      <div class="mb-6 border-b border-slate-200">
        <div class="flex gap-4">
          <button 
            id="tab-trabalhos" 
            onclick="switchTab('trabalhos')" 
            class="tab-button px-4 py-3 border-b-2 border-accent text-accent font-medium active"
          >
            <i class="fa-solid fa-briefcase mr-2"></i>Relatórios de Trabalhos
          </button>
          <button 
            id="tab-financeiro" 
            onclick="switchTab('financeiro')" 
            class="tab-button px-4 py-3 border-b-2 border-transparent text-slate-500 font-medium hover:text-slate-700"
          >
            <i class="fa-solid fa-chart-line mr-2"></i>Financeiro
          </button>
        </div>
      </div>

      <!-- TAB: Trabalhos -->
      <div id="content-trabalhos" class="tab-content">
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
            <?php if ($isAdmin): ?>
            <button type="button" id="exportReport" class="px-4 py-2 rounded-xl border border-slate-200">Exportar CSV</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-accent text-white">Filtrar</button>
            <?php else: ?>
            <p class="text-xs text-slate-500">Relatórios de trabalhos são gerados apenas pelo administrador.</p>
            <?php endif; ?>
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
      </div>

      <!-- TAB: Financeiro -->
      <div id="content-financeiro" class="tab-content hidden">
        <!-- Seção de Geração (Admin Only) -->
        <?php if ($isAdmin): ?>
        <section class="bg-blue-50 border border-blue-200 rounded-3xl p-6 shadow-lg mb-6">
          <div class="flex items-start justify-between">
            <div>
              <h3 class="text-lg font-semibold text-blue-900 mb-1">Gerar Relatório Financeiro</h3>
              <p class="text-sm text-blue-700">Consolide os dados financeiros do mês e publique para visualização</p>
            </div>
            <div class="flex gap-2">
              <input 
                type="month" 
                id="generate-month" 
                value="<?= date('Y-m'); ?>"
                class="max-w-xs rounded-lg border border-blue-300 px-3 py-2 text-sm"
              >
              <button 
                onclick="generateFinancialReport()"
                id="generate-btn"
                class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 whitespace-nowrap flex items-center gap-2"
              >
                <i class="fa-solid fa-plus"></i>Gerar e Publicar
              </button>
            </div>
          </div>
          <div id="generate-status" class="hidden mt-4 p-3 rounded-lg text-sm"></div>
        </section>
        <?php endif; ?>

        <!-- Seção de Visualização -->
        <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40 mb-6">
          <div class="mb-6">
            <label class="text-sm font-medium text-slate-700">Selecione o Mês</label>
            <div class="flex gap-4 mt-2">
              <input 
                type="month" 
                id="finance-month" 
                value="<?= date('Y-m'); ?>"
                class="max-w-xs rounded-xl border border-slate-200 px-4 py-2"
              >
              <button 
                onclick="loadFinancialReport()"
                class="px-6 py-2 rounded-xl bg-accent text-white font-medium hover:opacity-90"
              >
                <i class="fa-solid fa-refresh mr-2"></i>Carregar
              </button>
            </div>
          </div>

          <div id="finance-loading" class="hidden text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-accent"></div>
            <p class="text-slate-600 mt-2">Carregando relatório...</p>
          </div>

          <div id="finance-error" class="hidden bg-red-50 border border-red-200 rounded-xl p-4 text-red-700"></div>

          <!-- Estado Vazio -->
          <div id="finance-empty" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum relatório publicado</h3>
            <p class="mt-1 text-sm text-gray-500">Nenhum relatório financeiro foi publicado para este período ainda.</p>
            <?php if ($isAdmin): ?>
            <p class="text-xs text-gray-400 mt-3">Use a seção acima para gerar e publicar um relatório.</p>
            <?php endif; ?>
          </div>

          <!-- Conteúdo Dinâmico -->
          <div id="finance-content" class="space-y-6 hidden"></div>
        </section>
      </div>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
        initSensitivePageProtection('relatorios');

        // ===== VARIÁVEIS GLOBAIS =====
        const isAdmin = <?= json_encode($isAdmin); ?>;
        const isCommonUser = <?= json_encode($currentUserRole === 'user'); ?>;
        const currentUserName = <?= json_encode($currentUserName); ?>;

        // ===== FUNÇÕES GLOBAIS =====

        // Sistema de Abas
        function switchTab(tabName) {
          document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
          document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('border-accent', 'text-accent');
            btn.classList.add('border-transparent', 'text-slate-500');
          });

          document.getElementById(`content-${tabName}`).classList.remove('hidden');
          document.getElementById(`tab-${tabName}`).classList.add('border-accent', 'text-accent');
          document.getElementById(`tab-${tabName}`).classList.remove('border-transparent', 'text-slate-500');
        }

        // Formatador de Moeda
        function formatMoeda(valor) {
          return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }).format(valor);
        }

        // Gerar Relatório (Admin)
        async function generateFinancialReport() {
          if (!isAdmin) return;

          const month = document.getElementById('generate-month').value;
          if (!month) {
            alert('Por favor, selecione um mês');
            return;
          }

          const btn = document.getElementById('generate-btn');
          const statusDiv = document.getElementById('generate-status');
          const originalText = btn.innerHTML;

          btn.disabled = true;
          btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i>Gerando...';
          statusDiv.classList.add('hidden');

          try {
            const response = await fetch('api/generated-reports.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({ action: 'generate', month })
            });

            const data = await response.json();

            if (data.ok) {
              statusDiv.className = 'bg-green-100 border border-green-300 text-green-800 p-3 rounded-lg text-sm';
              statusDiv.textContent = '✓ Relatório gerado com sucesso! Carregue-o acima para visualizar.';
              statusDiv.classList.remove('hidden');
              document.getElementById('finance-month').value = month;
              setTimeout(() => loadFinancialReport(), 1000);
            } else {
              statusDiv.className = 'bg-red-100 border border-red-300 text-red-800 p-3 rounded-lg text-sm';
              statusDiv.textContent = '✗ Erro: ' + (data.message || 'Erro desconhecido');
              statusDiv.classList.remove('hidden');
            }
          } catch (error) {
            statusDiv.className = 'bg-red-100 border border-red-300 text-red-800 p-3 rounded-lg text-sm';
            statusDiv.textContent = '✗ Erro: ' + error.message;
            statusDiv.classList.remove('hidden');
          } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
          }
        }

        // Carregar Relatório Financeiro
        async function loadFinancialReport() {
          const month = document.getElementById('finance-month').value;
          if (!month) {
            alert('Por favor, selecione um mês');
            return;
          }

          const loading = document.getElementById('finance-loading');
          const error = document.getElementById('finance-error');
          const empty = document.getElementById('finance-empty');
          const content = document.getElementById('finance-content');

          loading.classList.remove('hidden');
          error.classList.add('hidden');
          empty.classList.add('hidden');
          content.classList.add('hidden');

          try {
            const response = await fetch(`api/generated-reports.php?action=get&month=${month}`);
            const data = await response.json();

            loading.classList.add('hidden');

            if (!data.ok) {
              if (data.empty) {
                empty.classList.remove('hidden');
              } else {
                error.classList.remove('hidden');
                error.textContent = data.message || 'Erro ao carregar relatório';
              }
              return;
            }

            renderFinancialReport(data.relatorio);
            content.classList.remove('hidden');
          } catch (err) {
            loading.classList.add('hidden');
            error.classList.remove('hidden');
            error.textContent = 'Erro: ' + err.message;
          }
        }

        // Renderizar Relatório Financeiro
        function renderFinancialReport(data) {
          const content = document.getElementById('finance-content');
          const entradas = data.entradas.total;
          const saidas = data.saidas.detalhado || [];
          const acumulado = data.acumulado.saldo_anterior;
          const subtotal = data.subtotal_mes.valor;
          const saldoFinal = data.saldo_final.valor;

          content.innerHTML = `
            <!-- Entradas -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6">
              <h3 class="text-lg font-semibold text-green-900 mb-2">Entradas</h3>
              <p class="text-4xl font-bold text-green-600">${formatMoeda(entradas)}</p>
              <p class="text-sm text-green-700 mt-1">Total de receitas do mês</p>
            </div>

            <!-- Saídas Detalhadas -->
            <div class="bg-white border border-slate-200 rounded-xl p-6">
              <h3 class="text-lg font-semibold text-slate-900 mb-4">Saídas Detalhadas</h3>
              <div class="space-y-2">
                ${saidas.length === 0 
                  ? '<p class="text-slate-500 text-center py-4">Nenhuma saída registrada</p>'
                  : saidas.map(saida => `
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                      <span class="text-slate-700">${saida.descricao}</span>
                      <span class="font-semibold text-red-600">${formatMoeda(saida.valor)}</span>
                    </div>
                  `).join('')
                }
              </div>
              <div class="border-t border-slate-200 mt-4 pt-4">
                <div class="flex justify-between items-center">
                  <span class="font-medium text-slate-700">Total de Saídas</span>
                  <span class="text-2xl font-bold text-red-600">${formatMoeda(saidas.reduce((sum, s) => sum + s.valor, 0))}</span>
                </div>
              </div>
            </div>

            <!-- Saldo Acumulado -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
              <h3 class="text-lg font-semibold text-blue-900 mb-2">Saldo Acumulado</h3>
              <p class="text-3xl font-bold text-blue-600">${formatMoeda(acumulado)}</p>
              <p class="text-sm text-blue-700 mt-1">Saldo de períodos anteriores</p>
            </div>

            <!-- Cards de Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="border rounded-xl p-6 ${data.subtotal_mes.fundo}">
                <h4 class="text-sm font-medium text-slate-700 mb-2">Subtotal do Mês</h4>
                <p class="text-3xl font-bold ${data.subtotal_mes.cor}">${formatMoeda(subtotal)}</p>
              </div>
              <div class="border rounded-xl p-6 ${data.saldo_final.fundo}">
                <h4 class="text-sm font-medium text-slate-700 mb-2">Saldo Final</h4>
                <p class="text-3xl font-bold ${data.saldo_final.cor}">${formatMoeda(saldoFinal)}</p>
              </div>
            </div>
          `;
        }

        // ===== RELATÓRIO DE TRABALHOS (Original) =====
        const reportsTable = document.getElementById('reportsTable');
        const totalSum = document.getElementById('totalSum');
        const reportForm = document.getElementById('reportForm');
        const resetFilters = document.getElementById('resetFilters');
        const serviceFilter = document.getElementById('serviceFilter');
        const statusFilter = document.getElementById('statusFilter');
        const exportReport = document.getElementById('exportReport');
        const sourceFilter = document.getElementById('sourceFilter');
        const gradeFilter = document.getElementById('gradeFilter');

        const formatCurrencyValue = (value) => formatCurrency(String(value || 0));

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
              <td class="py-3 text-right">${formatCurrencyValue(row.total_amount)}</td>
            </tr>
          `);
          
          if (rows.length === 0) {
            // Interface "Lista Vazia"
            const parentSection = reportsTable.closest('section');
            const container = parentSection.querySelector('.overflow-x-auto');
            container.innerHTML = `
              <div class="text-center py-16">
                <div class="flex justify-center mb-4">
                  <svg class="h-16 w-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m0 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900 mb-1">Nenhum relatório disponível</h3>
                <p class="text-slate-500 text-sm mb-4">
                  ${isCommonUser 
                    ? 'Nenhum relatório de trabalhos foi gerado para o período solicitado. Relatórios são gerados pelo administrador.'
                    : 'Nenhum relatório de trabalhos foi gerado para o período solicitado. Crie filtros diferentes e tente novamente.'}
                </p>
                ${!isCommonUser 
                  ? '<p class="text-xs text-slate-400">Use os filtros acima para buscar relatórios existentes.</p>'
                  : ''}
              </div>
            `;
            totalSum.textContent = `Total: ${formatCurrencyValue(0)}`;
          } else {
            reportsTable.innerHTML = rows.join('');
            totalSum.textContent = `Total: ${formatCurrencyValue(data.total || 0)}`;
          }
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

        // ===== INICIALIZAÇÃO =====
        loadServices();
        loadReports();
  </script>
</body>
</html>