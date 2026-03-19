<?php
$pageTitle = 'CRM Terreiro - Trabalhos';
$activePage = 'trabalhos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
    <!-- Overlay de segurança para print/captura -->
    <div id="printBlockOverlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:9999;color:#fff;font-size:1.3rem;align-items:center;justify-content:center;text-align:center;backdrop-filter:blur(2px);">
      <div>
        <i class="fa-solid fa-shield-halved" style="font-size:2.5rem;margin-bottom:16px;"></i><br>
        <b>Captura de tela detectada ou página fora de foco.</b><br>
        Por segurança, o conteúdo foi ocultado.<br>
        <button onclick="hidePrintBlockOverlay()" style="margin-top:24px;padding:12px 24px;border-radius:8px;background:#dc2626;color:#fff;font-weight:bold;border:none;">Voltar ao conteúdo</button>
      </div>
    </div>
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <!-- MAIN -->
    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Trabalhos</h1>
          <p class="text-slate-500">Agendamentos e realizações de trabalhos</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button id="openCatalogoModal" class="px-4 py-2 rounded-xl border border-red-600 text-red-600 font-bold hover:bg-red-50">
            <i class="fa-solid fa-list mr-2"></i>Catálogo
          </button>
          <button id="openModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
            <i class="fa-solid fa-plus mr-2"></i>Novo Trabalho
          </button>
        </div>
      </header>

      <div class="flex flex-wrap gap-3 mb-6">
        <button data-filter="all" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-black text-white">Todos</button>
        <button data-filter="Pendente" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">Pendentes</button>
        <button data-filter="Realizado" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">Realizados</button>
        <button data-filter="Adiado" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">Adiados</button>
      </div>

      <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500 border-b border-slate-100">
              <tr>
                <th class="text-left pb-3 pr-4">Trabalho</th>
                <th class="text-left pb-3 pr-4">Cliente</th>
                <th class="text-left pb-3 pr-4">Data Realização</th>
                <th class="text-left pb-3 pr-4">Status</th>
                <th class="text-left pb-3">Nova Data</th>
                <th class="text-right pb-3">Ação</th>
              </tr>
            </thead>
            <tbody id="trabalhosTable">
              <tr><td colspan="6" class="py-6 text-center text-slate-400">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- FAB -->
  <button id="fabAction" class="fixed bottom-6 right-6 w-14 h-14 bg-red-700 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl hover:bg-red-800 z-30 transition-colors">
    <i class="fa-solid fa-plus"></i>
  </button>

  <!-- MODAL NOVO/EDITAR TRABALHO -->
  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-lg p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="modalTitle">Novo Trabalho</h2>
        <button id="closeModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="trabalhoForm" class="space-y-4">
        <input type="hidden" id="trabalhoId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Tipo de Trabalho</label>
          <select id="trabalhoTipoId" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
            <option value="">Selecione...</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Cliente / Consulente</label>
          <div class="relative">
            <input id="trabalhoCliente" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Digite para buscar cliente..." autocomplete="off" />
            <input type="hidden" id="trabalhoClienteId" />
            <div id="clienteAutocomplete" class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-20 max-h-48 overflow-y-auto hidden"></div>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Vincular a Atendimento (opcional)</label>
          <select id="trabalhoAtendimentoId" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
            <option value="">Nenhum</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Data de Realização</label>
          <input id="trabalhoData" type="date" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Status</label>
          <select id="trabalhoStatus" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
            <option value="Pendente">⬜ Pendente</option>
            <option value="Realizado">✅ Realizado</option>
            <option value="Adiado">⏰ Adiado</option>
          </select>
        </div>
        <div id="novaDataRow" class="hidden">
          <label class="text-sm font-medium text-slate-700">Nova Data (se adiado)</label>
          <input id="trabalhoNovaData" type="date" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Observações</label>
          <textarea id="trabalhoObs" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" rows="2"></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CATÁLOGO -->
  <div id="catalogoModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-2xl p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Catálogo de Trabalhos</h2>
        <button id="closeCatalogoModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div class="mb-4 flex justify-end">
        <button id="openAddCatalogoForm" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold text-sm hover:bg-red-800">
          <i class="fa-solid fa-plus mr-1"></i> Adicionar
        </button>
      </div>
      <div id="addCatalogoForm" class="hidden mb-4 p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
        <input type="hidden" id="catId" />
        <input id="catName" placeholder="Nome do trabalho *" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        <textarea id="catDesc" placeholder="Descrição (opcional)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" rows="2"></textarea>
        <input id="catPrice" inputmode="numeric" placeholder="Preço (ex: <?= $_crmCurrSymbol ?>150)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        <div class="flex justify-end gap-2">
          <button id="cancelCatForm" type="button" class="px-3 py-1.5 rounded-lg border border-slate-200 text-sm">Cancelar</button>
          <button id="saveCatForm" type="button" class="px-3 py-1.5 rounded-lg bg-red-700 text-white text-sm font-bold hover:bg-red-800">Salvar</button>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-slate-500 border-b border-slate-100">
            <tr>
              <th class="text-left pb-3">Nome</th>
              <th class="text-left pb-3">Preço</th>
              <th class="text-right pb-3">Ações</th>
            </tr>
          </thead>
          <tbody id="catalogoTable">
            <tr><td colspan="3" class="py-4 text-center text-slate-400">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL DETALHE -->
  <div id="detalheModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="detalheTitle">Detalhe do Trabalho</h2>
        <button id="closeDetalheModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div id="detalheBody" class="space-y-3 text-sm"></div>
      <div class="flex justify-between gap-2 mt-6">
        <button id="detalheDelete" class="px-4 py-2 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100">
          <i class="fa-solid fa-trash mr-1"></i> Excluir
        </button>
        <button id="detalheEdit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-pen mr-1"></i> Editar
        </button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
        // Overlay de bloqueio de print/foco
        function showPrintBlockOverlay() {
          document.getElementById('printBlockOverlay').style.display = 'flex';
          registrarPrintLog();
        }
        function hidePrintBlockOverlay() {
          document.getElementById('printBlockOverlay').style.display = 'none';
        }
        // Registrar tentativa de print/foco no log
        function registrarPrintLog() {
          fetch('api/settings.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'log_event', event: 'print_screen', page: 'trabalhos', user_agent: navigator.userAgent }),
          });
        }
        // Detecta perda de foco (possível print)
        document.addEventListener('visibilitychange', function() {
          if (document.visibilityState === 'hidden') {
            showPrintBlockOverlay();
          }
        });
        // Detecta tecla PrintScreen (Windows)
        document.addEventListener('keydown', function(e) {
          if (e.key === 'PrintScreen') {
            showPrintBlockOverlay();
          }
        });
        // Detecta tentativa de copiar
        document.addEventListener('copy', function() {
          showPrintBlockOverlay();
        });
    const trabalhosTable = document.getElementById('trabalhosTable');
    const modal = document.getElementById('modal');
    const catalogoModal = document.getElementById('catalogoModal');
    const detalheModal = document.getElementById('detalheModal');

    let trabalhosCache = [];
    let catalogoCache = [];
    let clientesCache = [];
    let currentFilter = 'all';
    let currentDetalheId = null;

    // ── Load clients for autocomplete ──
    const loadClientes = async () => {
      if (clientesCache.length) return;
      try {
        const res = await fetch('api/attendances.php?action=bootstrap', { cache: 'no-store' });
        const data = await res.json();
        clientesCache = data.clients || [];
      } catch (e) { clientesCache = []; }
    };

    // ── Client autocomplete ──
    const clienteInput = document.getElementById('trabalhoCliente');
    const clienteIdInput = document.getElementById('trabalhoClienteId');
    const autocompleteEl = document.getElementById('clienteAutocomplete');
    const atendimentoSelect = document.getElementById('trabalhoAtendimentoId');

    clienteInput.addEventListener('input', () => {
      const term = clienteInput.value.toLowerCase().trim();
      clienteIdInput.value = '';
      if (term.length < 1) { autocompleteEl.classList.add('hidden'); return; }
      const matches = clientesCache.filter(c => c.name.toLowerCase().includes(term)).slice(0, 10);
      if (!matches.length) { autocompleteEl.classList.add('hidden'); return; }
      autocompleteEl.innerHTML = matches.map(c => `
        <div class="px-3 py-2 hover:bg-slate-100 cursor-pointer text-sm" data-id="${c.id}" data-name="${c.name}">${c.name}</div>
      `).join('');
      autocompleteEl.classList.remove('hidden');
    });

    autocompleteEl.addEventListener('click', async (e) => {
      const item = e.target.closest('[data-id]');
      if (!item) return;
      clienteInput.value = item.dataset.name;
      clienteIdInput.value = item.dataset.id;
      autocompleteEl.classList.add('hidden');
      // Load attendances for this client
      try {
        const res = await fetch(`api/attendances.php?action=history&client_id=${item.dataset.id}`, { cache: 'no-store' });
        const data = await res.json();
        const atts = data.data || [];
        atendimentoSelect.innerHTML = '<option value="">Nenhum</option>' +
          atts.map(a => `<option value="${a.id}">${a.services || 'Atendimento'} — ${formatBRLOrZero(String(a.total_amount))}</option>`).join('');
      } catch (e) { /* ignore */ }
    });

    document.addEventListener('click', (e) => {
      if (!autocompleteEl.contains(e.target) && e.target !== clienteInput) {
        autocompleteEl.classList.add('hidden');
      }
    });

    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => {
          b.className = 'filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600';
        });
        btn.className = 'filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-black text-white';
        currentFilter = btn.dataset.filter;
        renderTrabalhos();
      });
    });

    const statusBadge = (status) => {
      if (status === 'Realizado') return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-bold text-xs"><i class="fa-solid fa-circle-check"></i> Realizado</span>';
      if (status === 'Adiado') return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-bold text-xs"><i class="fa-solid fa-clock"></i> Adiado</span>';
      return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-bold text-xs"><i class="fa-regular fa-circle"></i> Pendente</span>';
    };

    const loadTrabalhos = async () => {
      trabalhosTable.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-slate-400">Carregando...</td></tr>';
      const res = await fetch(`api/trabalhos.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      trabalhosCache = data.data || [];
      renderTrabalhos();
    };

    const renderTrabalhos = () => {
      const rows = trabalhosCache.filter(r => currentFilter === 'all' || r.status === currentFilter);
      if (!rows.length) {
        trabalhosTable.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-slate-400">Nenhum trabalho encontrado.</td></tr>';
        return;
      }
      trabalhosTable.innerHTML = rows.map(r => `
        <tr class="border-t border-slate-100 hover:bg-red-50 cursor-pointer transition-colors" data-id="${r.id}">
          <td class="py-3 pr-4 font-medium">${r.trabalho_nome}</td>
          <td class="py-3 pr-4 text-slate-500">${r.cliente_nome || '-'}</td>
          <td class="py-3 pr-4">
            <span class="inline-flex items-center gap-1 text-slate-600">
              <i class="fa-regular fa-calendar text-red-500"></i>
              ${fmtDate(r.data_realizacao)}
            </span>
          </td>
          <td class="py-3 pr-4">${statusBadge(r.status)}</td>
          <td class="py-3">
            ${r.status === 'Adiado' && r.nova_data
              ? `<span class="inline-flex items-center gap-1 text-yellow-600"><i class="fa-regular fa-calendar"></i> ${fmtDate(r.nova_data)}</span>`
              : '-'}
          </td>
          <td class="py-3 text-right">
            <button class="text-red-600 hover:text-red-800 mr-3" data-edit="${r.id}" onclick="event.stopPropagation()">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button class="text-slate-400 hover:text-red-600" data-delete="${r.id}" onclick="event.stopPropagation()">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    };

    const loadCatalogo = async () => {
      document.getElementById('catalogoTable').innerHTML = '<tr><td colspan="3" class="py-4 text-center text-slate-400">Carregando...</td></tr>';
      const res = await fetch(`api/trabalhos.php?action=list_catalogo_all&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      catalogoCache = data.data || [];
      renderCatalogo();
    };

    const renderCatalogo = () => {
      const el = document.getElementById('catalogoTable');
      if (!catalogoCache.length) {
        el.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-slate-400">Nenhum trabalho no catálogo.</td></tr>';
        return;
      }
      el.innerHTML = catalogoCache.map(c => `
        <tr class="border-t border-slate-100">
          <td class="py-2 font-medium">${c.name}</td>
          <td class="py-2 text-slate-500">${formatBRL(String(Math.round(parseFloat(c.price) || 0)))}</td>
          <td class="py-2 text-right">
            <button class="text-red-600 text-xs mr-2" data-cat-edit="${c.id}"><i class="fa-solid fa-pen"></i></button>
            <button class="text-slate-400 hover:text-red-600 text-xs" data-cat-delete="${c.id}"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      `).join('');
    };

    const loadCatalogoSelect = async () => {
      if (!catalogoCache.length) {
        const res = await fetch(`api/trabalhos.php?action=list_catalogo&t=${Date.now()}`, { cache: 'no-store' });
        const data = await res.json();
        catalogoCache = data.data || [];
      }
      const sel = document.getElementById('trabalhoTipoId');
      const cur = sel.value;
      const trabalhos = catalogoCache.filter(c => c.tipo === 'trabalho' || !c.tipo);
      const servicos = catalogoCache.filter(c => c.tipo === 'servico');
      let html = '<option value="">Selecione...</option>';
      if (trabalhos.length) {
        html += '<optgroup label="Trabalhos">';
        html += trabalhos.map(c => `<option value="${c.id}" ${cur == c.id ? 'selected' : ''}>${c.name} — ${formatBRL(String(Math.round(parseFloat(c.price) || 0)))}</option>`).join('');
        html += '</optgroup>';
      }
      if (servicos.length) {
        html += '<optgroup label="Serviços">';
        html += servicos.map(c => `<option value="${c.id}" data-tipo="servico" ${cur == c.id ? 'selected' : ''}>${c.name} — ${formatBRL(String(Math.round(parseFloat(c.price) || 0)))}</option>`).join('');
        html += '</optgroup>';
      }
      sel.innerHTML = html;
    };

    const openNewModal = () => {
      document.getElementById('trabalhoId').value = '';
      document.getElementById('trabalhoCliente').value = '';
      document.getElementById('trabalhoClienteId').value = '';
      document.getElementById('trabalhoAtendimentoId').innerHTML = '<option value="">Nenhum</option>';
      document.getElementById('trabalhoData').value = new Date().toISOString().split('T')[0];
      document.getElementById('trabalhoStatus').value = 'Pendente';
      document.getElementById('trabalhoNovaData').value = '';
      document.getElementById('trabalhoObs').value = '';
      document.getElementById('novaDataRow').classList.add('hidden');
      document.getElementById('modalTitle').textContent = 'Novo Trabalho';
      loadCatalogoSelect();
      loadClientes();
      toggleModal(modal, true);
    };

    document.getElementById('openModal').addEventListener('click', openNewModal);
    document.getElementById('fabAction').addEventListener('click', openNewModal);
    document.getElementById('closeModal').addEventListener('click', () => toggleModal(modal, false));
    document.getElementById('cancelModal').addEventListener('click', () => toggleModal(modal, false));

    document.getElementById('trabalhoStatus').addEventListener('change', function () {
      document.getElementById('novaDataRow').classList.toggle('hidden', this.value !== 'Adiado');
    });

    document.getElementById('openCatalogoModal').addEventListener('click', () => {
      loadCatalogo();
      toggleModal(catalogoModal, true);
    });
    document.getElementById('closeCatalogoModal').addEventListener('click', () => toggleModal(catalogoModal, false));
    document.getElementById('openAddCatalogoForm').addEventListener('click', () => {
      document.getElementById('catId').value = '';
      document.getElementById('catName').value = '';
      document.getElementById('catDesc').value = '';
      document.getElementById('catPrice').value = '';
      document.getElementById('addCatalogoForm').classList.remove('hidden');
    });
    document.getElementById('cancelCatForm').addEventListener('click', () => {
      document.getElementById('addCatalogoForm').classList.add('hidden');
    });

    const catPriceInput = document.getElementById('catPrice');
    catPriceInput.addEventListener('input', () => {
      const n = catPriceInput.value.replace(/[^\d]/g, '');
      if (!n) { catPriceInput.value = ''; return; }
      // Use the dynamic currency formatter (not hardcoded BRL)
      const formatted = formatBRL(n);
      catPriceInput.value = formatted || '';
    });
    // Also handle paste: strip formatting and re-apply
    catPriceInput.addEventListener('paste', (e) => {
      setTimeout(() => {
        const n = catPriceInput.value.replace(/[^\d]/g, '');
        catPriceInput.value = n ? formatBRL(n) : '';
      }, 0);
    });

    document.getElementById('saveCatForm').addEventListener('click', () => {
      const id = document.getElementById('catId').value;
      const name = document.getElementById('catName').value.trim();
      if (!name) { alert('Nome obrigatório'); return; }
      const price = parseBRL(document.getElementById('catPrice').value);
      const description = document.getElementById('catDesc').value.trim();
      const action = id ? 'update_catalogo' : 'create_catalogo';
      const body = new URLSearchParams({ action, id, name, description, price, is_active: 1 });
      fetch('api/trabalhos.php', { method: 'POST', body })
        .then(() => {
          catalogoCache = [];
          document.getElementById('addCatalogoForm').classList.add('hidden');
          loadCatalogo();
        });
    });

    document.getElementById('catalogoTable').addEventListener('click', (e) => {
      const editId = e.target.closest('[data-cat-edit]')?.dataset.catEdit;
      const deleteId = e.target.closest('[data-cat-delete]')?.dataset.catDelete;
      if (editId) {
        const c = catalogoCache.find(x => String(x.id) === editId);
        if (!c) return;
        document.getElementById('catId').value = c.id;
        document.getElementById('catName').value = c.name;
        document.getElementById('catDesc').value = c.description || '';
        document.getElementById('catPrice').value = c.price ? formatBRL(String(Math.round(parseFloat(c.price)))) : '';
        document.getElementById('addCatalogoForm').classList.remove('hidden');
      }
      if (deleteId) {
        if (!confirm('Excluir este item do catálogo?')) return;
        fetch('api/trabalhos.php', { method: 'POST', body: new URLSearchParams({ action: 'delete_catalogo', id: deleteId }) })
          .then(() => {
            catalogoCache = [];
            loadCatalogo();
          });
      }
    });

    trabalhosTable.addEventListener('click', (e) => {
      const editId = e.target.closest('[data-edit]')?.dataset.edit;
      const deleteId = e.target.closest('[data-delete]')?.dataset.delete;
      const row = e.target.closest('tr[data-id]');

      if (editId) {
        const r = trabalhosCache.find(x => String(x.id) === editId);
        if (!r) return;
        document.getElementById('trabalhoId').value = r.id;
        document.getElementById('trabalhoCliente').value = r.cliente_nome || '';
        document.getElementById('trabalhoData').value = r.data_realizacao;
        document.getElementById('trabalhoStatus').value = r.status;
        document.getElementById('trabalhoNovaData').value = r.nova_data || '';
        document.getElementById('trabalhoObs').value = r.observacoes || '';
        document.getElementById('novaDataRow').classList.toggle('hidden', r.status !== 'Adiado');
        document.getElementById('modalTitle').textContent = 'Editar Trabalho';
        loadCatalogoSelect().then(() => {
          document.getElementById('trabalhoTipoId').value = r.trabalho_id;
        });
        toggleModal(modal, true);
        return;
      }

      if (deleteId) {
        if (!confirm('Excluir este agendamento?')) return;
        fetch('api/trabalhos.php', { method: 'POST', body: new URLSearchParams({ action: 'delete', id: deleteId }) })
          .then(loadTrabalhos);
        return;
      }

      if (row) {
        const r = trabalhosCache.find(x => String(x.id) === row.dataset.id);
        if (!r) return;
        currentDetalheId = r.id;
        document.getElementById('detalheTitle').textContent = r.trabalho_nome;
        document.getElementById('detalheBody').innerHTML = `
          <div class="flex justify-between"><span class="text-slate-500">Cliente:</span> <span class="font-medium">${r.cliente_nome || '-'}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Data:</span> <span class="font-medium flex items-center gap-1"><i class="fa-regular fa-calendar text-red-500"></i> ${fmtDate(r.data_realizacao)}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Status:</span> ${statusBadge(r.status)}</div>
          ${r.nova_data ? `<div class="flex justify-between"><span class="text-slate-500">Nova Data:</span> <span class="font-medium text-yellow-600">${fmtDate(r.nova_data)}</span></div>` : ''}
          ${r.observacoes ? `<div class="pt-2 border-t border-slate-100"><p class="text-slate-500 mb-1">Observações:</p><p>${r.observacoes}</p></div>` : ''}
        `;
        toggleModal(detalheModal, true);
      }
    });

    document.getElementById('closeDetalheModal').addEventListener('click', () => toggleModal(detalheModal, false));
    document.getElementById('detalheDelete').addEventListener('click', () => {
      if (!confirm('Excluir este agendamento?')) return;
      fetch('api/trabalhos.php', { method: 'POST', body: new URLSearchParams({ action: 'delete', id: currentDetalheId }) })
        .then(() => {
          toggleModal(detalheModal, false);
          loadTrabalhos();
        });
    });
    document.getElementById('detalheEdit').addEventListener('click', () => {
      toggleModal(detalheModal, false);
      const r = trabalhosCache.find(x => String(x.id) === String(currentDetalheId));
      if (!r) return;
      document.getElementById('trabalhoId').value = r.id;
      document.getElementById('trabalhoCliente').value = r.cliente_nome || '';
      document.getElementById('trabalhoData').value = r.data_realizacao;
      document.getElementById('trabalhoStatus').value = r.status;
      document.getElementById('trabalhoNovaData').value = r.nova_data || '';
      document.getElementById('trabalhoObs').value = r.observacoes || '';
      document.getElementById('novaDataRow').classList.toggle('hidden', r.status !== 'Adiado');
      document.getElementById('modalTitle').textContent = 'Editar Trabalho';
      loadCatalogoSelect().then(() => {
        document.getElementById('trabalhoTipoId').value = r.trabalho_id;
      });
      toggleModal(modal, true);
    });

    document.getElementById('trabalhoForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const id = document.getElementById('trabalhoId').value;
      const status = document.getElementById('trabalhoStatus').value;
      const novaData = status === 'Adiado' ? document.getElementById('trabalhoNovaData').value : '';
      const body = new URLSearchParams({
        action: id ? 'update' : 'create',
        id,
        trabalho_id: document.getElementById('trabalhoTipoId').value,
        cliente_nome: document.getElementById('trabalhoCliente').value,
        client_id: document.getElementById('trabalhoClienteId').value || '',
        attendance_id: document.getElementById('trabalhoAtendimentoId').value || '',
        data_realizacao: document.getElementById('trabalhoData').value,
        status,
        nova_data: novaData,
        observacoes: document.getElementById('trabalhoObs').value,
      });
      fetch('api/trabalhos.php', { method: 'POST', body })
        .then(() => {
          toggleModal(modal, false);
          catalogoCache = [];
          loadTrabalhos();
        });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggleModal(modal, false);
        toggleModal(catalogoModal, false);
        toggleModal(detalheModal, false);
      }
    });

    loadTrabalhos();
  </script>
</body>
</html>