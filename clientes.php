<?php
$pageTitle = 'CRM Terreiro - Clientes';
$activePage = 'clientes';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Clientes</h1>
          <p class="text-slate-500">Gerencie o cadastro de clientes</p>
        </div>
        <button id="openModal" class="px-4 py-2 rounded-lg bg-accent text-white font-medium">Adicionar Cliente</button>
      </header>

      <section class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Lista de Clientes</h2>
          <input id="searchInput" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Buscar..." />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Nome</th>
                <th class="text-left pb-3">Email</th>
                <th class="text-left pb-3">Telefone</th>
                <th class="text-right pb-3">Ações</th>
              </tr>
            </thead>
            <tbody id="clientsTable">
              <tr>
                <td class="py-3" colspan="4">Carregando...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 border border-slate-200">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="modalTitle">Novo Cliente</h2>
        <button id="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <form id="clientForm" class="space-y-4">
        <input type="hidden" id="clientId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Nome</label>
          <input id="clientName" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Email</label>
          <input id="clientEmail" type="email" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Telefone</label>
          <input id="clientPhone" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Endereço</label>
          <textarea id="clientAddress" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" rows="3"></textarea>
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
    const clientForm = document.getElementById('clientForm');
    const clientsTable = document.getElementById('clientsTable');
    const searchInput = document.getElementById('searchInput');
    const modalTitle = document.getElementById('modalTitle');

    const clientId = document.getElementById('clientId');
    const clientName = document.getElementById('clientName');
    const clientEmail = document.getElementById('clientEmail');
    const clientPhone = document.getElementById('clientPhone');
    const clientAddress = document.getElementById('clientAddress');

    let clientsCache = [];

    const openModalFn = (show) => toggleModal(modal, show);

    const resetForm = () => {
      clientId.value = '';
      clientName.value = '';
      clientEmail.value = '';
      clientPhone.value = '';
      clientAddress.value = '';
    };

    const loadClients = async () => {
      clientsTable.innerHTML = '<tr><td class="py-3" colspan="4">Carregando...</td></tr>';
      try {
        const response = await fetch(`api/clients.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
        const data = await response.json();
        clientsCache = data.data || [];
        renderClients(clientsCache);
      } catch (error) {
        clientsTable.innerHTML = '<tr><td class="py-3" colspan="4">Erro ao carregar.</td></tr>';
      }
    };

    const formatWhatsapp = (phone) => {
      const digits = String(phone || '').replace(/\D+/g, '');
      if (!digits) return '';
      return digits.startsWith('81') ? `https://wa.me/${digits}` : `https://wa.me/81${digits}`;
    };

    const renderClients = (rows) => {
      const html = rows.map((client) => `
        <tr class="border-t border-slate-100">
          <td class="py-3">${client.name}</td>
          <td class="py-3">${client.email ?? '-'}</td>
          <td class="py-3">
            ${client.phone ? `<a href="${formatWhatsapp(client.phone)}" class="text-accent" target="_blank">${client.phone}</a>` : '-'}
          </td>
          <td class="py-3 text-right">
            <button class="text-accent" data-edit="${client.id}">Editar</button>
            <button class="text-red-500 ml-3" data-delete="${client.id}">Excluir</button>
          </td>
        </tr>
      `).join('');
      clientsTable.innerHTML = html || '<tr><td class="py-3" colspan="4">Nenhum cliente encontrado.</td></tr>';
    };

    openModal.addEventListener('click', () => {
      resetForm();
      modalTitle.textContent = 'Novo Cliente';
      openModalFn(true);
    });

    [closeModal, cancelModal].forEach((btn) => btn.addEventListener('click', () => openModalFn(false)));

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') openModalFn(false);
    });

    clientsTable.addEventListener('click', (event) => {
      const editId = event.target.getAttribute('data-edit');
      const deleteId = event.target.getAttribute('data-delete');

      if (editId) {
        const client = clientsCache.find((item) => String(item.id) === editId);
        if (!client) return;
        clientId.value = client.id;
        clientName.value = client.name || '';
        clientEmail.value = client.email || '';
        clientPhone.value = client.phone || '';
        clientAddress.value = client.address || '';
        modalTitle.textContent = 'Editar Cliente';
        openModalFn(true);
      }

      if (deleteId) {
        if (!confirm('Deseja excluir este cliente?')) return;
        fetch('api/clients.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'delete', id: deleteId }),
        }).then(() => loadClients());
      }
    });

    clientForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const payload = new URLSearchParams({
        action: clientId.value ? 'update' : 'create',
        id: clientId.value,
        name: clientName.value,
        email: clientEmail.value,
        phone: clientPhone.value,
        address: clientAddress.value,
      });
      fetch('api/clients.php', { method: 'POST', body: payload })
        .then((res) => res.json())
        .then(async () => {
          openModalFn(false);
          await loadClients();
        });
    });

    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase();
      const filtered = clientsCache.filter((client) =>
        [client.name, client.email, client.phone].some((value) => (value || '').toLowerCase().includes(term))
      );
      renderClients(filtered);
    });

    loadClients();
  </script>
</body>
</html>