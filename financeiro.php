<?php
$pageTitle = 'CRM Terreiro - Financeiro';
$activePage = 'financeiro';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold">Financeiro</h1>
          <p class="text-slate-500">Controle de caixa, contas e entradas</p>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-500 font-semibold">Mês:</label>
          <input id="mesFilter" type="month" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm" />
        </div>
      </div>

      <div id="dashCards" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Entradas (mês)</p>
          <p id="cardEntradas" class="text-2xl font-black text-green-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Saídas (mês)</p>
          <p id="cardSaidas" class="text-2xl font-black text-red-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Saldo</p>
          <p id="cardSaldo" class="text-2xl font-black text-slate-700 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Crédito Casa</p>
          <p id="cardCredito" class="text-2xl font-black text-amber-500 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Mensalidades Pagas</p>
          <p id="cardMensPagas" class="text-xl font-bold text-green-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Mensalidades Pendentes</p>
          <p id="cardMensPend" class="text-xl font-bold text-amber-500 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Contas Pendentes</p>
          <p id="cardContasPend" class="text-xl font-bold text-red-600 mt-1">0 — <?= $_crmCurrSymbol ?>0</p>
        </div>
      </div>

      <div class="flex gap-1 mb-4 border-b border-slate-200">
        <button onclick="showTab('caixa')"    id="tab-caixa"    class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Caixa</button>
        <button onclick="showTab('contas')"   id="tab-contas"   class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Contas a Pagar</button>
        <button onclick="showTab('entradas')" id="tab-entradas" class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Entradas</button>
        <button onclick="showTab('credito')"  id="tab-credito"  class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Crédito Casa</button>
      </div>

      <section id="pane-caixa" class="tab-pane hidden">
        <div id="caixaSummary" class="grid grid-cols-3 gap-4 mb-4"></div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-left">Origem</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-right">Valor</th>
              </tr>
            </thead>
            <tbody id="caixaBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-contas" class="tab-pane hidden">
        <div class="flex justify-end mb-3">
          <button onclick="openContaModal()" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800 text-sm">
            <i class="fa-solid fa-plus mr-1"></i> Nova Conta
          </button>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-left">Categoria</th>
                <th class="px-4 py-3 text-left">Vencimento</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-right">Valor</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody id="contasBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-entradas" class="tab-pane hidden">
        <div class="flex justify-end mb-3">
          <button onclick="openEntradaModal()" class="px-4 py-2 rounded-lg bg-green-600 text-white font-bold hover:bg-green-700 text-sm">
            <i class="fa-solid fa-plus mr-1"></i> Nova Entrada
          </button>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-left">Origem</th>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-right">Valor</th>
                <th class="px-4 py-3 text-right">Crédito Casa (10%)</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody id="entradasBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-credito" class="tab-pane hidden">
        <p class="text-sm text-slate-500 mb-3">10% de cada entrada é automaticamente reservado como Crédito Casa.</p>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-right">Valor Original</th>
                <th class="px-4 py-3 text-right">% Crédito</th>
                <th class="px-4 py-3 text-right">Valor Crédito</th>
              </tr>
            </thead>
            <tbody id="creditoBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- MODAL CONTA A PAGAR -->
  <div id="modalConta" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 id="modalContaTitulo" class="text-lg font-bold">Nova Conta</h2>
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
            <input id="contaCategoria" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Serviço" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Vencimento *</label>
          <input id="contaVencimento" type="date" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
        </div>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
          <button type="button" onclick="closeContaModal()" class="flex-1 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL ENTRADA -->
  <div id="modalEntrada" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 id="modalEntradaTitulo" class="text-lg font-bold">Nova Entrada</h2>
        <button onclick="closeEntradaModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="formEntrada" class="p-6 space-y-4" onsubmit="submitEntrada(event)">
        <input type="hidden" id="entradaId" />
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Descrição *</label>
          <input id="entradaDescricao" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Doação, Serviço..." />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Valor (<?= $_crmCurrSymbol ?>) *</label>
            <input id="entradaValor" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?= $_crmCurrSymbol ?>0" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Origem</label>
            <select id="entradaOrigem" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
              <option value="manual">Manual</option>
              <option value="mensalidade">Mensalidade</option>
              <option value="trabalho">Trabalho</option>
              <option value="doacao">Doação</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Data *</label>
          <input id="entradaData" type="date" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
        </div>
        <p class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">
          <i class="fa-solid fa-circle-info mr-1"></i>
          10% do valor será automaticamente reservado como Crédito Casa.
        </p>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 py-2 rounded-lg bg-green-600 text-white font-bold hover:bg-green-700">Salvar</button>
          <button type="button" onclick="closeEntradaModal()" class="flex-1 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <style>
    .tab-btn { color: #64748b; border-bottom: 2px solid transparent; }
    .tab-btn.active { color: #dc2626; border-bottom-color: #dc2626; background: #fff1f2; }
  </style>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    function formatBRLCents(cents) {
      return formatBRLOrZero(String(cents || 0));
    }

    function parseCurrency(str) {
      return parseCurrencyInput(str);
    }

    // Real-time currency mask for value inputs
    document.getElementById('contaValor').addEventListener('input', function () {
      this.value = formatBRL(this.value);
    });
    document.getElementById('entradaValor').addEventListener('input', function () {
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

    let currentTab = 'caixa';

    function getMes() {
      return document.getElementById('mesFilter').value || new Date().toISOString().slice(0, 7);
    }

    function showTab(name) {
      currentTab = name;
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.getElementById('pane-' + name).classList.remove('hidden');
      document.getElementById('tab-' + name).classList.add('active');
      loadTab(name);
    }

    function loadTab(name) {
      if (name === 'caixa')    loadCaixa();
      if (name === 'contas')   loadContas();
      if (name === 'entradas') loadEntradas();
      if (name === 'credito')  loadCredito();
    }

    async function loadDashboard() {
      const mes = getMes();
      const d = await api({ action: 'dashboard', query: { mes } });
      if (!d.ok) return;
      const r = d.data;
      const saldoColor = r.saldo >= 0 ? 'text-slate-700' : 'text-red-600';
      document.getElementById('cardEntradas').textContent = formatBRLCents(r.total_entradas);
      document.getElementById('cardSaidas').textContent = formatBRLCents(r.total_saidas);
      document.getElementById('cardSaldo').className = `text-2xl font-black mt-1 ${saldoColor}`;
      document.getElementById('cardSaldo').textContent = formatBRLCents(r.saldo);
      document.getElementById('cardCredito').textContent = formatBRLCents(r.total_credito_casa);
      document.getElementById('cardMensPagas').textContent = formatBRLCents(r.mensalidades_pagas);
      document.getElementById('cardMensPend').textContent = formatBRLCents(r.mensalidades_pendentes);
      document.getElementById('cardContasPend').textContent =
        `${r.contas_pendentes_qtd} — ${formatBRLCents(r.contas_pendentes_valor)}`;
    }

    async function loadCaixa() {
      const mes = getMes() + '-01';
      const d = await api({ action: 'list_caixa', query: { month: mes } });
      if (!d.ok) return;
      const s = d.summary;
      document.getElementById('caixaSummary').innerHTML = `
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase">Saldo Inicial</p>
          <p class="text-xl font-black ${s.saldo_inicial >= 0 ? 'text-slate-700' : 'text-red-600'} mt-1">${formatBRLCents(s.saldo_inicial)}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase">Entradas / Saídas (realizadas)</p>
          <p class="text-xl font-black text-green-600 mt-1">${formatBRLCents(s.entradas)} / <span class="text-red-600">${formatBRLCents(s.saidas)}</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase">Saldo Final</p>
          <p class="text-xl font-black ${s.saldo_final >= 0 ? 'text-slate-700' : 'text-red-600'} mt-1">${formatBRLCents(s.saldo_final)}</p>
        </div>
      `;
      const body = document.getElementById('caixaBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Nenhum movimento neste mês</td></tr>'
        : d.data.map(m => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3">${m.data_movimento}</td>
            <td class="px-4 py-3">${m.descricao || '-'}</td>
            <td class="px-4 py-3 capitalize">${m.origem.replace('_', ' ')}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${m.status === 'realizado' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}">
                ${m.status}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold ${m.tipo === 'entrada' ? 'text-green-600' : 'text-red-600'}">
              ${m.tipo === 'entrada' ? '+' : '-'} ${formatBRLCents(m.valor)}
            </td>
          </tr>`).join('');
    }

    async function loadContas() {
      const d = await api({ action: 'list_contas' });
      if (!d.ok) return;
      const body = document.getElementById('contasBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Nenhuma conta cadastrada</td></tr>'
        : d.data.map(c => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium">${c.descricao}</td>
            <td class="px-4 py-3 text-slate-500">${c.categoria || '-'}</td>
            <td class="px-4 py-3">${c.data_vencimento}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${c.status === 'Pago' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                ${c.status}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold">${formatBRLCents(c.valor)}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-1 justify-end">
                ${c.status !== 'Pago' ? `<button onclick="pagarConta(${c.id})" class="px-2 py-1 rounded text-xs bg-green-100 text-green-700 font-bold hover:bg-green-200">Pagar</button>` : ''}
                <button onclick="openContaModal(${c.id},'${esc(c.descricao)}','${c.categoria||''}',${c.valor},'${c.data_vencimento}')" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">Editar</button>
                <button onclick="deleteConta(${c.id})" class="px-2 py-1 rounded text-xs bg-red-100 text-red-600 font-bold hover:bg-red-200">Excluir</button>
              </div>
            </td>
          </tr>`).join('');
    }

    function openContaModal(id, descricao, categoria, valor, vencimento) {
      document.getElementById('contaId').value = id || '';
      document.getElementById('modalContaTitulo').textContent = id ? 'Editar Conta' : 'Nova Conta';
      document.getElementById('contaDescricao').value = descricao || '';
      document.getElementById('contaCategoria').value = categoria || '';
      document.getElementById('contaValor').value = id ? formatCurrencyInput(valor) : '';
      document.getElementById('contaVencimento').value = vencimento || new Date().toISOString().slice(0, 10);
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
      const body = { descricao, valor, categoria, data_vencimento: vencimento };
      if (id) body.id = id;
      const d = await api({ action, method: 'POST', body });
      if (d.ok) { toast('Conta salva!'); closeContaModal(); loadContas(); loadDashboard(); }
      else toast(d.message || 'Erro ao salvar', false);
    }

    async function pagarConta(id) {
      if (!confirm('Marcar como pago?')) return;
      const d = await api({ action: 'pay_conta', method: 'POST', body: { id } });
      if (d.ok) { toast('Pago!'); loadContas(); loadDashboard(); if (currentTab === 'caixa') loadCaixa(); }
      else toast(d.message || 'Erro', false);
    }

    async function deleteConta(id) {
      confirmDelete('Excluir esta conta?', async () => {
        const d = await api({ action: 'delete_conta', method: 'POST', body: { id } });
        if (d.ok) { toast('Conta excluída'); loadContas(); loadDashboard(); }
        else toast(d.message || 'Erro', false);
      });
    }

    async function loadEntradas() {
      const d = await api({ action: 'list_entradas' });
      if (!d.ok) return;
      const body = document.getElementById('entradasBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Nenhuma entrada cadastrada</td></tr>'
        : d.data.map(e => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium">${e.descricao}</td>
            <td class="px-4 py-3 capitalize text-slate-500">${e.origem}</td>
            <td class="px-4 py-3">${e.data_entrada}</td>
            <td class="px-4 py-3 text-right font-semibold text-green-600">${formatBRLCents(e.valor)}</td>
            <td class="px-4 py-3 text-right text-amber-600">${formatBRLCents(Math.round(e.valor * 0.10))}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-1 justify-end">
                <button onclick="openEntradaModal(${e.id},'${esc(e.descricao)}','${e.origem}',${e.valor},'${e.data_entrada}')" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">Editar</button>
                <button onclick="deleteEntrada(${e.id})" class="px-2 py-1 rounded text-xs bg-red-100 text-red-600 font-bold hover:bg-red-200">Excluir</button>
              </div>
            </td>
          </tr>`).join('');
    }

    function openEntradaModal(id, descricao, origem, valor, data) {
      document.getElementById('entradaId').value = id || '';
      document.getElementById('modalEntradaTitulo').textContent = id ? 'Editar Entrada' : 'Nova Entrada';
      document.getElementById('entradaDescricao').value = descricao || '';
      document.getElementById('entradaOrigem').value = origem || 'manual';
      document.getElementById('entradaValor').value = id ? formatCurrencyInput(valor) : '';
      document.getElementById('entradaData').value = data || new Date().toISOString().slice(0, 10);
      document.getElementById('modalEntrada').classList.remove('hidden');
    }

    function closeEntradaModal() {
      document.getElementById('modalEntrada').classList.add('hidden');
      document.getElementById('formEntrada').reset();
    }

    async function submitEntrada(e) {
      e.preventDefault();
      const id = document.getElementById('entradaId').value;
      const action = id ? 'update_entrada' : 'create_entrada';
      const descricao = document.getElementById('entradaDescricao').value;
      const valor = parseCurrency(document.getElementById('entradaValor').value);
      const origem = document.getElementById('entradaOrigem').value;
      const data_entrada = document.getElementById('entradaData').value;
      const body = { descricao, valor, origem, data_entrada };
      if (id) body.id = id;
      const d = await api({ action, method: 'POST', body });
      if (d.ok) { toast('Entrada salva!'); closeEntradaModal(); loadEntradas(); loadDashboard(); }
      else toast(d.message || 'Erro ao salvar', false);
    }

    async function deleteEntrada(id) {
      confirmDelete('Excluir esta entrada? O crédito casa associado também será removido.', async () => {
        const d = await api({ action: 'delete_entrada', method: 'POST', body: { id } });
        if (d.ok) { toast('Entrada excluída'); loadEntradas(); loadDashboard(); }
        else toast(d.message || 'Erro', false);
      });
    }

    async function loadCredito() {
      const d = await api({ action: 'list_credito_casa' });
      if (!d.ok) return;
      const body = document.getElementById('creditoBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Nenhum registro de crédito casa</td></tr>'
        : d.data.map(c => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3">${c.data}</td>
            <td class="px-4 py-3">${c.descricao || c.entrada_descricao || '-'}</td>
            <td class="px-4 py-3 text-right">${formatBRLCents(c.valor_original)}</td>
            <td class="px-4 py-3 text-right">${parseFloat(c.percentual).toFixed(0)}%</td>
            <td class="px-4 py-3 text-right font-semibold text-amber-600">${formatBRLCents(c.valor_credito)}</td>
          </tr>`).join('');
    }

    function esc(s) {
      return String(s).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', () => {
      const today = new Date();
      document.getElementById('mesFilter').value =
        today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
      document.getElementById('mesFilter').addEventListener('change', () => {
        loadDashboard();
        loadTab(currentTab);
      });
      loadDashboard();
      showTab('caixa');
    });
  </script>
</body>
</html>