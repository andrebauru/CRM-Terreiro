<?php
$pageTitle = 'CRM Terreiro - Gastos';
$activePage = 'gastos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <!-- Overlay de segurança para print/captura -->
    <div id="printBlockOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;color:#fff;font-size:1.3rem;align-items:center;justify-content:center;text-align:center;backdrop-filter:blur(2px);">
      <div>
        <i class="fa-solid fa-shield-halved" style="font-size:2.5rem;margin-bottom:16px;"></i><br>
        <b>Tentativa de captura, impressão ou cópia detectada.</b><br>
        Por segurança, o conteúdo foi ocultado temporariamente.<br>
        <button onclick="hidePrintBlockOverlay()" style="margin-top:24px;padding:12px 24px;border-radius:8px;background:#dc2626;color:#fff;font-weight:bold;border:none;">Voltar ao conteúdo</button>
      </div>
    </div>
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
          <button onclick="history.back()" class="h-10 w-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors shadow-sm" title="Voltar">
            <i class="fa-solid fa-arrow-left"></i>
          </button>
          <div>
            <h1 class="text-2xl font-bold">Gastos</h1>
            <p class="text-slate-500">Controle de contas a pagar e despesas</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="openContaModal()" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800 text-sm">
            <i class="fa-solid fa-plus mr-1"></i> Novo Gasto
          </button>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Total Pendente</p>
          <p id="cardPendente" class="text-2xl font-black text-red-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Total Pago</p>
          <p id="cardPago" class="text-2xl font-black text-green-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Qtd. Pendente</p>
          <p id="cardQtd" class="text-2xl font-black text-amber-500 mt-1">0</p>
        </div>
      </div>

      <!-- Actions row -->
      <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
        <button onclick="carryOverContas()" class="px-3 py-2 rounded-lg bg-amber-100 text-amber-700 font-bold hover:bg-amber-200 text-sm" title="Mover contas vencidas para o próximo mês">
          <i class="fa-solid fa-arrow-right-arrow-left mr-1"></i> Carry-over Vencidas
        </button>
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-500 font-semibold">Filtro:</label>
          <select id="statusFilter" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm" onchange="loadContas()">
            <option value="">Todos</option>
            <option value="Pendente">Pendente</option>
            <option value="Pago">Pago</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
            <tr>
              <th class="px-3 py-3 text-left">Descrição</th>
              <th class="px-3 py-3 text-left">Fornecedor</th>
              <th class="px-3 py-3 text-left">Categoria</th>
              <th class="px-3 py-3 text-left">Vencimento</th>
              <th class="px-3 py-3 text-left">Parcela</th>
              <th class="px-3 py-3 text-left">Status</th>
              <th class="px-3 py-3 text-right">Valor</th>
              <th class="px-3 py-3 text-right">Pago</th>
              <th class="px-3 py-3"></th>
            </tr>
          </thead>
          <tbody id="contasBody" class="divide-y divide-slate-100"></tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-between items-center mt-4">
        <p id="paginationInfo" class="text-sm text-slate-500"></p>
        <div class="flex gap-2">
          <button id="prevPage" onclick="changePage(-1)" class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 disabled:opacity-40" disabled>
            <i class="fa-solid fa-chevron-left mr-1"></i> Anterior
          </button>
          <button id="nextPage" onclick="changePage(1)" class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 disabled:opacity-40" disabled>
            Próximo <i class="fa-solid fa-chevron-right ml-1"></i>
          </button>
        </div>
      </div>
    </main>
  </div>

  <!-- MODAL GASTO (CONTA A PAGAR) -->
  <div id="modalConta" class="fixed inset-0 bg-black/60 hidden z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 id="modalContaTitulo" class="text-lg font-bold">Novo Gasto</h2>
        <button onclick="closeContaModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="formConta" class="p-6 space-y-4" onsubmit="submitConta(event)">
        <input type="hidden" id="contaId" />
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Descrição *</label>
          <input id="contaDescricao" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Aluguel, Energia..." />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Valor (<?= $_crmCurrSymbol ?>) *</label>
            <input id="contaValor" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?= $_crmCurrSymbol ?>0" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Categoria</label>
            <div class="flex gap-1">
              <select id="contaCategoria" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Selecione...</option>
              </select>
              <button type="button" onclick="openCategoriaModal()" class="px-2 py-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 text-sm" title="Gerenciar categorias">
                <i class="fa-solid fa-gear"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Fornecedor</label>
            <input id="contaFornecedor" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Nome do fornecedor" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Vencimento *</label>
            <input id="contaVencimento" type="date" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Recorrência</label>
            <select id="contaRecorrencia" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
              <option value="nenhuma">Nenhuma</option>
              <option value="mensal">Mensal</option>
              <option value="bimestral">Bimestral</option>
              <option value="trimestral">Trimestral</option>
              <option value="semestral">Semestral</option>
              <option value="anual">Anual</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Parcelamento</label>
            <div class="flex gap-1">
              <input id="contaParcelaNum" type="number" min="0" placeholder="Parcela" class="w-1/2 border border-slate-200 rounded-lg px-2 py-2 text-sm" />
              <span class="self-center text-slate-400 text-sm">/</span>
              <input id="contaParcelaTotal" type="number" min="0" placeholder="Total" class="w-1/2 border border-slate-200 rounded-lg px-2 py-2 text-sm" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
          <button type="button" onclick="closeContaModal()" class="flex-1 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CATEGORIAS -->
  <div id="modalCategoria" class="fixed inset-0 bg-black/60 hidden z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 class="text-lg font-bold">Categorias</h2>
        <button onclick="closeCategoriaModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div class="p-6">
        <div class="flex gap-2 mb-4">
          <input id="novaCategoriaInput" placeholder="Nova categoria..." class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm" />
          <button onclick="addCategoria()" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold text-sm hover:bg-red-800">
            <i class="fa-solid fa-plus"></i>
          </button>
        </div>
        <div id="categoriasLista" class="max-h-48 overflow-y-auto space-y-1"></div>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    function formatBRLCents(cents) {
      return formatBRLOrZero(String(cents || 0));
    }

    function parseCurrency(str) {
      return parseCurrencyInput(str);
    }

    // Real-time currency mask
    document.getElementById('contaValor').addEventListener('input', function () {
      this.value = formatBRL(this.value);
    });

    async function api(params) {
      const isGet = !params.method || params.method === 'GET';
      let url = 'api/financeiro.php?action=' + params.action;
      if (params.query) url += '&' + new URLSearchParams(params.query).toString();
      const opts = isGet ? { method: 'GET' } : { method: 'POST', body: new URLSearchParams(params.body || {}) };
      const r = await fetch(url, opts);
      return r.json();
    }

    function toast(msg, ok = true) {
      const t = document.createElement('div');
      t.className = `fixed bottom-6 right-6 z-[100] px-4 py-3 rounded-xl shadow-lg text-white text-sm font-semibold transition-all ${ok ? 'bg-green-600' : 'bg-red-600'}`;
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3000);
    }

    function confirmDelete(msg, cb) { if (confirm(msg)) cb(); }

    let allContas = [];
    let currentPage = 1;
    const perPage = 15;

    async function loadContas() {
      const d = await api({ action: 'list_contas' });
      if (!d.ok) return;
      allContas = d.data || [];

      // Apply status filter
      const statusFilter = document.getElementById('statusFilter').value;
      let filtered = allContas;
      if (statusFilter) {
        filtered = allContas.filter(c => c.status === statusFilter);
      }

      // Calculate summary
      let totalPendente = 0, totalPago = 0, qtdPendente = 0;
      allContas.forEach(c => {
        if (c.status === 'Pago') {
          totalPago += (c.valor_pago || c.valor || 0);
        } else {
          totalPendente += (c.valor || 0);
          qtdPendente++;
        }
      });
      document.getElementById('cardPendente').textContent = formatBRLCents(totalPendente);
      document.getElementById('cardPago').textContent = formatBRLCents(totalPago);
      document.getElementById('cardQtd').textContent = qtdPendente;

      // Pagination
      const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
      if (currentPage > totalPages) currentPage = totalPages;
      const start = (currentPage - 1) * perPage;
      const pageData = filtered.slice(start, start + perPage);

      // Render
      const body = document.getElementById('contasBody');
      body.innerHTML = pageData.length === 0
        ? '<tr><td colspan="9" class="px-4 py-6 text-center text-slate-400">Nenhum gasto cadastrado</td></tr>'
        : pageData.map(c => {
          const parcelaStr = (c.parcela_num && c.parcela_total) ? `${c.parcela_num}/${c.parcela_total}` : '-';
          const statusCls = c.status === 'Pago' ? 'bg-green-100 text-green-700'
            : (c.status === 'Vencido' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700');
          const valorPago = c.valor_pago ? formatBRLCents(c.valor_pago) : '-';
          return `<tr class="hover:bg-slate-50">
            <td class="px-3 py-3 font-medium">${c.descricao}</td>
            <td class="px-3 py-3 text-slate-500 text-xs">${c.fornecedor || '-'}</td>
            <td class="px-3 py-3 text-slate-500 text-xs">${c.categoria || '-'}</td>
            <td class="px-3 py-3 text-xs">${c.data_vencimento}</td>
            <td class="px-3 py-3 text-xs">${parcelaStr}</td>
            <td class="px-3 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusCls}">${c.status}</span>
            </td>
            <td class="px-3 py-3 text-right font-semibold">${formatBRLCents(c.valor)}</td>
            <td class="px-3 py-3 text-right text-green-600 text-xs">${valorPago}</td>
            <td class="px-3 py-3 text-right">
              <div class="flex gap-1 justify-end flex-wrap">
                ${c.status !== 'Pago' ? `<button onclick="pagarConta(${c.id})" class="px-2 py-1 rounded text-xs bg-green-100 text-green-700 font-bold hover:bg-green-200">Pagar</button>` : ''}
                <button onclick="editConta(${c.id})" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">Editar</button>
                <button onclick="deleteConta(${c.id})" class="px-2 py-1 rounded text-xs bg-red-100 text-red-600 font-bold hover:bg-red-200">Excluir</button>
              </div>
            </td>
          </tr>`;
        }).join('');

      // Cache for edit
      window._contasCache = allContas;

      // Pagination controls
      document.getElementById('paginationInfo').textContent =
        filtered.length > 0 ? `Mostrando ${start + 1}-${Math.min(start + perPage, filtered.length)} de ${filtered.length}` : '';
      document.getElementById('prevPage').disabled = currentPage <= 1;
      document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }

    function changePage(delta) {
      currentPage += delta;
      loadContas();
    }

    function editConta(id) {
      const c = (window._contasCache || []).find(x => x.id == id);
      if (!c) return;
      openContaModal();
      document.getElementById('contaId').value = c.id;
      document.getElementById('modalContaTitulo').textContent = 'Editar Gasto';
      document.getElementById('contaDescricao').value = c.descricao || '';
      document.getElementById('contaCategoria').value = c.categoria || '';
      document.getElementById('contaValor').value = formatBRL(String(c.valor));
      document.getElementById('contaFornecedor').value = c.fornecedor || '';
      document.getElementById('contaVencimento').value = c.data_vencimento || '';
      document.getElementById('contaRecorrencia').value = c.recorrencia || 'nenhuma';
      document.getElementById('contaParcelaNum').value = c.parcela_num || '';
      document.getElementById('contaParcelaTotal').value = c.parcela_total || '';
    }

    function openContaModal() {
      loadCategorias();
      document.getElementById('contaId').value = '';
      document.getElementById('modalContaTitulo').textContent = 'Novo Gasto';
      document.getElementById('formConta').reset();
      document.getElementById('contaVencimento').value = new Date().toISOString().slice(0, 10);
      document.getElementById('modalConta').classList.remove('hidden');
    }

    function closeContaModal() {
      document.getElementById('modalConta').classList.add('hidden');
      document.getElementById('formConta').reset();
    }

    async function submitConta(e) {
      e.preventDefault();
      const id = document.getElementById('contaId').value;
      const action = id ? 'update_conta' : 'create_conta';
      const descricao = document.getElementById('contaDescricao').value;
      const valor = parseCurrency(document.getElementById('contaValor').value);
      const categoria = document.getElementById('contaCategoria').value;
      const vencimento = document.getElementById('contaVencimento').value;
      const fornecedor = document.getElementById('contaFornecedor').value;
      const recorrencia = document.getElementById('contaRecorrencia').value;
      const parcela_num = document.getElementById('contaParcelaNum').value || 0;
      const parcela_total = document.getElementById('contaParcelaTotal').value || 0;
      const body = { descricao, valor, categoria, data_vencimento: vencimento, fornecedor, recorrencia, parcela_num, parcela_total };
      if (id) body.id = id;
      const d = await api({ action, method: 'POST', body });
      if (d.ok) { toast('Gasto salvo!'); closeContaModal(); loadContas(); }
      else toast(d.message || 'Erro ao salvar', false);
    }

    async function pagarConta(id) {
      const valorStr = prompt('Valor pago (deixe em branco para valor total):');
      if (valorStr === null) return;
      const body = { id };
      if (valorStr.trim()) body.valor_pago = parseCurrency(valorStr);
      const d = await api({ action: 'pay_conta', method: 'POST', body });
      if (d.ok) { toast('Pago!'); loadContas(); }
      else toast(d.message || 'Erro', false);
    }

    /* ── Categorias ───────────────────────────── */
    async function loadCategorias() {
      const d = await api({ action: 'list_categorias' });
      if (!d.ok) return;
      const sel = document.getElementById('contaCategoria');
      const prev = sel.value;
      sel.innerHTML = '<option value="">Selecione...</option>';
      d.data.forEach(c => {
        const o = document.createElement('option');
        o.value = c.nome;
        o.textContent = c.nome;
        sel.appendChild(o);
      });
      if (prev) sel.value = prev;
      const lista = document.getElementById('categoriasLista');
      if (lista) {
        lista.innerHTML = d.data.map(c => `
          <div class="flex items-center justify-between bg-slate-50 px-3 py-2 rounded-lg text-sm">
            <span>${c.nome}</span>
            <button onclick="deleteCategoria(${c.id})" class="text-red-500 hover:text-red-700 text-xs font-bold"><i class="fa-solid fa-trash"></i></button>
          </div>`).join('');
      }
    }

    function openCategoriaModal() {
      loadCategorias();
      document.getElementById('modalCategoria').classList.remove('hidden');
    }

    function closeCategoriaModal() {
      document.getElementById('modalCategoria').classList.add('hidden');
    }

    async function addCategoria() {
      const nome = document.getElementById('novaCategoriaInput').value.trim();
      if (!nome) return;
      const d = await api({ action: 'create_categoria', method: 'POST', body: { nome } });
      if (d.ok) { document.getElementById('novaCategoriaInput').value = ''; loadCategorias(); toast('Categoria criada!'); }
      else toast(d.message || 'Erro', false);
    }

    async function deleteCategoria(id) {
      if (!confirm('Excluir esta categoria?')) return;
      const d = await api({ action: 'delete_categoria', method: 'POST', body: { id } });
      if (d.ok) { loadCategorias(); toast('Categoria excluída'); }
      else toast(d.message || 'Erro', false);
    }

    async function carryOverContas() {
      if (!confirm('Mover todas as contas vencidas não-pagas para o próximo mês?')) return;
      const d = await api({ action: 'carry_over', method: 'POST' });
      if (d.ok) {
        toast(`${d.carried || 0} conta(s) movidas para o próximo mês`);
        loadContas();
      } else toast(d.message || 'Erro', false);
    }

    async function deleteConta(id) {
      confirmDelete('Excluir este gasto?', async () => {
        const d = await api({ action: 'delete_conta', method: 'POST', body: { id } });
        if (d.ok) { toast('Gasto excluído'); loadContas(); }
        else toast(d.message || 'Erro', false);
      });
    }

    initSensitivePageProtection('gastos');
    document.addEventListener('DOMContentLoaded', () => {
      loadContas();
    });
  </script>
</body>
</html>
