<?php
$pageTitle = 'CRM Terreiro - Usuários';
$activePage = 'usuarios';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 p-8">
      <header class="flex items-center justify-between mb-8">
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
                <th class="text-left pb-3">Perfil</th>
                <th class="text-left pb-3">Status</th>
                <th class="text-right pb-3">Ações</th>
              </tr>
            </thead>
            <tbody id="usersTable">
              <tr>
                <td class="py-3" colspan="5">Carregando...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 border border-slate-200">
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
          <label class="text-sm font-medium text-slate-700">Perfil</label>
          <select id="userRole" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="admin">Administrador</option>
            <option value="staff">Usuário</option>
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
        <div class="flex justify-end gap-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-lg border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-accent text-white">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
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
    const userRole = document.getElementById('userRole');
    const userActive = document.getElementById('userActive');
    const userPassword = document.getElementById('userPassword');

    let usersCache = [];

    const openFn = (show) => toggleModal(modal, show);

    const resetForm = () => {
      userId.value = '';
      userName.value = '';
      userEmail.value = '';
      userRole.value = 'staff';
      userActive.value = '1';
      userPassword.value = '';
    };

    const loadUsers = async () => {
      usersTable.innerHTML = '<tr><td class="py-3" colspan="5">Carregando...</td></tr>';
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
          <td class="py-3">${user.role}</td>
          <td class="py-3">${user.is_active == 1 ? 'Ativo' : 'Inativo'}</td>
          <td class="py-3 text-right">
            <button class="text-accent" data-edit="${user.id}">Editar</button>
            <button class="text-red-500 ml-3" data-delete="${user.id}">Excluir</button>
          </td>
        </tr>
      `).join('');
      usersTable.innerHTML = html || '<tr><td class="py-3" colspan="5">Nenhum usuário encontrado.</td></tr>';
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
        userRole.value = user.role || 'staff';
        userActive.value = String(user.is_active ?? 1);
        userPassword.value = '';
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
        role: userRole.value,
        is_active: userActive.value,
        password: userPassword.value,
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