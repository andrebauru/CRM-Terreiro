<?php
$pageTitle = 'CRM Terreiro - Quimbandeiro';
$activePage = 'quimbandeiro';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Quimbandeiro</h1>
          <p class="text-slate-500">Acompanhamento de graus e iniciações dos filhos</p>
        </div>
        <button id="openAddModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-plus mr-2"></i>Novo Registro
        </button>
      </header>

      <div class="flex gap-4 mb-6 text-sm">
        <span class="inline-flex items-center gap-1.5 text-slate-500">
          <span class="w-4 h-4 rounded bg-green-100 border border-green-300 inline-block"></span> Concluído
        </span>
        <span class="inline-flex items-center gap-1.5 text-slate-500">
          <span class="w-4 h-4 rounded bg-slate-100 border border-slate-300 inline-block"></span> Pendente
        </span>
      </div>

      <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-black text-white">
              <tr>
                <th class="text-left px-4 py-3 font-bold">Filho</th>
                <th class="text-center px-3 py-3 font-bold">Probatório</th>
                <th class="text-center px-3 py-3 font-bold">Iniciação</th>
                <th class="text-center px-3 py-3 font-bold">Mão de Búzios</th>
                <th class="text-center px-3 py-3 font-bold">Mão de Faca</th>
                <th class="text-center px-3 py-3 font-bold">1º Grau</th>
                <th class="text-center px-3 py-3 font-bold">2º Grau</th>
                <th class="text-center px-3 py-3 font-bold">3º Grau</th>
                <th class="text-right px-4 py-3 font-bold">Ação</th>
              </tr>
            </thead>
            <tbody id="quimbanceiroTable">
              <tr><td colspan="9" class="py-8 text-center text-slate-400">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <button id="fabAction" class="fixed bottom-6 right-6 w-14 h-14 bg-red-700 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl hover:bg-red-800 z-30 transition-colors">
    <i class="fa-solid fa-plus"></i>
  </button>

  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40">
    <div class="bg-white rounded-3xl w-full max-w-xl p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-1">
        <h2 class="text-lg font-bold text-black">Quimbandeiro — <span id="modalFilhoName" class="text-red-600"></span></h2>
        <button id="closeModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <p class="text-slate-400 text-xs mb-5">Preencha as datas de cada etapa concluída. Deixe em branco se ainda não realizado.</p>
      <form id="quimbandeiroForm" class="space-y-4">
        <input type="hidden" id="modalFilhoId" />
        <div id="filhoSelectRow" class="hidden">
          <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
            <i class="fa-solid fa-user text-red-600"></i> Filho da Casa
          </label>
          <select id="filhoSelect" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">Selecione um filho...</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-clipboard-check text-red-600"></i> Probatório
            </label>
            <input id="qProbatorio" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-link text-red-600"></i> Link de Iniciação
            </label>
            <input id="qLinkIniciacao" type="url" placeholder="https://..." class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-hand-sparkles text-red-600"></i> Mão de Búzios
            </label>
            <input id="qMaoBuzios" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-hand-fist text-red-600"></i> Mão de Faca
            </label>
            <input id="qMaoFaca" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-star text-red-600"></i> 1º Grau
            </label>
            <input id="qGrau1" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-star text-red-600"></i><i class="fa-solid fa-star text-red-600 -ml-1"></i> 2º Grau
            </label>
            <input id="qGrau2" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div class="col-span-2">
            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
              <i class="fa-solid fa-crown text-red-600"></i> 3º Grau
            </label>
            <input id="qGrau3" type="date" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <div id="detalheModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold" id="detalheNome">Detalhe</h2>
        <button id="closeDetalheModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div id="detalheBody" class="space-y-2.5 text-sm"></div>
      <div class="flex justify-end mt-5">
        <button id="detalheEdit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-pen mr-1"></i> Editar
        </button>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const quimbanceiroTable = document.getElementById('quimbanceiroTable');
    const modal = document.getElementById('modal');
    const detalheModal = document.getElementById('detalheModal');

    let quimCache = [];
    let currentRow = null;

    const dotCell = (date) => {
      if (date) {
        return `<td class="py-3 text-center">
          <span class="inline-flex flex-col items-center gap-0.5">
            <span class="w-6 h-6 rounded-full bg-green-100 border-2 border-green-400 flex items-center justify-center">
              <i class="fa-solid fa-check text-green-600 text-xs"></i>
            </span>
            <span class="text-xs text-slate-400 mt-0.5">${fmtDate(date)}</span>
          </span>
        </td>`;
      }
      return `<td class="py-3 text-center">
        <span class="w-6 h-6 rounded-full bg-slate-100 border-2 border-slate-200 inline-flex items-center justify-center">
          <i class="fa-solid fa-minus text-slate-300 text-xs"></i>
        </span>
      </td>`;
    };

    const loadQuimbandeiro = async () => {
      quimbanceiroTable.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Carregando...</td></tr>';
      const res = await fetch(`api/quimbandeiro.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      quimCache = data.data || [];
      renderQuimbandeiro();
    };

    const renderQuimbandeiro = () => {
      if (!quimCache.length) {
        quimbanceiroTable.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Nenhum filho cadastrado.</td></tr>';
        return;
      }
      quimbanceiroTable.innerHTML = quimCache.map(f => `
        <tr class="border-t border-slate-100 hover:bg-red-50 cursor-pointer transition-colors" data-id="${f.id}">
          <td class="py-3 px-4 font-medium">${f.name}</td>
          ${dotCell(f.probatorio)}
          ${dotCell(f.link_iniciacao ? '1' : null)}
          ${dotCell(f.mao_buzios)}
          ${dotCell(f.mao_faca)}
          ${dotCell(f.grau1)}
          ${dotCell(f.grau2)}
          ${dotCell(f.grau3)}
          <td class="py-3 px-4 text-right">
            <button class="text-red-600 hover:text-red-800 font-bold text-xs px-3 py-1 rounded-lg bg-red-50 hover:bg-red-100" data-edit="${f.id}" onclick="event.stopPropagation()">
              <i class="fa-solid fa-pen mr-1"></i>Editar
            </button>
          </td>
        </tr>
      `).join('');
    };

    const openEditModal = (f) => {
      document.getElementById('modalFilhoId').value = f.id;
      document.getElementById('modalFilhoName').textContent = f.name;
      document.getElementById('filhoSelectRow').classList.add('hidden');
      document.getElementById('qProbatorio').value = f.probatorio ? f.probatorio.split('T')[0] : '';
      document.getElementById('qLinkIniciacao').value = f.link_iniciacao || '';
      document.getElementById('qMaoBuzios').value = f.mao_buzios ? f.mao_buzios.split('T')[0] : '';
      document.getElementById('qMaoFaca').value = f.mao_faca ? f.mao_faca.split('T')[0] : '';
      document.getElementById('qGrau1').value = f.grau1 ? f.grau1.split('T')[0] : '';
      document.getElementById('qGrau2').value = f.grau2 ? f.grau2.split('T')[0] : '';
      document.getElementById('qGrau3').value = f.grau3 ? f.grau3.split('T')[0] : '';
      toggleModal(modal, true);
    };

    quimbanceiroTable.addEventListener('click', (e) => {
      const editId = e.target.closest('[data-edit]')?.dataset.edit;
      const row = e.target.closest('tr[data-id]');

      if (editId) {
        const f = quimCache.find(x => String(x.id) === editId);
        if (f) openEditModal(f);
        return;
      }

      if (row) {
        const f = quimCache.find(x => String(x.id) === row.dataset.id);
        if (!f) return;
        currentRow = f;

        const defLabel = (v) => v
          ? `<span class="text-green-600 font-bold"><i class="fa-solid fa-circle-check mr-1"></i>${fmtDate(v)}</span>`
          : `<span class="text-slate-400"><i class="fa-regular fa-circle mr-1"></i>Pendente</span>`;

        document.getElementById('detalheNome').textContent = f.name;
        document.getElementById('detalheBody').innerHTML = `
          <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <span class="text-slate-500 font-medium">Probatório</span>${defLabel(f.probatorio)}
          </div>
          <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <span class="text-slate-500 font-medium">Link Iniciação</span>
            ${f.link_iniciacao ? `<a href="${f.link_iniciacao}" target="_blank" class="text-red-600 underline text-xs">Ver link</a>` : '<span class="text-slate-400">Pendente</span>'}
          </div>
          <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <span class="text-slate-500 font-medium">Mão de Búzios</span>${defLabel(f.mao_buzios)}
          </div>
          <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <span class="text-slate-500 font-medium">Mão de Faca</span>${defLabel(f.mao_faca)}
          </div>
          <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <span class="text-slate-500 font-medium">1º Grau</span>${defLabel(f.grau1)}
          </div>
          <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <span class="text-slate-500 font-medium">2º Grau</span>${defLabel(f.grau2)}
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-500 font-medium">3º Grau</span>${defLabel(f.grau3)}
          </div>
        `;
        toggleModal(detalheModal, true);
      }
    });

    document.getElementById('closeDetalheModal').addEventListener('click', () => toggleModal(detalheModal, false));
    document.getElementById('detalheEdit').addEventListener('click', () => {
      toggleModal(detalheModal, false);
      if (currentRow) openEditModal(currentRow);
    });

    document.getElementById('closeModal').addEventListener('click', () => toggleModal(modal, false));
    document.getElementById('cancelModal').addEventListener('click', () => toggleModal(modal, false));

    const openAddModal = async () => {
      // Buscar filhos sem registro no quimbandeiro
      const res = await fetch(`api/quimbandeiro.php?action=unregistered&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      const filhos = data.data || [];
      const sel = document.getElementById('filhoSelect');
      sel.innerHTML = '<option value="">Selecione um filho...</option>' +
        filhos.map(f => `<option value="${f.id}">${f.name}</option>`).join('');

      if (!filhos.length) {
        alert('Todos os filhos já possuem registro no Quimbandeiro.');
        return;
      }

      // Limpar campos
      document.getElementById('modalFilhoId').value = '';
      document.getElementById('modalFilhoName').textContent = 'Novo Registro';
      document.getElementById('filhoSelectRow').classList.remove('hidden');
      document.getElementById('qProbatorio').value = '';
      document.getElementById('qLinkIniciacao').value = '';
      document.getElementById('qMaoBuzios').value = '';
      document.getElementById('qMaoFaca').value = '';
      document.getElementById('qGrau1').value = '';
      document.getElementById('qGrau2').value = '';
      document.getElementById('qGrau3').value = '';
      toggleModal(modal, true);
    };

    // Ao selecionar filho no select, preencher o hidden
    document.getElementById('filhoSelect').addEventListener('change', function() {
      document.getElementById('modalFilhoId').value = this.value;
      const opt = this.options[this.selectedIndex];
      if (opt && opt.value) {
        document.getElementById('modalFilhoName').textContent = opt.textContent;
      }
    });

    document.getElementById('openAddModal').addEventListener('click', openAddModal);
    document.getElementById('fabAction').addEventListener('click', openAddModal);

    document.getElementById('quimbandeiroForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const body = new URLSearchParams({
        action: 'save',
        filho_id: document.getElementById('modalFilhoId').value,
        probatorio: document.getElementById('qProbatorio').value,
        link_iniciacao: document.getElementById('qLinkIniciacao').value,
        mao_buzios: document.getElementById('qMaoBuzios').value,
        mao_faca: document.getElementById('qMaoFaca').value,
        grau1: document.getElementById('qGrau1').value,
        grau2: document.getElementById('qGrau2').value,
        grau3: document.getElementById('qGrau3').value,
      });
      fetch('api/quimbandeiro.php', { method: 'POST', body })
        .then(() => { toggleModal(modal, false); loadQuimbandeiro(); });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggleModal(modal, false);
        toggleModal(detalheModal, false);
      }
    });

    loadQuimbandeiro();
  </script>
</body>
</html>