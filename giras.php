<?php
$pageTitle = 'CRM Terreiro - Registro de Giras';
$activePage = 'giras';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <!-- MAIN -->
    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Registro de Giras</h1>
          <p class="text-slate-500">Campanhas de giras nas redes sociais</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button id="openTiposModal" class="px-4 py-2 rounded-xl border border-red-600 text-red-600 font-bold hover:bg-red-50">
            <i class="fa-solid fa-list mr-2"></i>Tipos de Gira
          </button>
          <button id="openModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
            <i class="fa-solid fa-plus mr-2"></i>Nova Gira
          </button>
        </div>
      </header>

      <!-- FILTROS POR PLATAFORMA -->
      <div class="flex flex-wrap gap-3 mb-6">
        <button data-filter="all" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-black text-white">Todas</button>
        <button data-filter="Facebook" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">
          <i class="fa-brands fa-facebook mr-1"></i>Facebook
        </button>
        <button data-filter="Instagram" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">
          <i class="fa-brands fa-instagram mr-1"></i>Instagram
        </button>
        <button data-filter="TikTok" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">
          <i class="fa-brands fa-tiktok mr-1"></i>TikTok
        </button>
        <button data-filter="YouTube" class="filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600">
          <i class="fa-brands fa-youtube mr-1"></i>YouTube
        </button>
      </div>

      <!-- CARDS GRID -->
      <section id="girasGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="col-span-full text-center text-slate-400 py-12">Carregando...</div>
      </section>
    </main>
  </div>

  <!-- FAB -->
  <button id="fabAction" class="fixed bottom-6 right-6 w-14 h-14 bg-red-700 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl hover:bg-red-800 z-30 transition-colors">
    <i class="fa-solid fa-plus"></i>
  </button>

  <!-- MODAL NOVA/EDITAR GIRA -->
  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-lg p-6 border border-slate-200 shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="modalTitle">Nova Gira</h2>
        <button id="closeModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="giraForm" class="space-y-4">
        <input type="hidden" id="giraId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Tipo de Gira *</label>
          <select id="giraTipo" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
            <option value="">Selecione...</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Plataformas *</label>
          <div id="giraPlataformas" class="mt-2 flex flex-wrap gap-3">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" name="plataforma" value="Facebook" class="accent-blue-600 w-4 h-4" />
              <i class="fa-brands fa-facebook text-blue-600"></i> Facebook
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" name="plataforma" value="Instagram" checked class="accent-pink-500 w-4 h-4" />
              <i class="fa-brands fa-instagram text-pink-500"></i> Instagram
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" name="plataforma" value="TikTok" class="accent-black w-4 h-4" />
              <i class="fa-brands fa-tiktok"></i> TikTok
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" name="plataforma" value="YouTube" class="accent-red-600 w-4 h-4" />
              <i class="fa-brands fa-youtube text-red-600"></i> YouTube
            </label>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Data de Realização *</label>
          <input id="giraDataRealizacao" type="date" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Data de Postagem</label>
          <input id="giraDataPostagem" type="date" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Foto da Postagem</label>
          <input id="giraFoto" type="file" accept="image/*" class="mt-2 w-full text-sm" />
          <div id="fotoPreview" class="mt-2"></div>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Descrição</label>
          <textarea id="giraDescricao" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" rows="2" placeholder="Observações sobre a gira..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL TIPOS DE GIRA -->
  <div id="tiposModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Tipos de Gira</h2>
        <button id="closeTiposModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div class="mb-4 flex gap-2">
        <input id="novoTipoInput" placeholder="Nome do novo tipo..." class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        <button id="addTipoBtn" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold text-sm hover:bg-red-800">
          <i class="fa-solid fa-plus mr-1"></i>Adicionar
        </button>
      </div>
      <div class="overflow-y-auto max-h-60">
        <table class="w-full text-sm">
          <thead class="text-slate-500 border-b border-slate-100">
            <tr>
              <th class="text-left pb-3">Nome</th>
              <th class="text-right pb-3">Ação</th>
            </tr>
          </thead>
          <tbody id="tiposTable">
            <tr><td colspan="2" class="py-4 text-center text-slate-400">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MODAL DETALHE -->
  <div id="detalheModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-md p-6 border border-slate-200 shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="detalheTitle">Detalhe da Gira</h2>
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

  <!-- LIGHTBOX (expand image) -->
  <div id="lightbox" class="fixed inset-0 hidden items-center justify-center bg-black/80 z-[70] p-4 cursor-pointer" onclick="this.classList.add('hidden');this.classList.remove('flex')">
    <img id="lightboxImg" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl object-contain" />
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const girasGrid = document.getElementById('girasGrid');
    const modal = document.getElementById('modal');
    const tiposModal = document.getElementById('tiposModal');
    const detalheModal = document.getElementById('detalheModal');

    let girasCache = [];
    let tiposCache = [];
    let currentFilter = 'all';
    let currentDetalheId = null;

    // ── Platform icon/color ──
    const platformIcon = (p) => {
      if (p === 'Facebook') return '<i class="fa-brands fa-facebook text-blue-600"></i>';
      if (p === 'Instagram') return '<i class="fa-brands fa-instagram text-pink-500"></i>';
      if (p === 'TikTok') return '<i class="fa-brands fa-tiktok text-black"></i>';
      if (p === 'YouTube') return '<i class="fa-brands fa-youtube text-red-600"></i>';
      return '<i class="fa-solid fa-globe text-slate-400"></i>';
    };

    const platformBg = (p) => {
      const first = p.split(',')[0].trim();
      if (first === 'Facebook') return 'border-blue-200 bg-blue-50/50';
      if (first === 'Instagram') return 'border-pink-200 bg-pink-50/50';
      if (first === 'TikTok') return 'border-slate-300 bg-slate-50/50';
      if (first === 'YouTube') return 'border-red-200 bg-red-50/50';
      return 'border-slate-200';
    };

    // Multi-platform badges helper
    const platformBadges = (p) => {
      return (p || '').split(',').map(x => x.trim()).filter(Boolean)
        .map(x => `<span class="inline-flex items-center gap-1 text-xs">${platformIcon(x)} ${x}</span>`).join(' ');
    };

    // Helper: check if a gira matches the filter (supports multi-platform)
    const matchesPlatformFilter = (giraPlataforma, filter) => {
      if (filter === 'all') return true;
      return (giraPlataforma || '').split(',').map(x => x.trim()).includes(filter);
    };

    // ── Helper: get selected platforms from checkboxes ──
    const getSelectedPlatforms = () => {
      return Array.from(document.querySelectorAll('#giraPlataformas input[name="plataforma"]:checked'))
        .map(cb => cb.value).join(',');
    };
    const setSelectedPlatforms = (val) => {
      const arr = (val || '').split(',').map(x => x.trim());
      document.querySelectorAll('#giraPlataformas input[name="plataforma"]').forEach(cb => {
        cb.checked = arr.includes(cb.value);
      });
    };

    // ── Filter buttons ──
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => {
          b.className = 'filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-600';
        });
        btn.className = 'filter-btn px-4 py-2 rounded-xl text-sm font-bold bg-black text-white';
        currentFilter = btn.dataset.filter;
        renderGiras();
      });
    });

    // ── Load data ──
    const loadGiras = async () => {
      girasGrid.innerHTML = '<div class="col-span-full text-center text-slate-400 py-12">Carregando...</div>';
      const res = await fetch(`api/giras.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      girasCache = data.data || [];
      renderGiras();
    };

    const loadTipos = async () => {
      const res = await fetch(`api/giras.php?action=list_tipos&t=${Date.now()}`, { cache: 'no-store' });
      const data = await res.json();
      tiposCache = data.data || [];
    };

    // ── Render cards ──
    const renderGiras = () => {
      const rows = girasCache.filter(r => matchesPlatformFilter(r.plataforma, currentFilter));
      if (!rows.length) {
        girasGrid.innerHTML = '<div class="col-span-full text-center text-slate-400 py-12">Nenhuma gira registrada.</div>';
        return;
      }
      girasGrid.innerHTML = rows.map(g => `
        <div class="bg-white/90 backdrop-blur border ${platformBg(g.plataforma)} rounded-2xl shadow-lg shadow-slate-200/40 overflow-hidden cursor-pointer hover:shadow-xl transition-shadow" data-id="${g.id}">
          ${g.foto_path
            ? `<div class="p-3 flex justify-center"><img src="${g.foto_path}" class="h-20 w-20 object-cover rounded-xl cursor-zoom-in gira-thumb" data-full="${g.foto_path}" /></div>`
            : `<div class="p-3 flex justify-center"><div class="h-20 w-20 bg-gradient-to-br from-red-100 to-red-50 rounded-xl flex items-center justify-center text-red-300 text-3xl"><i class="fa-solid fa-drum"></i></div></div>`
          }
          <div class="p-4 pt-0 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-sm">${g.tipo_gira_nome}</span>
              <span class="flex gap-1 text-lg">${(g.plataforma || '').split(',').map(x => platformIcon(x.trim())).join('')}</span>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-500">
              <span><i class="fa-solid fa-calendar text-red-400 mr-1"></i>Gira: ${fmtDate(g.data_realizacao)}</span>
              ${g.data_postagem ? `<span><i class="fa-solid fa-share text-blue-400 mr-1"></i>Post: ${fmtDate(g.data_postagem)}</span>` : ''}
            </div>
            ${g.descricao ? `<p class="text-xs text-slate-400 line-clamp-2">${g.descricao}</p>` : ''}
          </div>
        </div>
      `).join('');
    };

    // ── Render tipos table ──
    const renderTipos = () => {
      const el = document.getElementById('tiposTable');
      if (!tiposCache.length) {
        el.innerHTML = '<tr><td colspan="2" class="py-4 text-center text-slate-400">Nenhum tipo cadastrado.</td></tr>';
        return;
      }
      el.innerHTML = tiposCache.map(t => `
        <tr class="border-t border-slate-100">
          <td class="py-2 font-medium">${t.nome}</td>
          <td class="py-2 text-right">
            <button class="text-red-400 hover:text-red-600 text-xs" data-tipo-delete="${t.id}"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      `).join('');
    };

    // ── Populate tipos select ──
    const populateTiposSelect = () => {
      const sel = document.getElementById('giraTipo');
      const cur = sel.value;
      sel.innerHTML = '<option value="">Selecione...</option>' +
        tiposCache.map(t => `<option value="${t.id}" ${cur == t.id ? 'selected' : ''}>${t.nome}</option>`).join('');
    };

    // ── Open new modal ──
    const openNewModal = async () => {
      document.getElementById('giraId').value = '';
      setSelectedPlatforms('Instagram');
      document.getElementById('giraDataRealizacao').value = new Date().toISOString().split('T')[0];
      document.getElementById('giraDataPostagem').value = '';
      document.getElementById('giraDescricao').value = '';
      document.getElementById('giraFoto').value = '';
      document.getElementById('fotoPreview').innerHTML = '';
      document.getElementById('modalTitle').textContent = 'Nova Gira';
      if (!tiposCache.length) await loadTipos();
      populateTiposSelect();
      toggleModal(modal, true);
    };

    // ── Event listeners ──
    document.getElementById('openModal').addEventListener('click', openNewModal);
    document.getElementById('fabAction').addEventListener('click', openNewModal);
    document.getElementById('closeModal').addEventListener('click', () => toggleModal(modal, false));
    document.getElementById('cancelModal').addEventListener('click', () => toggleModal(modal, false));

    // Foto preview
    document.getElementById('giraFoto').addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (ev) => {
          document.getElementById('fotoPreview').innerHTML = `<img src="${ev.target.result}" class="h-24 rounded-xl border border-slate-200" />`;
        };
        reader.readAsDataURL(file);
      }
    });

    // ── Save gira ──
    document.getElementById('giraForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const plataformas = getSelectedPlatforms();
      if (!plataformas) { alert('Selecione pelo menos uma plataforma'); return; }
      const id = document.getElementById('giraId').value;
      const formData = new FormData();
      formData.append('action', id ? 'update' : 'create');
      if (id) formData.append('id', id);
      formData.append('tipo_gira_id', document.getElementById('giraTipo').value);
      formData.append('plataforma', plataformas);
      formData.append('data_realizacao', document.getElementById('giraDataRealizacao').value);
      formData.append('data_postagem', document.getElementById('giraDataPostagem').value);
      formData.append('descricao', document.getElementById('giraDescricao').value);
      const fotoFile = document.getElementById('giraFoto').files[0];
      if (fotoFile) formData.append('foto', fotoFile);

      const res = await fetch('api/giras.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (!data.ok) {
        alert(data.message || 'Erro ao salvar');
        return;
      }
      toggleModal(modal, false);
      loadGiras();
    });

    // ── Tipos modal ──
    document.getElementById('openTiposModal').addEventListener('click', async () => {
      await loadTipos();
      renderTipos();
      toggleModal(tiposModal, true);
    });
    document.getElementById('closeTiposModal').addEventListener('click', () => toggleModal(tiposModal, false));

    document.getElementById('addTipoBtn').addEventListener('click', async () => {
      const input = document.getElementById('novoTipoInput');
      const nome = input.value.trim();
      if (!nome) { alert('Digite o nome do tipo'); return; }
      await fetch('api/giras.php', { method: 'POST', body: new URLSearchParams({ action: 'create_tipo', nome }) });
      input.value = '';
      await loadTipos();
      renderTipos();
    });

    document.getElementById('tiposTable').addEventListener('click', async (e) => {
      const deleteId = e.target.closest('[data-tipo-delete]')?.dataset.tipoDelete;
      if (deleteId) {
        if (!confirm('Excluir este tipo de gira?')) return;
        await fetch('api/giras.php', { method: 'POST', body: new URLSearchParams({ action: 'delete_tipo', id: deleteId }) });
        await loadTipos();
        renderTipos();
      }
    });

    // ── Card click → detail ──
    girasGrid.addEventListener('click', (e) => {
      const card = e.target.closest('[data-id]');
      if (!card) return;
      const g = girasCache.find(x => String(x.id) === card.dataset.id);
      if (!g) return;
      currentDetalheId = g.id;
      document.getElementById('detalheTitle').textContent = g.tipo_gira_nome;
      document.getElementById('detalheBody').innerHTML = `
        ${g.foto_path ? `<div class="flex justify-center mb-3"><img src="${g.foto_path}" class="h-24 w-24 object-cover rounded-xl cursor-zoom-in border border-slate-200 gira-thumb" data-full="${g.foto_path}" /></div>` : ''}
        <div class="flex justify-between"><span class="text-slate-500">Plataformas:</span> <span class="font-medium flex gap-2">${platformBadges(g.plataforma)}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Data da Gira:</span> <span class="font-medium"><i class="fa-regular fa-calendar text-red-500 mr-1"></i>${fmtDate(g.data_realizacao)}</span></div>
        ${g.data_postagem ? `<div class="flex justify-between"><span class="text-slate-500">Data da Postagem:</span> <span class="font-medium">${fmtDate(g.data_postagem)}</span></div>` : ''}
        ${g.descricao ? `<div class="pt-2 border-t border-slate-100"><p class="text-slate-500 mb-1">Descrição:</p><p>${g.descricao}</p></div>` : ''}
      `;
      toggleModal(detalheModal, true);
    });

    // ── Detail actions ──
    document.getElementById('closeDetalheModal').addEventListener('click', () => toggleModal(detalheModal, false));

    document.getElementById('detalheDelete').addEventListener('click', async () => {
      if (!confirm('Excluir este registro de gira?')) return;
      await fetch('api/giras.php', { method: 'POST', body: new URLSearchParams({ action: 'delete', id: currentDetalheId }) });
      toggleModal(detalheModal, false);
      loadGiras();
    });

    document.getElementById('detalheEdit').addEventListener('click', async () => {
      toggleModal(detalheModal, false);
      const g = girasCache.find(x => String(x.id) === String(currentDetalheId));
      if (!g) return;
      document.getElementById('giraId').value = g.id;
      setSelectedPlatforms(g.plataforma);
      document.getElementById('giraDataRealizacao').value = g.data_realizacao;
      document.getElementById('giraDataPostagem').value = g.data_postagem || '';
      document.getElementById('giraDescricao').value = g.descricao || '';
      document.getElementById('giraFoto').value = '';
      document.getElementById('fotoPreview').innerHTML = g.foto_path
        ? `<img src="${g.foto_path}" class="h-24 rounded-xl border border-slate-200" />`
        : '';
      document.getElementById('modalTitle').textContent = 'Editar Gira';
      if (!tiposCache.length) await loadTipos();
      populateTiposSelect();
      document.getElementById('giraTipo').value = g.tipo_gira_id;
      toggleModal(modal, true);
    });

    // ── Escape key ──
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggleModal(modal, false);
        toggleModal(tiposModal, false);
        toggleModal(detalheModal, false);
      }
    });

    // ── Init ──
    loadTipos().then(() => loadGiras());

    // ── Lightbox: click thumbnail → expand image ──
    document.addEventListener('click', (e) => {
      const thumb = e.target.closest('.gira-thumb');
      if (thumb) {
        e.stopPropagation();
        const lb = document.getElementById('lightbox');
        document.getElementById('lightboxImg').src = thumb.dataset.full || thumb.src;
        lb.classList.remove('hidden');
        lb.classList.add('flex');
      }
    });
  </script>
</body>
</html>
