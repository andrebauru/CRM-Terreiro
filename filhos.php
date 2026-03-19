<?php
$pageTitle = 'CRM Terreiro - Filhos da Casa';
$activePage = 'filhos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <!-- MAIN -->
    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Filhos da Casa</h1>
          <p class="text-slate-500">Gestão de cadastro e grau hierárquico</p>
        </div>
        <button id="openModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-plus mr-2"></i>Novo Filho
        </button>
      </header>

      <!-- Filtros -->
      <div class="flex gap-2 mb-4">
        <button data-filter="ativos" class="filter-btn px-3 py-1.5 rounded-lg text-sm font-bold bg-red-700 text-white">Ativos</button>
        <button data-filter="todos" class="filter-btn px-3 py-1.5 rounded-lg text-sm font-bold bg-white border border-slate-200 text-slate-600 hover:bg-red-50">Todos</button>
        <button data-filter="saiu" class="filter-btn px-3 py-1.5 rounded-lg text-sm font-bold bg-white border border-slate-200 text-slate-600 hover:bg-red-50">Saíram</button>
      </div>

      <section class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500 border-b border-slate-100">
              <tr>
                <th class="text-left pb-3">Nome</th>
                <th class="text-left pb-3">Grau</th>
                <th class="text-left pb-3">Entidade / Orixás</th>
                <th class="text-left pb-3">Mensalidade</th>
                <th class="text-left pb-3">Telefone</th>
                <th class="text-right pb-3">Ações</th>
              </tr>
            </thead>
            <tbody id="filhosTable">
              <tr>
                <td class="py-3" colspan="6">Carregando...</td>
              </tr>
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

  <!-- MODAL NOVO/EDITAR FILHO -->
  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40 overflow-y-auto py-6">
    <div class="bg-white rounded-3xl w-full max-w-2xl p-6 border border-slate-200 shadow-2xl my-auto">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-semibold" id="modalTitle">Novo Filho</h2>
        <button id="closeModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="filhoForm" class="space-y-4">
        <input type="hidden" id="filhoId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Nome</label>
          <input id="filhoName" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Email</label>
            <input id="filhoEmail" type="email" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Telefone</label>
            <input id="filhoPhone" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Grau Espiritual</label>
            <select id="filhoGrade" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
              <option>Probatório</option>
              <option>Iniciação</option>
              <option>1º Grau</option>
              <option>2º Grau</option>
              <option>3º Grau</option>
              <option>Mestre</option>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Status</label>
            <select id="filhoStatus" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
              <option value="ativo">Ativo</option>
              <option value="saiu">Saiu da casa</option>
            </select>
          </div>
        </div>
        <div id="saiuAtWrap" class="hidden">
          <label class="text-sm font-medium text-slate-700">Data de Saída</label>
          <input id="filhoSaiuAt" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Entidade de Frente</label>
          <input id="filhoEntidadeFrente" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Ex: Exu Veludo" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Orixá Pai</label>
            <input id="filhoOrixaPai" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Ex: Ogum" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Orixá Mãe</label>
            <input id="filhoOrixaMae" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Ex: Iemanjá" />
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-slate-700">Valor Mensalidade</label>
            <input id="filhoMensalidade" data-mask="currency" inputmode="numeric" placeholder="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700">Dia de Vencimento</label>
            <input id="filhoDueDay" type="number" min="1" max="28" placeholder="5" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <input id="filhoIsento" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500" />
          <label for="filhoIsento" class="text-sm font-medium text-slate-700">Isento de mensalidade</label>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Observações de Evolução Espiritual</label>
          <textarea id="filhoNotes" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" rows="2"></textarea>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Anotações Gerais</label>
          <textarea id="filhoAnotacoes" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" rows="2"></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL DETALHE -->
  <div id="detalheModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-40 overflow-y-auto py-6">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl my-auto">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <h2 class="text-lg font-bold" id="detalheNome">Filho</h2>
          <span id="detalheStatusBadge" class="text-xs px-2 py-0.5 rounded-full font-bold"></span>
        </div>
        <button id="closeDetalheModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div id="detalheBody" class="space-y-2 text-sm mb-4"></div>
      <div class="flex justify-between gap-2 mt-2">
        <button id="detalheDelete" class="px-4 py-2 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100">
          <i class="fa-solid fa-trash mr-1"></i> Excluir
        </button>
        <div class="flex gap-2">
          <a id="detalheQuimbandeiro" href="quimbandeiro.php" class="px-4 py-2 rounded-xl border border-red-200 text-red-600 font-bold hover:bg-red-50">
            <i class="fa-solid fa-fire mr-1"></i> Quimbandeiro
          </a>
          <button id="detalheEdit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
            <i class="fa-solid fa-pen mr-1"></i> Editar
          </button>
        </div>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const filhosTable = document.getElementById('filhosTable');
    const modal = document.getElementById('modal');
    const detalheModal = document.getElementById('detalheModal');
    const filhoForm = document.getElementById('filhoForm');
    const modalTitle = document.getElementById('modalTitle');

    const filhoId             = document.getElementById('filhoId');
    const filhoName           = document.getElementById('filhoName');
    const filhoEmail          = document.getElementById('filhoEmail');
    const filhoPhone          = document.getElementById('filhoPhone');
    const filhoGrade          = document.getElementById('filhoGrade');
    const filhoStatus         = document.getElementById('filhoStatus');
    const filhoSaiuAt         = document.getElementById('filhoSaiuAt');
    const filhoMensalidade    = document.getElementById('filhoMensalidade');
    const filhoDueDay         = document.getElementById('filhoDueDay');
    const filhoNotes          = document.getElementById('filhoNotes');
    const filhoAnotacoes      = document.getElementById('filhoAnotacoes');
    const filhoEntidadeFrente = document.getElementById('filhoEntidadeFrente');
    const filhoOrixaPai       = document.getElementById('filhoOrixaPai');
    const filhoOrixaMae       = document.getElementById('filhoOrixaMae');
    const filhoIsento         = document.getElementById('filhoIsento');
    const saiuAtWrap          = document.getElementById('saiuAtWrap');

    let filhosCache = [];
    let filtroAtual = 'ativos';
    let currentFilho = null;

    filhoStatus.addEventListener('change', () => {
      saiuAtWrap.classList.toggle('hidden', filhoStatus.value !== 'saiu');
    });

    const resetForm = () => {
      filhoId.value            = '';
      filhoName.value          = '';
      filhoEmail.value         = '';
      filhoPhone.value         = '';
      filhoGrade.value         = 'Iniciação';
      filhoStatus.value        = 'ativo';
      filhoSaiuAt.value        = '';
      filhoMensalidade.value   = '';
      filhoDueDay.value        = '5';
      filhoIsento.checked      = false;
      filhoNotes.value         = '';
      filhoAnotacoes.value     = '';
      filhoEntidadeFrente.value = '';
      filhoOrixaPai.value      = '';
      filhoOrixaMae.value      = '';
      saiuAtWrap.classList.add('hidden');
    };

    const formatWhatsapp = (phone) => {
      const digits = String(phone || '').replace(/\D+/g, '');
      if (!digits) return '';
      return digits.startsWith('81') ? `https://wa.me/${digits}` : `https://wa.me/81${digits}`;
    };

    const gradeColor = (grade) => {
      const map = {
        'Probatório': 'bg-slate-100 text-slate-600',
        'Iniciação':  'bg-blue-100 text-blue-700',
        '1º Grau':    'bg-green-100 text-green-700',
        '2º Grau':    'bg-yellow-100 text-yellow-700',
        '3º Grau':    'bg-orange-100 text-orange-700',
        'Mestre':     'bg-red-100 text-red-700',
      };
      return map[grade] || 'bg-slate-100 text-slate-600';
    };

    const renderFilhos = (rows) => {
      filhosTable.innerHTML = rows.length
        ? rows.map(filho => {
            const saiu = filho.status === 'saiu';
            const entOrixas = [filho.entidade_frente, filho.orixa_pai, filho.orixa_mae].filter(Boolean).join(' · ') || '—';
            return `
            <tr class="border-t border-slate-100 hover:bg-red-50 cursor-pointer transition-colors ${saiu ? 'opacity-50' : ''}" data-id="${filho.id}">
              <td class="py-3 font-medium">
                ${filho.name}
                ${saiu ? '<span class="ml-2 text-xs bg-slate-200 text-slate-500 px-1.5 py-0.5 rounded-full">saiu</span>' : ''}
              </td>
              <td class="py-3">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold ${gradeColor(filho.grade)}">${filho.grade}</span>
              </td>
              <td class="py-3 text-slate-500 text-xs">${entOrixas}</td>
              <td class="py-3">${saiu ? '—' : (parseInt(filho.isento_mensalidade) ? '<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Isento</span>' : formatBRL(String(filho.mensalidade_value || 0)))}</td>
              <td class="py-3">${filho.phone ? `<a href="${formatWhatsapp(filho.phone)}" class="text-red-600" target="_blank" onclick="event.stopPropagation()">${filho.phone}</a>` : '—'}</td>
              <td class="py-3 text-right">
                <button class="text-red-600 hover:text-red-800 mr-3 btn-edit" data-edit="${filho.id}"><i class="fa-solid fa-pen"></i></button>
                <button class="text-slate-400 hover:text-red-600 btn-delete" data-delete="${filho.id}"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>`;
          }).join('')
        : '<tr><td class="py-4 text-center text-slate-400" colspan="6">Nenhum filho encontrado.</td></tr>';
    };

    const aplicarFiltro = () => {
      let rows = filhosCache;
      if (filtroAtual === 'ativos') rows = filhosCache.filter(f => (f.status || 'ativo') === 'ativo');
      if (filtroAtual === 'saiu')   rows = filhosCache.filter(f => f.status === 'saiu');
      renderFilhos(rows);
    };

    const loadFilhos = async () => {
      filhosTable.innerHTML = '<tr><td class="py-3" colspan="6">Carregando...</td></tr>';
      const response = await fetch(`api/filhos.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await response.json();
      filhosCache = data.data || [];
      aplicarFiltro();
    };

    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => {
          b.className = 'filter-btn px-3 py-1.5 rounded-lg text-sm font-bold bg-white border border-slate-200 text-slate-600 hover:bg-red-50';
        });
        btn.className = 'filter-btn px-3 py-1.5 rounded-lg text-sm font-bold bg-red-700 text-white';
        filtroAtual = btn.dataset.filter;
        aplicarFiltro();
      });
    });

    document.getElementById('openModal').addEventListener('click', () => {
      resetForm();
      modalTitle.textContent = 'Novo Filho';
      toggleModal(modal, true);
    });
    document.getElementById('fabAction').addEventListener('click', () => {
      resetForm();
      modalTitle.textContent = 'Novo Filho';
      toggleModal(modal, true);
    });

    [document.getElementById('closeModal'), document.getElementById('cancelModal')].forEach(btn =>
      btn.addEventListener('click', () => toggleModal(modal, false))
    );
    document.getElementById('closeDetalheModal').addEventListener('click', () => toggleModal(detalheModal, false));

    filhoMensalidade.addEventListener('input', () => {
      filhoMensalidade.value = formatBRL(filhoMensalidade.value);
    });

    const openEdit = (filho) => {
      filhoId.value              = filho.id;
      filhoName.value            = filho.name || '';
      filhoEmail.value           = filho.email || '';
      filhoPhone.value           = filho.phone || '';
      filhoGrade.value           = filho.grade || 'Iniciação';
      filhoStatus.value          = filho.status || 'ativo';
      filhoSaiuAt.value          = filho.saiu_at || '';
      filhoMensalidade.value     = formatBRL(String(filho.mensalidade_value || ''));
      filhoDueDay.value          = filho.due_day || 5;
      filhoIsento.checked        = parseInt(filho.isento_mensalidade) === 1;
      filhoNotes.value           = filho.notes_evolucao || '';
      filhoAnotacoes.value       = filho.anotacoes || '';
      filhoEntidadeFrente.value  = filho.entidade_frente || '';
      filhoOrixaPai.value        = filho.orixa_pai || '';
      filhoOrixaMae.value        = filho.orixa_mae || '';
      saiuAtWrap.classList.toggle('hidden', filho.status !== 'saiu');
      modalTitle.textContent = 'Editar Filho';
      toggleModal(modal, true);
    };

    filhosTable.addEventListener('click', (event) => {
      const editBtn   = event.target.closest('.btn-edit');
      const deleteBtn = event.target.closest('.btn-delete');
      const editId   = editBtn?.dataset.edit;
      const deleteId = deleteBtn?.dataset.delete;
      const row      = event.target.closest('tr[data-id]');

      if (editId) {
        const filho = filhosCache.find(f => String(f.id) === editId);
        if (filho) openEdit(filho);
        return;
      }

      if (deleteId) {
        if (!confirm('Deseja excluir este filho?')) return;
        fetch('api/filhos.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'delete', id: deleteId }),
        }).then(loadFilhos);
        return;
      }

      if (row) {
        currentFilho = filhosCache.find(f => String(f.id) === row.dataset.id);
        if (!currentFilho) return;

        const saiu = currentFilho.status === 'saiu';
        document.getElementById('detalheNome').textContent = currentFilho.name;
        const badge = document.getElementById('detalheStatusBadge');
        badge.textContent = saiu ? 'Saiu' : 'Ativo';
        badge.className = `text-xs px-2 py-0.5 rounded-full font-bold ${saiu ? 'bg-slate-200 text-slate-500' : 'bg-green-100 text-green-700'}`;

        const row2 = (label, value) => value
          ? `<div class="flex justify-between border-b border-slate-100 pb-2">
               <span class="text-slate-500">${label}</span>
               <span class="font-medium text-right max-w-[60%]">${value}</span>
             </div>`
          : '';

        document.getElementById('detalheBody').innerHTML = `
          ${row2('Grau', `<span class="px-2 py-0.5 rounded-full text-xs font-bold ${gradeColor(currentFilho.grade)}">${currentFilho.grade}</span>`)}
          ${row2('Data do Grau', fmtDate(currentFilho.grade_date))}
          ${currentFilho.entidade_frente ? row2('Entidade de Frente', currentFilho.entidade_frente) : ''}
          ${currentFilho.orixa_pai ? row2('Orixá Pai', currentFilho.orixa_pai) : ''}
          ${currentFilho.orixa_mae ? row2('Orixá Mãe', currentFilho.orixa_mae) : ''}
          ${row2('Mensalidade', saiu ? '— (inativo)' : (parseInt(currentFilho.isento_mensalidade) ? '<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Isento</span>' : formatBRL(String(currentFilho.mensalidade_value || 0))))}
          ${!saiu ? row2('Vencimento', `Dia ${currentFilho.due_day || 5}`) : ''}
          ${saiu && currentFilho.saiu_at ? row2('Saiu em', fmtDate(currentFilho.saiu_at)) : ''}
          ${currentFilho.email ? row2('Email', currentFilho.email) : ''}
          ${currentFilho.phone ? `<div class="flex justify-between border-b border-slate-100 pb-2"><span class="text-slate-500">Telefone</span><a href="${formatWhatsapp(currentFilho.phone)}" target="_blank" class="font-medium text-red-600">${currentFilho.phone}</a></div>` : ''}
          ${currentFilho.notes_evolucao ? `<div class="pt-1"><p class="text-slate-500 text-xs mb-1">Evolução Espiritual:</p><p class="text-slate-700">${currentFilho.notes_evolucao}</p></div>` : ''}
          ${currentFilho.anotacoes ? `<div class="pt-1"><p class="text-slate-500 text-xs mb-1">Anotações:</p><p class="text-slate-700">${currentFilho.anotacoes}</p></div>` : ''}
        `;

        document.getElementById('detalheDelete').onclick = () => {
          if (!confirm('Deseja excluir este filho?')) return;
          fetch('api/filhos.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'delete', id: currentFilho.id }),
          }).then(() => { toggleModal(detalheModal, false); loadFilhos(); });
        };
        document.getElementById('detalheEdit').onclick = () => {
          toggleModal(detalheModal, false);
          openEdit(currentFilho);
        };
        toggleModal(detalheModal, true);
      }
    });

    filhoForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const payload = new URLSearchParams({
        action:           filhoId.value ? 'update' : 'create',
        id:               filhoId.value,
        name:             filhoName.value,
        email:            filhoEmail.value,
        phone:            filhoPhone.value,
        grade:            filhoGrade.value,
        status:           filhoStatus.value,
        saiu_at:          filhoSaiuAt.value,
        mensalidade_value: parseBRL(filhoMensalidade.value),
        due_day:          filhoDueDay.value || 5,
        isento_mensalidade: filhoIsento.checked ? 1 : 0,
        notes_evolucao:   filhoNotes.value,
        anotacoes:        filhoAnotacoes.value,
        entidade_frente:  filhoEntidadeFrente.value,
        orixa_pai:        filhoOrixaPai.value,
        orixa_mae:        filhoOrixaMae.value,
      });

      fetch('api/filhos.php', { method: 'POST', body: payload })
        .then(() => {
          toggleModal(modal, false);
          loadFilhos();
        });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        toggleModal(modal, false);
        toggleModal(detalheModal, false);
      }
    });

    loadFilhos();
  </script>
</body>
</html>
