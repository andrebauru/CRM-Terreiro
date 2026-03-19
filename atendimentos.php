<?php
$pageTitle = 'CRM Terreiro - Atendimentos';
$activePage = 'atendimentos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Registro de Atendimentos</h1>
          <p class="text-slate-500">Cadastre serviços e pagamentos em um único fluxo</p>
        </div>
        <div class="hidden md:flex items-center gap-2">
          <span class="text-xs text-slate-400">Atualizado em tempo real</span>
          <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
        </div>
      </header>

      <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
          <div class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
            <h2 class="text-lg font-semibold mb-4">Cadastro de Atendimento</h2>
            <form id="attendanceForm" class="space-y-4">
              <div>
                <label class="text-sm font-medium text-slate-700">Cliente</label>
                <div class="flex flex-col gap-2">
                  <select id="clientSelect" class="rounded-xl border border-slate-200 px-3 py-2"></select>
                  <div class="flex items-center justify-between">
                    <a id="whatsappLink" class="text-sm text-accent hidden" target="_blank">Abrir WhatsApp</a>
                    <button id="goServices" type="button" class="text-sm text-slate-500">Ir para serviços</button>
                  </div>
                </div>
              </div>
              <div>
                <div class="flex items-center justify-between">
                  <label class="text-sm font-medium text-slate-700">Serviços</label>
                  <span class="text-xs text-slate-500" id="servicesCount">0 selecionados</span>
                </div>
                <div id="servicesList" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">Anotações Gerais</label>
                <textarea id="notes" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" rows="3"></textarea>
              </div>
              <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-500">Total</span>
                  <span class="text-xl font-semibold" id="totalAmount"><?= $_crmCurrSymbol ?>0</span>
                </div>
                <div class="mt-4 flex gap-4">
                  <label class="flex items-center gap-2 text-sm"><input type="radio" name="paymentType" value="cash" checked /> À Vista</label>
                  <label class="flex items-center gap-2 text-sm"><input type="radio" name="paymentType" value="installments" /> Parcelado</label>
                </div>
                <div id="installmentsFields" class="mt-4 grid grid-cols-2 gap-4 hidden">
                  <div>
                    <label class="text-sm text-slate-600">Qtd Parcelas</label>
                    <input id="installmentsCount" type="number" min="1" value="2" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
                  </div>
                  <div>
                    <label class="text-sm text-slate-600">Dia de Vencimento (1-28)</label>
                    <input id="dueDay" type="number" min="1" max="28" value="5" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
                  </div>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center gap-2 text-sm"><input id="isDelinquent" type="checkbox" /> Inadimplente</label>
                <label class="flex items-center gap-2 text-sm"><input id="isReversed" type="checkbox" /> Reverter Trabalho</label>
              </div>
              <button type="submit" class="w-full rounded-2xl bg-accent py-3 text-white font-semibold shadow-lg hover:opacity-90">Salvar Atendimento</button>
            </form>
          </div>

          <div class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold">Atendimentos Recentes</h2>
              <button id="refreshAttendances" class="text-sm text-accent">Atualizar</button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="text-slate-500">
                  <tr>
                    <th class="text-left pb-3">Cliente</th>
                    <th class="text-left pb-3">Serviços</th>
                    <th class="text-left pb-3">Pagamento</th>
                    <th class="text-right pb-3">Total</th>
                  </tr>
                </thead>
                <tbody id="attendancesTable">
                  <tr><td class="py-3" colspan="4">Carregando...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
            <h2 class="text-lg font-semibold mb-4">Parcelas do Atendimento</h2>
            <div id="installmentsPanel" class="text-sm text-slate-500">Selecione um atendimento para ver as parcelas.</div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <div id="editModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-3xl w-full max-w-xl p-6 border border-slate-200">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Editar Atendimento</h2>
        <button id="closeEditModal" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="space-y-3">
        <div class="text-sm text-slate-500">Cliente</div>
        <div id="editClient" class="font-semibold"></div>
        <div class="text-sm text-slate-500">Serviços</div>
        <div id="editServices" class="text-sm"></div>
        <div>
          <label class="text-sm font-medium text-slate-700">Anotações</label>
          <textarea id="editNotes" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" rows="3"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="flex items-center gap-2 text-sm"><input id="editDelinquent" type="checkbox" /> Inadimplente</label>
          <label class="flex items-center gap-2 text-sm"><input id="editReversed" type="checkbox" /> Reverter Trabalho</label>
        </div>
        <div class="flex justify-between gap-2 pt-2">
          <button id="deleteEditModal" class="px-4 py-2 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100">
            <i class="fa-solid fa-trash mr-1"></i>Excluir
          </button>
          <div class="flex gap-2">
            <button id="cancelEditModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
            <button id="saveEditModal" class="px-4 py-2 rounded-xl bg-accent text-white">Salvar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const clientSelect = document.getElementById('clientSelect');
    const servicesList = document.getElementById('servicesList');
    const totalAmountEl = document.getElementById('totalAmount');
    const attendanceForm = document.getElementById('attendanceForm');
    const installmentsFields = document.getElementById('installmentsFields');
    const installmentsCount = document.getElementById('installmentsCount');
    const dueDay = document.getElementById('dueDay');
    const attendancesTable = document.getElementById('attendancesTable');
    const installmentsPanel = document.getElementById('installmentsPanel');
    const whatsappLink = document.getElementById('whatsappLink');
    const servicesCount = document.getElementById('servicesCount');
    const goServices = document.getElementById('goServices');
    const isDelinquent = document.getElementById('isDelinquent');
    const isReversed = document.getElementById('isReversed');
    const editModal = document.getElementById('editModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const cancelEditModal = document.getElementById('cancelEditModal');
    const saveEditModal = document.getElementById('saveEditModal');
    const editClient = document.getElementById('editClient');
    const editServices = document.getElementById('editServices');
    const editNotes = document.getElementById('editNotes');
    const editDelinquent = document.getElementById('editDelinquent');
    const editReversed = document.getElementById('editReversed');
    const deleteEditModal = document.getElementById('deleteEditModal');
    let currentEditAttendanceId = null;
    let servicesCache = [];
    let clientsCache = [];
    let currentAttendanceId = null;

    const formatBRLAmount = (value) => formatBRLOrZero(String(value || 0));

    const loadBootstrap = async () => {
      const response = await fetch('api/attendances.php?action=bootstrap', { cache: 'no-store' });
      const data = await response.json();
      clientsCache = data.clients || [];
      servicesCache = data.services || [];
      clientSelect.innerHTML = clientsCache.map((client) => `<option value="${client.id}">${client.name}</option>`).join('');
      servicesList.innerHTML = servicesCache.map((service) => `
        <label class="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2">
          <input type="checkbox" value="${service.id}" data-price="${service.price}" class="service-check" />
          <span>${service.name}</span>
          <span class="ml-auto text-slate-400 text-sm">${formatBRLAmount(service.price)}</span>
        </label>
      `).join('');
      updateWhatsappLink();
    };

    const updateWhatsappLink = () => {
      const clientId = Number(clientSelect.value || 0);
      const client = clientsCache.find((item) => item.id == clientId);
      if (client && client.whatsapp) {
        const digits = String(client.whatsapp).replace(/\D+/g, '');
        const url = digits.startsWith('81') ? `https://wa.me/${digits}` : `https://wa.me/81${digits}`;
        whatsappLink.href = url;
        whatsappLink.classList.remove('hidden');
      } else {
        whatsappLink.classList.add('hidden');
      }
    };

    const calculateTotal = () => {
      const checks = document.querySelectorAll('.service-check:checked');
      const total = Array.from(checks).reduce((sum, input) => sum + Number(input.dataset.price || 0), 0);
      totalAmountEl.textContent = formatBRLAmount(total);
      servicesCount.textContent = `${checks.length} selecionados`;
      return total;
    };

    const loadAttendances = async () => {
      attendancesTable.innerHTML = '<tr><td class="py-3" colspan="4">Carregando...</td></tr>';
      const response = await fetch(`api/attendances.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await response.json();
      const rows = (data.data || []).map((attendance) => `
        <tr class="border-t border-slate-100 ${attendance.is_delinquent == 1 ? 'bg-red-50' : ''}" data-attendance="${attendance.id}">
          <td class="py-3">
            <button class="text-accent" data-client-history="${attendance.client_id}">${attendance.client_name}</button>
          </td>
          <td class="py-3">${attendance.services || '-'}</td>
          <td class="py-3">
            ${attendance.payment_type === 'cash' ? 'À Vista' : 'Parcelado'}
            ${attendance.is_reversed == 1 ? '<span class="ml-2 text-xs text-amber-600">Revertido</span>' : ''}
          </td>
          <td class="py-3 text-right">
            <div class="flex items-center justify-end gap-3">
              <button class="text-sm text-slate-500" data-installments="${attendance.id}">Parcelas</button>
              <span class="font-semibold">${formatBRLAmount(attendance.total_amount)}</span>
            </div>
          </td>
        </tr>
      `);
      attendancesTable.innerHTML = rows.length ? rows.join('') : '<tr><td class="py-3" colspan="4">Nenhum atendimento.</td></tr>';
    };

    const loadInstallments = async (attendanceId) => {
      currentAttendanceId = attendanceId;
      installmentsPanel.innerHTML = 'Carregando parcelas...';
      const response = await fetch(`api/attendances.php?action=installments&attendance_id=${attendanceId}`, { cache: 'no-store' });
      const data = await response.json();
      const rows = (data.data || []).map((inst) => `
        <div class="border border-slate-200 rounded-xl p-3 mb-2">
          <div class="flex items-center justify-between text-sm">
            <div>Parcela ${inst.installment_number} - ${formatBRLAmount(inst.amount)}</div>
            <div class="text-slate-500">Venc: ${inst.due_date}</div>
          </div>
          <div class="mt-2 flex items-center justify-between">
            <span class="text-xs ${inst.status === 'paid' ? 'text-emerald-600' : 'text-amber-600'}">${inst.status === 'paid' ? 'Pago' : 'Pendente'}</span>
            <div class="flex items-center gap-2">
              ${inst.receipt_path ? `<a href="${inst.receipt_path}" target="_blank" class="text-xs text-accent">Ver comprovante</a>` : ''}
              <label class="text-xs text-slate-600 cursor-pointer">
                <input type="file" data-upload="${inst.id}" class="hidden upload-input" />
                Upload
              </label>
            </div>
          </div>
        </div>
      `);
      installmentsPanel.innerHTML = rows.length ? rows.join('') : 'Sem parcelas.';
    };

    attendanceForm.addEventListener('change', (event) => {
      if (event.target.matches('.service-check')) calculateTotal();
    });

    goServices.addEventListener('click', () => {
      servicesList.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    clientSelect.addEventListener('change', updateWhatsappLink);

    document.querySelectorAll('input[name="paymentType"]').forEach((input) => {
      input.addEventListener('change', () => {
        const type = document.querySelector('input[name="paymentType"]:checked').value;
        installmentsFields.classList.toggle('hidden', type !== 'installments');
      });
    });

    attendanceForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const selectedServices = Array.from(document.querySelectorAll('.service-check:checked')).map((input) => input.value);
      if (selectedServices.length === 0) { alert('Selecione pelo menos um serviço.'); return; }

      const formData = new URLSearchParams();
      formData.append('action', 'create');
      formData.append('client_id', clientSelect.value);
      selectedServices.forEach((id) => formData.append('service_ids[]', id));
      formData.append('notes', document.getElementById('notes').value);
      const paymentType = document.querySelector('input[name="paymentType"]:checked').value;
      formData.append('payment_type', paymentType);
      formData.append('installments_count', installmentsCount.value);
      formData.append('due_day', dueDay.value);
      formData.append('is_delinquent', isDelinquent.checked ? '1' : '0');
      formData.append('is_reversed', isReversed.checked ? '1' : '0');

      const response = await fetch('api/attendances.php', { method: 'POST', body: formData });
      const data = await response.json();
      if (!data.ok) { alert(data.message || 'Erro ao salvar'); return; }

      attendanceForm.reset();
      installmentsFields.classList.add('hidden');
      totalAmountEl.textContent = formatBRLAmount(0);
      isDelinquent.checked = false;
      isReversed.checked = false;
      await loadAttendances();
    });

    const loadHistory = async (clientId) => {
      installmentsPanel.innerHTML = 'Carregando histórico...';
      const response = await fetch(`api/attendances.php?action=history&client_id=${clientId}`, { cache: 'no-store' });
      const data = await response.json();
      const rows = (data.data || []).map((attendance) => `
        <div class="border border-slate-200 rounded-xl p-3 mb-2">
          <div class="flex items-center justify-between text-sm">
            <div>${attendance.services || '-'}</div>
            <div class="text-slate-500">${formatBRLAmount(attendance.total_amount)}</div>
          </div>
          <button class="mt-2 text-xs text-accent" data-installments="${attendance.id}">Ver parcelas</button>
        </div>
      `);
      installmentsPanel.innerHTML = rows.length ? rows.join('') : 'Nenhum atendimento para este cliente.';
    };

    attendancesTable.addEventListener('click', (event) => {
      const row = event.target.closest('tr[data-attendance]');
      if (row && !event.target.hasAttribute('data-installments') && !event.target.hasAttribute('data-client-history')) {
        openEditModal(row.getAttribute('data-attendance'));
      }
      const attendanceId = event.target.getAttribute('data-installments');
      const clientId = event.target.getAttribute('data-client-history');
      if (attendanceId) loadInstallments(attendanceId);
      if (clientId) loadHistory(clientId);
    });

    const toggleEditModal = (show) => toggleModal(editModal, show);

    const openEditModal = async (attendanceId) => {
      currentEditAttendanceId = attendanceId;
      const response = await fetch(`api/attendances.php?action=detail&attendance_id=${attendanceId}`, { cache: 'no-store' });
      const data = await response.json();
      if (!data.ok) { alert(data.message || 'Erro ao carregar atendimento'); return; }
      editClient.textContent = data.attendance.client_name || '-';
      editServices.textContent = (data.services || []).map((s) => s.name).join(', ') || '-';
      editNotes.value = data.attendance.notes || '';
      editDelinquent.checked = data.attendance.is_delinquent == 1;
      editReversed.checked = data.attendance.is_reversed == 1;
      toggleEditModal(true);
    };

    saveEditModal.addEventListener('click', async () => {
      if (!currentEditAttendanceId) return;
      const payload = new URLSearchParams();
      payload.append('action', 'update');
      payload.append('attendance_id', currentEditAttendanceId);
      payload.append('notes', editNotes.value);
      payload.append('is_delinquent', editDelinquent.checked ? '1' : '0');
      payload.append('is_reversed', editReversed.checked ? '1' : '0');
      const response = await fetch('api/attendances.php', { method: 'POST', body: payload });
      const data = await response.json();
      if (!data.ok) { alert(data.message || 'Erro ao salvar'); return; }
      toggleEditModal(false);
      await loadAttendances();
    });

    deleteEditModal.addEventListener('click', async () => {
      if (!currentEditAttendanceId) return;
      if (!confirm('Tem certeza que deseja excluir este atendimento? Todas as parcelas associadas também serão removidas. Esta ação não pode ser desfeita.')) return;
      const payload = new URLSearchParams();
      payload.append('action', 'delete');
      payload.append('attendance_id', currentEditAttendanceId);
      const response = await fetch('api/attendances.php', { method: 'POST', body: payload });
      const data = await response.json();
      if (!data.ok) { alert(data.message || 'Erro ao excluir'); return; }
      toggleEditModal(false);
      await loadAttendances();
    });

    [closeEditModal, cancelEditModal].forEach((btn) => btn.addEventListener('click', () => toggleEditModal(false)));

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') toggleEditModal(false);
    });

    installmentsPanel.addEventListener('click', (event) => {
      const attendanceId = event.target.getAttribute('data-installments');
      if (attendanceId) loadInstallments(attendanceId);
    });

    installmentsPanel.addEventListener('change', async (event) => {
      const input = event.target;
      if (!input.classList.contains('upload-input')) return;
      const installmentId = input.getAttribute('data-upload');
      if (!input.files.length) return;
      const formData = new FormData();
      formData.append('action', 'upload_receipt');
      formData.append('installment_id', installmentId);
      formData.append('receipt', input.files[0]);
      const response = await fetch('api/attendances.php', { method: 'POST', body: formData });
      const data = await response.json();
      if (!data.ok) { alert(data.message || 'Falha no upload'); return; }
      if (currentAttendanceId) loadInstallments(currentAttendanceId);
    });

    document.getElementById('refreshAttendances').addEventListener('click', loadAttendances);

    loadBootstrap();
    loadAttendances();

    const urlParams = new URLSearchParams(window.location.search);
    const clientIdParam = urlParams.get('client_id');
    if (clientIdParam) loadHistory(clientIdParam);
  </script>
</body>
</html>