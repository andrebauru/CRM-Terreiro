<?php
$pageTitle = 'CRM Terreiro - Avisos';
$activePage = 'avisos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
$isAdminAvisos = ($_SESSION['user_role'] ?? '') === 'admin';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Quadro de Avisos</h1>
          <p class="text-slate-500">Comunicados visíveis para quem tem acesso a esta página</p>
        </div>
        <?php if ($isAdminAvisos): ?>
          <button id="openAvisoModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
            <i class="fa-solid fa-plus mr-2"></i>Novo Aviso
          </button>
        <?php endif; ?>
      </header>

      <?php if ($isAdminAvisos): ?>
      <section class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 text-sm text-amber-800">
        <i class="fa-solid fa-circle-info mr-2"></i>
        Os avisos ativos publicados aqui ficam visíveis para todos os usuários que tiverem acesso à página <strong>Avisos</strong>.
      </section>
      <?php endif; ?>

      <section class="space-y-4" id="avisosList">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-400">Carregando avisos...</div>
      </section>
    </main>
  </div>

  <?php if ($isAdminAvisos): ?>
  <div id="avisoModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-2xl p-6 border border-slate-200 shadow-2xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="avisoModalTitle">Novo Aviso</h2>
        <button id="closeAvisoModal" class="text-slate-400 hover:text-red-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="avisoForm" class="space-y-4">
        <input type="hidden" id="avisoId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Título</label>
          <input id="avisoTitulo" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" required />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Mensagem</label>
          <textarea id="avisoMensagem" rows="6" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" required></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input id="avisoAtivo" type="checkbox" checked /> Aviso ativo
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" id="cancelAvisoModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const isAdminAvisos = <?= $isAdminAvisos ? 'true' : 'false' ?>;
    const avisosList = document.getElementById('avisosList');
    let avisosCache = [];

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatAvisoDate(value) {
      if (!value) return '-';
      const date = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? value : date.toLocaleString('pt-BR');
    }

    async function loadAvisos() {
      const response = await fetch('api/avisos.php?action=list', { cache: 'no-store' });
      const data = await response.json();
      if (!data.ok) {
        avisosList.innerHTML = '<div class="bg-white border border-red-200 rounded-2xl p-6 text-red-500">Erro ao carregar avisos.</div>';
        return;
      }
      avisosCache = data.data || [];
      renderAvisos();
    }

    function renderAvisos() {
      if (!avisosCache.length) {
        avisosList.innerHTML = '<div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-400">Nenhum aviso disponível.</div>';
        return;
      }

      avisosList.innerHTML = avisosCache.map((aviso) => `
        <article class="bg-white border ${aviso.is_active == 1 ? 'border-rose-200' : 'border-slate-200'} rounded-2xl p-6 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-4 mb-3">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <h2 class="text-lg font-bold text-slate-900">${escapeHtml(aviso.titulo)}</h2>
                <span class="px-2 py-1 rounded-full text-xs font-bold ${aviso.is_active == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                  ${aviso.is_active == 1 ? 'Ativo' : 'Inativo'}
                </span>
              </div>
              <p class="text-xs text-slate-400">Atualizado em ${formatAvisoDate(aviso.updated_at || aviso.created_at)}</p>
            </div>
            ${isAdminAvisos ? `
              <div class="flex gap-2">
                <button onclick="editAviso(${aviso.id})" class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200">Editar</button>
                <button onclick="deleteAviso(${aviso.id})" class="px-3 py-1 rounded-lg bg-red-100 text-red-700 text-xs font-bold hover:bg-red-200">Excluir</button>
              </div>
            ` : ''}
          </div>
          <div class="text-slate-700 whitespace-pre-wrap leading-7">${escapeHtml(aviso.mensagem)}</div>
        </article>
      `).join('');
    }

    if (isAdminAvisos) {
      const avisoModal = document.getElementById('avisoModal');
      const avisoForm = document.getElementById('avisoForm');
      const avisoModalTitle = document.getElementById('avisoModalTitle');
      const avisoId = document.getElementById('avisoId');
      const avisoTitulo = document.getElementById('avisoTitulo');
      const avisoMensagem = document.getElementById('avisoMensagem');
      const avisoAtivo = document.getElementById('avisoAtivo');

      function toggleAvisoModal(show) { toggleModal(avisoModal, show); }
      function resetAvisoForm() {
        avisoId.value = '';
        avisoTitulo.value = '';
        avisoMensagem.value = '';
        avisoAtivo.checked = true;
        avisoModalTitle.textContent = 'Novo Aviso';
      }

      document.getElementById('openAvisoModal').addEventListener('click', () => {
        resetAvisoForm();
        toggleAvisoModal(true);
      });
      document.getElementById('closeAvisoModal').addEventListener('click', () => toggleAvisoModal(false));
      document.getElementById('cancelAvisoModal').addEventListener('click', () => toggleAvisoModal(false));

      window.editAviso = function editAviso(id) {
        const aviso = avisosCache.find((item) => String(item.id) === String(id));
        if (!aviso) return;
        avisoId.value = aviso.id;
        avisoTitulo.value = aviso.titulo || '';
        avisoMensagem.value = aviso.mensagem || '';
        avisoAtivo.checked = Number(aviso.is_active) === 1;
        avisoModalTitle.textContent = 'Editar Aviso';
        toggleAvisoModal(true);
      };

      window.deleteAviso = async function deleteAviso(id) {
        if (!confirm('Excluir este aviso?')) return;
        const body = new URLSearchParams({ action: 'delete', id });
        const response = await fetch('api/avisos.php', { method: 'POST', body });
        const data = await response.json();
        if (!data.ok) {
          alert(data.message || 'Erro ao excluir aviso');
          return;
        }
        await loadAvisos();
      };

      avisoForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = new URLSearchParams({
          action: avisoId.value ? 'update' : 'create',
          id: avisoId.value,
          titulo: avisoTitulo.value.trim(),
          mensagem: avisoMensagem.value.trim(),
          is_active: avisoAtivo.checked ? '1' : '0',
        });
        const response = await fetch('api/avisos.php', { method: 'POST', body });
        const data = await response.json();
        if (!data.ok) {
          alert(data.message || 'Erro ao salvar aviso');
          return;
        }
        toggleAvisoModal(false);
        await loadAvisos();
      });
    }

    document.addEventListener('DOMContentLoaded', loadAvisos);
  </script>
</body>
</html>
