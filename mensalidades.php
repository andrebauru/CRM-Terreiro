<?php
$pageTitle = 'CRM Terreiro - Mensalidades';
$activePage = 'mensalidades';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <!-- MAIN -->
    <main class="flex-1 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Mensalidades</h1>
          <p class="text-slate-500">Controle de pagamentos recorrentes</p>
        </div>
        <button id="openModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-plus mr-2"></i>Nova Mensalidade
        </button>
      </header>

      <div class="flex flex-wrap gap-2 mb-6">
        <button class="tab-btn px-4 py-2 rounded-xl text-sm font-bold bg-red-700 text-white" data-tab="mensalidades">Mensalidades</button>
        <button class="tab-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600" data-tab="contas">Contas a pagar</button>
        <button class="tab-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600" data-tab="caixa">Dinheiro em caixa</button>
      </div>

      <div id="tab-mensalidades" data-tab-content="mensalidades">
        <h2 class="text-base font-bold mb-3 text-slate-700">
          <i class="fa-solid fa-calendar-days text-red-600 mr-2"></i>Mensalidades do Mês Atual
        </h2>
        <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40 mb-8">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="text-left pb-3">Filho</th>
                  <th class="text-left pb-3">Grau</th>
                  <th class="text-left pb-3">Vencimento</th>
                  <th class="text-left pb-3">Valor</th>
                  <th class="text-right pb-3">Ação</th>
                </tr>
              </thead>
              <tbody id="mensalidadesTable">
                <tr><td class="py-3" colspan="5">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <h2 class="text-base font-bold mb-3 text-slate-700">
          <i class="fa-solid fa-receipt text-red-600 mr-2"></i>Lançamentos Extras
        </h2>
        <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="text-left pb-3">Filho</th>
                  <th class="text-left pb-3">Descrição</th>
                  <th class="text-left pb-3">Vencimento</th>
                  <th class="text-left pb-3">Valor</th>
                  <th class="text-right pb-3">Ação</th>
                </tr>
              </thead>
              <tbody id="lancamentosTable">
                <tr><td class="py-3" colspan="5">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div id="tab-contas" data-tab-content="contas" class="hidden">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-base font-bold text-slate-700">
            <i class="fa-solid fa-file-invoice-dollar text-red-600 mr-2"></i>Contas a Pagar
          </h2>
          <button id="openContaModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
            <i class="fa-solid fa-plus mr-2"></i>Nova Conta
          </button>
        </div>
        <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="text-left pb-3">Descrição</th>
                  <th class="text-left pb-3">Vencimento</th>
                  <th class="text-left pb-3">Valor</th>
                  <th class="text-right pb-3">Status</th>
                </tr>
              </thead>
              <tbody id="contasTable">
                <tr><td class="py-3" colspan="4">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div id="tab-caixa" data-tab-content="caixa" class="hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <h2 class="text-base font-bold text-slate-700">
            <i class="fa-solid fa-cash-register text-red-600 mr-2"></i>Dinheiro em Caixa
          </h2>
          <div class="flex items-center gap-2">
            <label for="caixaMonth" class="text-sm text-slate-500">Mês:</label>
            <input id="caixaMonth" type="month" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
          <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs text-slate-500">Saldo inicial</div>
            <div class="text-lg font-bold" id="caixaSaldoInicial"><?= $_crmCurrSymbol ?>0</div>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs text-slate-500">Entradas (realizado)</div>
            <div class="text-lg font-bold text-emerald-600" id="caixaEntradas"><?= $_crmCurrSymbol ?>0</div>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs text-slate-500">Saídas (realizado)</div>
            <div class="text-lg font-bold text-rose-600" id="caixaSaidas"><?= $_crmCurrSymbol ?>0</div>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <div class="text-xs text-slate-500">Saldo final</div>
            <div class="text-lg font-bold" id="caixaSaldoFinal"><?= $_crmCurrSymbol ?>0</div>
          </div>
        </div>
        <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="text-left pb-3">Data</th>
                  <th class="text-left pb-3">Tipo</th>
                  <th class="text-left pb-3">Origem</th>
                  <th class="text-left pb-3">Descrição</th>
                  <th class="text-left pb-3">Status</th>
                  <th class="text-right pb-3">Valor</th>
                </tr>
              </thead>
              <tbody id="caixaTable">
                <tr><td class="py-3" colspan="6">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- FAB -->
  <button id="fabAction" class="fixed bottom-6 right-6 w-14 h-14 bg-red-700 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl hover:bg-red-800 z-50 transition-colors">
    <i class="fa-solid fa-plus"></i>
  </button>

  <!-- MODAL NOVA MENSALIDADE -->
  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Nova Mensalidade</h2>
        <button id="closeModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="lancamentoForm" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-slate-700">Filho</label>
          <select id="lFilhoId" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
            <option value="">Selecione...</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Valor</label>
          <input id="lValor" data-mask="currency" inputmode="numeric" placeholder="<?= $_crmCurrSymbol ?>0" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Data de Vencimento</label>
          <input id="lVencimento" type="date" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Descrição (opcional)</label>
          <input id="lDescricao" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Ex: Mensalidade Janeiro 2025" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL DETALHE -->
  <div id="detalheModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold" id="detalheNome">Detalhe</h2>
        <button id="closeDetalheModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div id="detalheBody" class="space-y-3 text-sm"></div>
      <div class="flex justify-end mt-5">
        <button id="detalhePay" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-check mr-1"></i> Dar Baixa
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL NOVA CONTA A PAGAR -->
  <div id="contaModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Nova Conta a Pagar</h2>
        <button id="closeContaModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="contaForm" class="space-y-4">
        <div>
          <label class="text-sm font-medium text-slate-700">Descrição</label>
          <input id="contaDescricao" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Ex: Luz, Água, Aluguel" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Valor</label>
          <input id="contaValor" data-mask="currency" inputmode="numeric" placeholder="<?= $_crmCurrSymbol ?>0" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Data de Vencimento</label>
          <input id="contaVencimento" type="date" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelContaModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const mensalidadesTable = document.getElementById('mensalidadesTable');
    const lancamentosTable = document.getElementById('lancamentosTable');
    const modal = document.getElementById('modal');
    const detalheModal = document.getElementById('detalheModal');
    const contasTable = document.getElementById('contasTable');
    const contaModal = document.getElementById('contaModal');
    const caixaTable = document.getElementById('caixaTable');
    const caixaMonth = document.getElementById('caixaMonth');
    const caixaSaldoInicial = document.getElementById('caixaSaldoInicial');
    const caixaEntradas = document.getElementById('caixaEntradas');
    const caixaSaidas = document.getElementById('caixaSaidas');
    const caixaSaldoFinal = document.getElementById('caixaSaldoFinal');

    let currentDetalhe = null;

    const formatBRLDisplay = (value) => formatBRLOrZero(String(value || 0));

    const formatBRLInput = (value) => formatBRL(value);

    document.getElementById('lValor').addEventListener('input', function () {
      this.value = formatBRLInput(this.value);
    });
    document.getElementById('contaValor').addEventListener('input', function () {
      this.value = formatBRLInput(this.value);
    });

    const formatWhatsapp = (phone) => {
      const digits = String(phone || '').replace(/\D+/g, '');
      if (!digits) return '';
      return digits.startsWith('81') ? `https://wa.me/${digits}` : `https://wa.me/81${digits}`;
    };

    const loadMensalidades = async () => {
      mensalidadesTable.innerHTML = '<tr><td class="py-3" colspan="5">Carregando...</td></tr>';
      lancamentosTable.innerHTML = '<tr><td class="py-3" colspan="5">Carregando...</td></tr>';
      const res = await fetch(`api/mensalidades.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      const all = data.data || [];

      const mensais = all.filter(r => r.type === 'mensal');
      const extras = all.filter(r => r.type === 'lancamento');

      mensalidadesTable.innerHTML = mensais.length
        ? mensais.map(item => {
            const rowCls = item.paid ? 'bg-emerald-50' : item.overdue ? 'bg-red-50' : '';
            return `
              <tr class="border-t border-slate-100 ${rowCls} hover:opacity-80 cursor-pointer transition-opacity" data-type="mensal" data-id="${item.id}" data-paid="${item.paid}" data-value="${item.mensalidade_value}" data-name="${item.name}" data-day="${item.due_day}">
                <td class="py-3">
                  <div class="font-medium">${item.name}</div>
                  ${item.phone ? `<a href="${formatWhatsapp(item.phone)}" class="text-xs text-red-600" target="_blank" onclick="event.stopPropagation()">${item.phone}</a>` : ''}
                </td>
                <td class="py-3">${item.grade}</td>
                <td class="py-3"><span class="flex items-center gap-1"><i class="fa-regular fa-calendar text-red-500"></i>Dia ${item.due_day}</span></td>
                <td class="py-3 font-medium">${item.isento_mensalidade ? '<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Isento</span>' : formatBRLDisplay(item.mensalidade_value)}</td>
                <td class="py-3 text-right">
                  ${item.isento_mensalidade
                    ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold"><i class="fa-solid fa-hand"></i> Isento</span>'
                    : item.paid
                    ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Pago</span>'
                    : `<button class="text-red-700 font-bold text-xs px-3 py-1 rounded-lg bg-red-50 hover:bg-red-100" data-pay="${item.id}" onclick="event.stopPropagation()">Dar Baixa</button>`}
                </td>
              </tr>
            `;
          }).join('')
        : '<tr><td class="py-4 text-center text-slate-400" colspan="5">Nenhum filho cadastrado.</td></tr>';

      lancamentosTable.innerHTML = extras.length
        ? extras.map(item => {
            const rowCls = item.paid ? 'bg-emerald-50' : item.overdue ? 'bg-red-50' : '';
            return `
              <tr class="border-t border-slate-100 ${rowCls} hover:opacity-80 cursor-pointer transition-opacity" data-type="lancamento" data-id="${item.id}" data-paid="${item.paid}" data-value="${item.mensalidade_value}" data-name="${item.name}" data-venc="${item.data_vencimento}" data-desc="${item.descricao || ''}">
                <td class="py-3 font-medium">${item.name}</td>
                <td class="py-3 text-slate-500">${item.descricao || '-'}</td>
                <td class="py-3"><span class="flex items-center gap-1"><i class="fa-regular fa-calendar text-red-500"></i>${fmtDate(item.data_vencimento)}</span></td>
                <td class="py-3 font-medium">${formatBRLDisplay(item.mensalidade_value)}</td>
                <td class="py-3 text-right">
                  ${item.paid
                    ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Pago</span>'
                    : `<button class="text-red-700 font-bold text-xs px-3 py-1 rounded-lg bg-red-50 hover:bg-red-100" data-pay-lancamento="${item.id}" onclick="event.stopPropagation()">Dar Baixa</button>`}
                </td>
              </tr>
            `;
          }).join('')
        : '<tr><td class="py-4 text-center text-slate-400" colspan="5">Nenhum lançamento extra.</td></tr>';
    };

    const loadContas = async () => {
      contasTable.innerHTML = '<tr><td class="py-3" colspan="4">Carregando...</td></tr>';
      const res = await fetch(`api/financeiro.php?action=list_contas&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      const rows = (data.data || []).map((item) => {
        const status = item.status === 'Pago'
          ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Pago</span>'
          : `<button class="text-red-700 font-bold text-xs px-3 py-1 rounded-lg bg-red-50 hover:bg-red-100" data-pay-conta="${item.id}">Dar Baixa</button>`;
        return `
          <tr class="border-t border-slate-100">
            <td class="py-3 font-medium">${item.descricao}</td>
            <td class="py-3">${fmtDate(item.data_vencimento)}</td>
            <td class="py-3">${formatBRLDisplay(item.valor)}</td>
            <td class="py-3 text-right">${status}</td>
          </tr>
        `;
      });
      contasTable.innerHTML = rows.length ? rows.join('') : '<tr><td class="py-4 text-center text-slate-400" colspan="4">Nenhuma conta cadastrada.</td></tr>';
    };

    const loadCaixa = async () => {
      if (!caixaMonth.value) {
        caixaMonth.value = new Date().toISOString().slice(0, 7);
      }
      const monthParam = `${caixaMonth.value}-01`;
      caixaTable.innerHTML = '<tr><td class="py-3" colspan="6">Carregando...</td></tr>';
      const res = await fetch(`api/financeiro.php?action=list_caixa&month=${monthParam}&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      const summary = data.summary || {};
      caixaSaldoInicial.textContent = formatBRLDisplay(summary.saldo_inicial || 0);
      caixaEntradas.textContent = formatBRLDisplay(summary.entradas || 0);
      caixaSaidas.textContent = formatBRLDisplay(summary.saidas || 0);
      caixaSaldoFinal.textContent = formatBRLDisplay(summary.saldo_final || 0);

      const rows = (data.data || []).map((item) => {
        const tipo = item.tipo === 'entrada' ? 'Entrada' : 'Saída';
        const tipoClass = item.tipo === 'entrada' ? 'text-emerald-600' : 'text-rose-600';
        const status = item.status === 'realizado'
          ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Realizado</span>'
          : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold"><i class="fa-solid fa-clock"></i> Previsto</span>';
        const origemMap = { mensalidade: 'Mensalidade', trabalho: 'Trabalho', conta_pagar: 'Conta a pagar', manual: 'Manual' };
        return `
          <tr class="border-t border-slate-100">
            <td class="py-3">${fmtDate(item.data_movimento)}</td>
            <td class="py-3 ${tipoClass} font-medium">${tipo}</td>
            <td class="py-3">${origemMap[item.origem] || item.origem}</td>
            <td class="py-3 text-slate-500">${item.descricao || '-'}</td>
            <td class="py-3">${status}</td>
            <td class="py-3 text-right font-medium">${formatBRLDisplay(item.valor)}</td>
          </tr>
        `;
      });
      caixaTable.innerHTML = rows.length ? rows.join('') : '<tr><td class="py-4 text-center text-slate-400" colspan="6">Nenhum movimento neste mês.</td></tr>';
    };

    mensalidadesTable.addEventListener('click', (event) => {
      const payId = event.target.getAttribute('data-pay');
      if (payId) {
        fetch('api/mensalidades.php', { method: 'POST', body: new URLSearchParams({ action: 'pay', filho_id: payId }) })
          .then(loadMensalidades);
        return;
      }
      const row = event.target.closest('tr[data-id]');
      if (row) {
        const val = Number(row.dataset.value || 0);
        document.getElementById('detalheNome').textContent = row.dataset.name;
        document.getElementById('detalheBody').innerHTML = `
          <div class="flex justify-between"><span class="text-slate-500">Tipo:</span><span class="font-medium">Mensalidade Mensal</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Vencimento:</span><span class="font-medium flex items-center gap-1"><i class="fa-regular fa-calendar text-red-500"></i>Dia ${row.dataset.day}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Valor:</span><span class="font-medium">${formatBRLDisplay(val)}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Status:</span>
            ${row.dataset.paid === 'true'
              ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Pago</span>'
              : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold"><i class="fa-solid fa-clock"></i> Pendente</span>'}
          </div>
        `;
        document.getElementById('detalhePay').style.display = row.dataset.paid === 'true' ? 'none' : 'block';
        document.getElementById('detalhePay').onclick = () => {
          fetch('api/mensalidades.php', { method: 'POST', body: new URLSearchParams({ action: 'pay', filho_id: row.dataset.id }) })
            .then(() => { toggleModal(detalheModal, false); loadMensalidades(); });
        };
        toggleModal(detalheModal, true);
      }
    });

    lancamentosTable.addEventListener('click', (event) => {
      const payId = event.target.getAttribute('data-pay-lancamento');
      if (payId) {
        fetch('api/mensalidades.php', { method: 'POST', body: new URLSearchParams({ action: 'pay_lancamento', id: payId }) })
          .then(loadMensalidades);
        return;
      }
      const row = event.target.closest('tr[data-id]');
      if (row) {
        const val = Number(row.dataset.value || 0);
        document.getElementById('detalheNome').textContent = row.dataset.name;
        document.getElementById('detalheBody').innerHTML = `
          <div class="flex justify-between"><span class="text-slate-500">Tipo:</span><span class="font-medium">Lançamento Extra</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Descrição:</span><span class="font-medium">${row.dataset.desc || '-'}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Vencimento:</span><span class="font-medium flex items-center gap-1"><i class="fa-regular fa-calendar text-red-500"></i>${fmtDate(row.dataset.venc)}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Valor:</span><span class="font-medium">${formatBRLDisplay(val)}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Status:</span>
            ${row.dataset.paid === 'true'
              ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold"><i class="fa-solid fa-circle-check"></i> Pago</span>'
              : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold"><i class="fa-solid fa-clock"></i> Pendente</span>'}
          </div>
        `;
        document.getElementById('detalhePay').style.display = row.dataset.paid === 'true' ? 'none' : 'block';
        document.getElementById('detalhePay').onclick = () => {
          fetch('api/mensalidades.php', { method: 'POST', body: new URLSearchParams({ action: 'pay_lancamento', id: row.dataset.id }) })
            .then(() => { toggleModal(detalheModal, false); loadMensalidades(); });
        };
        toggleModal(detalheModal, true);
      }
    });

    const loadFilhosSelect = async () => {
      const res = await fetch(`api/mensalidades.php?action=list_filhos&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      const sel = document.getElementById('lFilhoId');
      sel.innerHTML = '<option value="">Selecione...</option>' +
        (data.data || []).map(f => `<option value="${f.id}">${f.name}${f.grade ? ' — ' + f.grade : ''}</option>`).join('');
    };

    const openLancamentoModal = () => {
      document.getElementById('lFilhoId').value = '';
      document.getElementById('lValor').value = '';
      document.getElementById('lVencimento').value = new Date().toISOString().split('T')[0];
      document.getElementById('lDescricao').value = '';
      loadFilhosSelect();
      toggleModal(modal, true);
    };

    document.getElementById('openModal').addEventListener('click', openLancamentoModal);
    document.getElementById('fabAction').addEventListener('click', openLancamentoModal);
    document.getElementById('closeModal').addEventListener('click', () => toggleModal(modal, false));
    document.getElementById('cancelModal').addEventListener('click', () => toggleModal(modal, false));
    document.getElementById('closeDetalheModal').addEventListener('click', () => toggleModal(detalheModal, false));

    document.getElementById('openContaModal').addEventListener('click', () => {
      document.getElementById('contaDescricao').value = '';
      document.getElementById('contaValor').value = '';
      document.getElementById('contaVencimento').value = new Date().toISOString().split('T')[0];
      toggleModal(contaModal, true);
    });
    document.getElementById('closeContaModal').addEventListener('click', () => toggleModal(contaModal, false));
    document.getElementById('cancelContaModal').addEventListener('click', () => toggleModal(contaModal, false));

    document.getElementById('lancamentoForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const valor = parseBRL(document.getElementById('lValor').value);
      const body = new URLSearchParams({
        action: 'create_lancamento',
        filho_id: document.getElementById('lFilhoId').value,
        valor,
        data_vencimento: document.getElementById('lVencimento').value,
        descricao: document.getElementById('lDescricao').value,
      });
      fetch('api/mensalidades.php', { method: 'POST', body })
        .then(() => { toggleModal(modal, false); loadMensalidades(); });
    });

    document.getElementById('contaForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const body = new URLSearchParams({
        action: 'create_conta',
        descricao: document.getElementById('contaDescricao').value,
        valor: parseBRL(document.getElementById('contaValor').value),
        data_vencimento: document.getElementById('contaVencimento').value,
      });
      fetch('api/financeiro.php', { method: 'POST', body })
        .then(() => { toggleModal(contaModal, false); loadContas(); loadCaixa(); });
    });

    contasTable.addEventListener('click', (event) => {
      const payId = event.target.getAttribute('data-pay-conta');
      if (payId) {
        fetch('api/financeiro.php', { method: 'POST', body: new URLSearchParams({ action: 'pay_conta', id: payId }) })
          .then(() => { loadContas(); loadCaixa(); });
      }
    });

    caixaMonth.addEventListener('change', loadCaixa);

    document.querySelectorAll('.tab-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach((b) => {
          b.className = 'tab-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600';
        });
        btn.className = 'tab-btn px-4 py-2 rounded-xl text-sm font-bold bg-red-700 text-white';
        const target = btn.dataset.tab;
        document.querySelectorAll('[data-tab-content]').forEach((content) => {
          content.classList.toggle('hidden', content.dataset.tabContent !== target);
        });
        if (target === 'contas') loadContas();
        if (target === 'caixa') loadCaixa();
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggleModal(modal, false);
        toggleModal(detalheModal, false);
        toggleModal(contaModal, false);
      }
    });

    loadMensalidades();
    loadContas();
    loadCaixa();
  </script>
</body>
</html>