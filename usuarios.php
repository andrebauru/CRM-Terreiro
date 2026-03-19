<?php
$pageTitle = 'CRM Terreiro - Usuários';
$activePage = 'usuarios';
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

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Usuários</h1>
          <p class="text-slate-500">Controle de acessos</p>
        </div>
        <button id="openModal" class="px-4 py-2 rounded-lg bg-accent text-white font-medium">Adicionar Usuário</button>
      </header>

      <section class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Lista de Usuários</h2>
          <input id="searchInput" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Buscar..." />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Nome</th>
                <th class="text-left pb-3">Email</th>
                <th class="text-left pb-3">Telefone</th>
                <th class="text-left pb-3">Perfil</th>
                <th class="text-left pb-3">Status</th>
                <th class="text-right pb-3">Ações</th>
              </tr>
            </thead>
            <tbody id="usersTable">
              <tr>
                <td class="py-3" colspan="6">Carregando...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="modalTitle">Novo Usuário</h2>
        <button id="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <form id="userForm" class="space-y-4">
        <input type="hidden" id="userId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Nome</label>
          <input id="userName" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Email</label>
          <input id="userEmail" type="email" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Telefone</label>
          <input id="userPhone" type="tel" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="(00) 00000-0000" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Perfil</label>
          <select id="userRole" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="admin">Administrador</option>
            <option value="staff">Equipe</option>
            <option value="user">Usuário (só Financeiro)</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Status</label>
          <select id="userActive" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="1">Ativo</option>
            <option value="0">Inativo</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Senha (opcional)</label>
          <input id="userPassword" type="password" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Deixe vazio para manter" />
        </div>
        <div id="allowedPagesSection">
          <label class="text-sm font-medium text-slate-700 block mb-2">Páginas Permitidas <span class="text-slate-400 text-xs">(admin vê tudo)</span></label>
          <div id="allowedPagesGrid" class="grid grid-cols-2 gap-2">
            <?php
            $allPages = [
              'atendimentos' => 'Atendimentos',
              'gastos' => 'Gastos',
              'trabalhos' => 'Trabalhos',
              'clientes' => 'Clientes',
              'filhos' => 'Filhos',
              'quimbandeiro' => 'Quimbandeiro',
              'mensalidades' => 'Mensalidades',
              'giras' => 'Registro de Giras',
              'servicos' => 'Serviços',
              'financeiro' => 'Financeiro',
              'usuarios' => 'Usuários',
              'relatorios' => 'Relatórios',
              'configuracoes' => 'Configurações',
            ];
            foreach ($allPages as $pageKey => $pageLabel): ?>
              <label class="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-100">
                <input type="checkbox" class="page-check rounded border-slate-300" value="<?= $pageKey ?>" />
                <?= htmlspecialchars($pageLabel) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="flex gap-2 mt-2">
            <button type="button" onclick="checkAllPages(true)" class="text-xs text-blue-600 hover:underline">Marcar todos</button>
            <button type="button" onclick="checkAllPages(false)" class="text-xs text-blue-600 hover:underline">Desmarcar todos</button>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-lg border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white">Salvar</button>
        </div>
      </form>
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
            body: new URLSearchParams({ action: 'log_event', event: 'print_screen', page: 'usuarios', user_agent: navigator.userAgent }),
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
    const modal = document.getElementById('modal');
    const openModal = document.getElementById('openModal');
    const closeModal = document.getElementById('closeModal');
    const cancelModal = document.getElementById('cancelModal');
    const userForm = document.getElementById('userForm');
    const usersTable = document.getElementById('usersTable');
    const searchInput = document.getElementById('searchInput');
    const modalTitle = document.getElementById('modalTitle');

    const userId = document.getElementById('userId');
    const userName = document.getElementById('userName');
    const userEmail = document.getElementById('userEmail');
    const userPhone = document.getElementById('userPhone');
    const userRole = document.getElementById('userRole');
    const userActive = document.getElementById('userActive');
    const userPassword = document.getElementById('userPassword');

    let usersCache = [];

    const openFn = (show) => toggleModal(modal, show);

    function checkAllPages(checked) {
      document.querySelectorAll('.page-check').forEach(cb => cb.checked = checked);
    }

    function getSelectedPages() {
      return Array.from(document.querySelectorAll('.page-check:checked')).map(cb => cb.value).join(',');
    }

    function setSelectedPages(csv) {
      document.querySelectorAll('.page-check').forEach(cb => cb.checked = false);
      if (!csv) return;
      const pages = csv.split(',').map(s => s.trim());
      document.querySelectorAll('.page-check').forEach(cb => {
        if (pages.includes(cb.value)) cb.checked = true;
      });
    }

    const resetForm = () => {
      userId.value = '';
      userName.value = '';
      userEmail.value = '';
      userPhone.value = '';
      userRole.value = 'staff';
      userActive.value = '1';
      userPassword.value = '';
      checkAllPages(false);
    };

    const loadUsers = async () => {
      usersTable.innerHTML = '<tr><td class="py-3" colspan="6">Carregando...</td></tr>';
      const response = await fetch(`api/users.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await response.json();
      usersCache = data.data || [];
      renderUsers(usersCache);
    };

    const renderUsers = (rows) => {
      const html = rows.map((user) => `
        <tr class="border-t border-slate-100">
          <td class="py-3">${user.name}</td>
          <td class="py-3">${user.email}</td>
          <td class="py-3 text-slate-500 text-xs">${user.phone || '-'}</td>
          <td class="py-3">${user.role}</td>
          <td class="py-3">${user.is_active == 1 ? 'Ativo' : 'Inativo'}</td>
          <td class="py-3 text-right">
            <button class="text-accent" data-edit="${user.id}">Editar</button>
            <button class="text-red-500 ml-3" data-delete="${user.id}">Excluir</button>
          </td>
        </tr>
      `).join('');
      usersTable.innerHTML = html || '<tr><td class="py-3" colspan="6">Nenhum usuário encontrado.</td></tr>';
    };

    openModal.addEventListener('click', () => {
      resetForm();
      modalTitle.textContent = 'Novo Usuário';
      openFn(true);
    });

    [closeModal, cancelModal].forEach((btn) => btn.addEventListener('click', () => openFn(false)));

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') openFn(false);
    });

    usersTable.addEventListener('click', (event) => {
      const editId = event.target.getAttribute('data-edit');
      const deleteId = event.target.getAttribute('data-delete');

      if (editId) {
        const user = usersCache.find((item) => String(item.id) === editId);
        if (!user) return;
        userId.value = user.id;
        userName.value = user.name || '';
        userEmail.value = user.email || '';
        userPhone.value = user.phone || '';
        userRole.value = user.role || 'staff';
        userActive.value = String(user.is_active ?? 1);
        userPassword.value = '';
        setSelectedPages(user.allowed_pages || '');
        modalTitle.textContent = 'Editar Usuário';
        openFn(true);
      }

      if (deleteId) {
        if (!confirm('Deseja excluir este usuário?')) return;
        fetch('api/users.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'delete', id: deleteId }),
        }).then(() => loadUsers());
      }
    });

    userForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const payload = new URLSearchParams({
        action: userId.value ? 'update' : 'create',
        id: userId.value,
        name: userName.value,
        email: userEmail.value,
        phone: userPhone.value,
        role: userRole.value,
        is_active: userActive.value,
        password: userPassword.value,
        allowed_pages: getSelectedPages(),
      });
      fetch('api/users.php', { method: 'POST', body: payload })
        .then(() => {
          openFn(false);
          loadUsers();
        });
    });

    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase();
      const filtered = usersCache.filter((user) =>
        [user.name, user.email, user.role].some((value) => (value || '').toLowerCase().includes(term))
      );
      renderUsers(filtered);
    });

    loadUsers();
  </script>
</body>
</html>