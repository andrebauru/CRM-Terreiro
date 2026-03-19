<?php
$pageTitle = 'CRM Terreiro - Catálogo de Serviços';
$activePage = 'servicos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Catálogo de Serviços</h1>
          <p class="text-slate-500">Tipos de serviços disponíveis</p>
        </div>
        <button id="openModal" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">
          <i class="fa-solid fa-plus mr-2"></i>Adicionar Serviço
        </button>
      </header>

      <section class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Lista de Serviços</h2>
          <input id="searchInput" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Buscar..." />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Nome</th>
                <th class="text-left pb-3">Preço</th>
                <th class="text-left pb-3">Status</th>
                <th class="text-right pb-3">Ações</th>
              </tr>
            </thead>
            <tbody id="servicesTable">
              <tr><td class="py-3" colspan="4">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 border border-slate-200">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold" id="modalTitle">Novo Serviço</h2>
        <button id="closeModal" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <form id="serviceForm" class="space-y-4">
        <input type="hidden" id="serviceId" />
        <div>
          <label class="text-sm font-medium text-slate-700">Nome do Serviço</label>
          <input id="serviceName" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Preço (<?= $_crmCurrSymbol ?>)</label>
          <input id="servicePrice" data-mask="currency" inputmode="numeric" placeholder="<?= $_crmCurrSymbol ?>0" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Descrição</label>
          <textarea id="serviceDescription" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2" rows="3"></textarea>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700">Status</label>
          <select id="serviceActive" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2">
            <option value="1">Ativo</option>
            <option value="0">Inativo</option>
          </select>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="cancelModal" class="px-4 py-2 rounded-lg border border-slate-200">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
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
    const serviceForm = document.getElementById('serviceForm');
    const servicesTable = document.getElementById('servicesTable');
    const searchInput = document.getElementById('searchInput');
    const modalTitle = document.getElementById('modalTitle');

    const serviceId = document.getElementById('serviceId');
    const serviceName = document.getElementById('serviceName');
    const servicePrice = document.getElementById('servicePrice');
    const serviceDescription = document.getElementById('serviceDescription');
    const serviceActive = document.getElementById('serviceActive');

    let servicesCache = [];

    const openFn = (show) => toggleModal(modal, show);

    const resetForm = () => {
      serviceId.value = '';
      serviceName.value = '';
      servicePrice.value = '';
      serviceDescription.value = '';
      serviceActive.value = '1';
    };

    const loadServices = async () => {
      servicesTable.innerHTML = '<tr><td class="py-3" colspan="4">Carregando...</td></tr>';
      const response = await fetch(`api/services.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await response.json();
      servicesCache = data.data || [];
      renderServices(servicesCache);
    };

    const renderServices = (rows) => {
      const html = rows.map((service) => `
        <tr class="border-t border-slate-100">
          <td class="py-3">${service.name}</td>
          <td class="py-3">${formatBRL(String(Math.round(parseFloat(service.price) || 0)))}</td>
          <td class="py-3">${service.is_active == 1 ? 'Ativo' : 'Inativo'}</td>
          <td class="py-3 text-right">
            <button class="text-accent" data-edit="${service.id}">Editar</button>
            <button class="text-red-500 ml-3" data-delete="${service.id}">Excluir</button>
          </td>
        </tr>
      `).join('');
      servicesTable.innerHTML = html || '<tr><td class="py-3" colspan="4">Nenhum serviço encontrado.</td></tr>';
    };

    openModal.addEventListener('click', () => {
      resetForm();
      modalTitle.textContent = 'Novo Serviço';
      openFn(true);
    });

    [closeModal, cancelModal].forEach((btn) => btn.addEventListener('click', () => openFn(false)));

    servicePrice.addEventListener('input', () => {
      servicePrice.value = formatBRL(servicePrice.value);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') openFn(false);
    });

    servicesTable.addEventListener('click', (event) => {
      const editId = event.target.getAttribute('data-edit');
      const deleteId = event.target.getAttribute('data-delete');

      if (editId) {
        const service = servicesCache.find((item) => String(item.id) === editId);
        if (!service) return;
        serviceId.value = service.id;
        serviceName.value = service.name || '';
        servicePrice.value = service.price ? formatBRL(String(Math.round(parseFloat(service.price)))) : '';
        serviceDescription.value = service.description || '';
        serviceActive.value = String(service.is_active ?? 1);
        modalTitle.textContent = 'Editar Serviço';
        openFn(true);
      }

      if (deleteId) {
        if (!confirm('Deseja excluir este serviço?')) return;
        fetch('api/services.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'delete', id: deleteId }),
        }).then(() => loadServices());
      }
    });

    serviceForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const payload = new URLSearchParams({
        action: serviceId.value ? 'update' : 'create',
        id: serviceId.value,
        name: serviceName.value,
        description: serviceDescription.value,
        price: parseBRL(servicePrice.value),
        is_active: serviceActive.value,
      });
      fetch('api/services.php', { method: 'POST', body: payload })
        .then(() => { openFn(false); loadServices(); });
    });

    searchInput.addEventListener('input', () => {
      const term = searchInput.value.toLowerCase();
      const filtered = servicesCache.filter((service) =>
        [service.name, service.description].some((value) => (value || '').toLowerCase().includes(term))
      );
      renderServices(filtered);
    });

    loadServices();
  </script>
</body>
</html>