<?php
$pageTitle = 'CRM Terreiro - Dashboard';
$activePage = 'dashboard';
$extraHead = <<<'HTML'
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>
HTML;
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Dashboard</h1>
          <p class="text-slate-500">Visão geral do terreiro</p>
        </div>
        <button id="openQuickAction" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold shadow-lg hover:bg-red-800">Nova ação</button>
      </header>

      <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-5 mb-8">
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 text-sm">Clientes</p>
              <p class="text-3xl font-semibold mt-2" id="countClients">0</p>
            </div>
            <span class="h-12 w-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center">
              <i class="fa-solid fa-users"></i>
            </span>
          </div>
          <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-red-500/70 to-red-400/60"></div>
        </div>
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 text-sm">Trabalhos em andamento</p>
              <p class="text-3xl font-semibold mt-2" id="countJobs">0</p>
            </div>
            <span class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
              <i class="fa-solid fa-clipboard-list"></i>
            </span>
          </div>
          <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-blue-500/70 to-cyan-400/60"></div>
        </div>
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 text-sm">Serviços</p>
              <p class="text-3xl font-semibold mt-2" id="countServices">0</p>
            </div>
            <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center">
              <i class="fa-solid fa-briefcase"></i>
            </span>
          </div>
          <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-amber-400/80 to-orange-400/60"></div>
        </div>
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 text-sm">Previsão (Mês)</p>
              <p class="text-3xl font-semibold mt-2" id="countReceivables"><?= $_crmCurrSymbol ?>0</p>
            </div>
            <span class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
              <i class="fa-solid fa-coins"></i>
            </span>
          </div>
          <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-emerald-400/80 to-teal-400/60"></div>
        </div>
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 text-sm">Contas a pagar (Mês)</p>
              <p class="text-3xl font-semibold mt-2" id="countPayables"><?= $_crmCurrSymbol ?>0</p>
            </div>
            <span class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center">
              <i class="fa-solid fa-file-invoice-dollar"></i>
            </span>
          </div>
          <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-rose-400/80 to-pink-400/60"></div>
        </div>
        <div class="bg-white/80 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 text-sm">Dinheiro em caixa</p>
              <p class="text-3xl font-semibold mt-2" id="countCash"><?= $_crmCurrSymbol ?>0</p>
            </div>
            <span class="h-12 w-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center">
              <i class="fa-solid fa-cash-register"></i>
            </span>
          </div>
          <div class="mt-6 h-1 rounded-full bg-gradient-to-r from-sky-400/80 to-blue-400/60"></div>
        </div>
      </section>

      <section id="mediumSummarySection" class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
          <div>
            <h2 class="text-lg font-semibold">Resumo do Médium</h2>
            <p class="text-sm text-slate-500">Split dos trabalhos com retenção Gensen Choshu de 10,21%</p>
          </div>
          <a href="ryoushuusho.php" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i class="fa-solid fa-receipt mr-1"></i>Gerar recibo
          </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total Realizado</p>
            <p id="mediumTotalRealizado" class="mt-2 text-2xl font-black text-emerald-800"><?= $_crmCurrSymbol ?>0</p>
          </div>
          <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Imposto Retido Acumulado</p>
            <p id="mediumImpostoRetido" class="mt-2 text-2xl font-black text-amber-800"><?= $_crmCurrSymbol ?>0</p>
          </div>
          <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Valor a Receber</p>
            <p id="mediumValorReceber" class="mt-2 text-2xl font-black text-sky-800"><?= $_crmCurrSymbol ?>0</p>
          </div>
          <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Mensalidades/Trabalhos Pendentes</p>
            <p id="mediumPendenciasTotal" class="mt-2 text-2xl font-black text-rose-800">0</p>
            <p id="mediumPendenciasDetalhe" class="mt-1 text-xs text-rose-700">0 mensalidades + 0 trabalhos</p>
          </div>
        </div>
      </section>

      <section class="bg-white border border-slate-200 rounded-2xl p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Trabalhos Recentes</h2>
          <span class="text-slate-400 text-sm">Atualizado em tempo real</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Cliente</th>
                <th class="text-left pb-3">Serviços</th>
                <th class="text-left pb-3">Observações</th>
                <th class="text-left pb-3">Pagamento</th>
                <th class="text-right pb-3">Total</th>
              </tr>
            </thead>
            <tbody id="jobsTable">
              <tr><td class="py-3" colspan="5">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="bg-white border border-slate-200 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Mensalidades do Mês</h2>
          <a href="mensalidades.php" class="text-sm text-red-600">Ver todas</a>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-slate-500">
              <tr>
                <th class="text-left pb-3">Filho</th>
                <th class="text-left pb-3">Grau</th>
                <th class="text-left pb-3">Venc.</th>
                <th class="text-right pb-3">Status</th>
              </tr>
            </thead>
            <tbody id="mensalidadesTable">
              <tr><td class="py-3" colspan="4">Carregando...</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="bg-white border border-slate-200 rounded-2xl p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Calendário</h2>
          <span class="text-slate-400 text-sm">Pagamentos, trabalhos e fases da lua</span>
        </div>
        <div id="calendar" class="rounded-xl overflow-hidden"></div>
      </section>
    </main>
  </div>

  <!-- MODAL AÇÃO RÁPIDA -->
  <div id="quickActionModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
    <div class="bg-white rounded-3xl w-full max-w-xl p-6 border border-slate-200">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Nova ação</h2>
        <button id="closeQuickAction" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="clientes.php" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-red-200 hover:bg-red-50">
          <span class="h-10 w-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center"><i class="fa-solid fa-user-plus"></i></span>
          <div><div class="font-semibold">Cadastrar Cliente</div><p class="text-sm text-slate-500">Adicionar um novo cliente</p></div>
        </a>
        <a href="trabalhos.php" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-amber-200 hover:bg-amber-50">
          <span class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center"><i class="fa-solid fa-briefcase"></i></span>
          <div><div class="font-semibold">Novo Trabalho</div><p class="text-sm text-slate-500">Agendar um trabalho</p></div>
        </a>
        <a href="usuarios.php" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-blue-200 hover:bg-blue-50">
          <span class="h-10 w-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fa-solid fa-user-shield"></i></span>
          <div><div class="font-semibold">Adicionar Usuário</div><p class="text-sm text-slate-500">Gerencie acessos</p></div>
        </a>
        <a href="atendimentos.php" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-emerald-200 hover:bg-emerald-50">
          <span class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-file-circle-plus"></i></span>
          <div><div class="font-semibold">Registrar Atendimento</div><p class="text-sm text-slate-500">Fluxo simplificado</p></div>
        </a>
      </div>
      <div class="mt-6 flex justify-end">
        <button id="closeQuickActionFooter" class="px-4 py-2 rounded-xl border border-slate-200">Fechar</button>
      </div>
    </div>
  </div>

  <!-- MODAL EDITAR ATENDIMENTO -->
  <div id="editModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 px-4 z-[60]">
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
          <label class="text-sm font-medium text-slate-700">Observações</label>
          <textarea id="editNotes" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" rows="3"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="flex items-center gap-2 text-sm"><input id="editDelinquent" type="checkbox" /> Inadimplente</label>
          <label class="flex items-center gap-2 text-sm"><input id="editReversed" type="checkbox" /> Trabalho Revertido</label>
        </div>
        <div class="flex justify-between gap-2 pt-2">
          <button id="deleteEditModal" class="px-4 py-2 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100">
            <i class="fa-solid fa-trash mr-1"></i>Excluir
          </button>
          <div class="flex gap-2">
            <button id="cancelEditModal" class="px-4 py-2 rounded-xl border border-slate-200">Cancelar</button>
            <button id="saveEditModal" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold">Salvar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const calendarEl = document.getElementById('calendar');
    const openQuickAction = document.getElementById('openQuickAction');
    const quickActionModal = document.getElementById('quickActionModal');
    const closeQuickAction = document.getElementById('closeQuickAction');
    const closeQuickActionFooter = document.getElementById('closeQuickActionFooter');

    const toggleQuickAction = (show) => {
      quickActionModal.classList.toggle('hidden', !show);
      quickActionModal.classList.toggle('flex', show);
    };

    openQuickAction.addEventListener('click', () => toggleQuickAction(true));
    [closeQuickAction, closeQuickActionFooter].forEach((btn) =>
      btn.addEventListener('click', () => toggleQuickAction(false))
    );

    const jobsTable = document.getElementById('jobsTable');
    const countClients = document.getElementById('countClients');
    const countJobs = document.getElementById('countJobs');
    const countServices = document.getElementById('countServices');
    const countReceivables = document.getElementById('countReceivables');
    const countPayables = document.getElementById('countPayables');
    const countCash = document.getElementById('countCash');
    const mediumTotalRealizado = document.getElementById('mediumTotalRealizado');
    const mediumImpostoRetido = document.getElementById('mediumImpostoRetido');
    const mediumValorReceber = document.getElementById('mediumValorReceber');
    const mediumPendenciasTotal = document.getElementById('mediumPendenciasTotal');
    const mediumPendenciasDetalhe = document.getElementById('mediumPendenciasDetalhe');
    const mensalidadesTable = document.getElementById('mensalidadesTable');
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

    const formatBRLAmt = (v) => formatBRLOrZero(String(v || 0));

    const loadDashboard = async () => {
      const response = await fetch(`api/dashboard.php?t=${Date.now()}`, { cache: 'no-store' });
      const data = await response.json();

      countClients.textContent = data.counts.clients ?? 0;
      countJobs.textContent = data.counts.attendances ?? 0;
      countServices.textContent = data.counts.services ?? 0;
      countReceivables.textContent = formatBRLAmt(data.counts.receivable_month ?? 0);
      countPayables.textContent = formatBRLAmt(data.counts.payable_month ?? 0);
      countCash.textContent = formatBRLAmt(data.counts.cash_month ?? 0);

      const mediumSummary = data.medium_summary || {};
      mediumTotalRealizado.textContent = formatBRLAmt(mediumSummary.total_realizado ?? 0);
      mediumImpostoRetido.textContent = formatBRLAmt(mediumSummary.imposto_retido_acumulado ?? 0);
      mediumValorReceber.textContent = formatBRLAmt(mediumSummary.valor_a_receber ?? 0);
      mediumPendenciasTotal.textContent = String(mediumSummary.pendencias_total ?? 0);
      mediumPendenciasDetalhe.textContent = `${mediumSummary.mensalidades_pendentes ?? 0} mensalidades + ${mediumSummary.trabalhos_pendentes ?? 0} trabalhos`;

      const formatWhatsapp = (phone) => {
        const digits = String(phone || '').replace(/\D+/g, '');
        if (!digits) return '';
        return digits.startsWith('81') ? `https://wa.me/${digits}` : `https://wa.me/81${digits}`;
      };

      const rows = (data.latest_attendances || []).map((attendance) => {
        const notePreview = (attendance.notes || '').slice(0, 40);
        return `
          <tr class="border-t border-slate-100 ${attendance.is_delinquent == 1 ? 'bg-[#ffcccc]' : ''} ${attendance.is_reversed == 1 ? 'opacity-70' : ''}" data-attendance="${attendance.id}">
            <td class="py-3">
              <div class="font-medium">${attendance.client_name || '-'}</div>
              ${attendance.client_phone ? `<a href="${formatWhatsapp(attendance.client_phone)}" class="text-xs text-red-600" target="_blank">${attendance.client_phone}</a>` : ''}
            </td>
            <td class="py-3">${attendance.services || '-'}</td>
            <td class="py-3 text-slate-500">${notePreview}${attendance.notes && attendance.notes.length > 40 ? '...' : ''}</td>
            <td class="py-3">
              ${attendance.payment_type === 'cash' ? 'À Vista' : 'Parcelado'}
              ${attendance.is_reversed == 1 ? '<span class="ml-2 text-xs text-amber-600">Revertido</span>' : ''}
            </td>
            <td class="py-3 text-right">${formatBRLAmt(attendance.total_amount || 0)}</td>
          </tr>`;
      });

      jobsTable.innerHTML = rows.length ? rows.join('') : '<tr><td class="py-3" colspan="5">Nenhum atendimento encontrado.</td></tr>';
      renderCalendar(data.calendar_events || []);
    };

    const specialMoons = {
      '2026-03-03': 'Lua de Sangue',
      '2026-08-28': 'Lua de Sangue',
    };

    const moonPhase = (date) => {
      const knownNewMoon = new Date(Date.UTC(2000, 0, 6, 18, 14));
      const synodicMonth = 29.53058867;
      const days = (date - knownNewMoon) / 86400000;
      const phase = (days % synodicMonth) / synodicMonth;
      return phase < 0 ? phase + 1 : phase;
    };

    const getMoonEvents = (year, monthIndex) => {
      const events = [];
      const targetPhases = [
        { value: 0,    name: 'Lua Nova',         color: '#64748b' },
        { value: 0.25, name: 'Quarto Crescente', color: '#0ea5e9' },
        { value: 0.5,  name: 'Lua Cheia',        color: '#f59e0b' },
        { value: 0.75, name: 'Quarto Minguante', color: '#8b5cf6' },
      ];
      const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
      targetPhases.forEach((target) => {
        let bestDay = 1, bestDiff = 1;
        for (let day = 1; day <= daysInMonth; day += 1) {
          const d = new Date(Date.UTC(year, monthIndex, day, 12, 0));
          const phase = moonPhase(d);
          const diff = Math.min(Math.abs(phase - target.value), 1 - Math.abs(phase - target.value));
          if (diff < bestDiff) { bestDiff = diff; bestDay = day; }
        }
        const dateStr = new Date(Date.UTC(year, monthIndex, bestDay)).toISOString().split('T')[0];
        const special = specialMoons[dateStr];
        events.push({
          title: special || target.name,
          date: dateStr,
          color: special ? '#ef4444' : target.color,
          textColor: '#0f172a',
          extendedProps: { type: 'lua' },
        });
      });
      return events;
    };

    let calendarInstance = null;
    const mergeCalendarEvents = (events, viewStart) => {
      const year = viewStart.getUTCFullYear();
      const monthIndex = viewStart.getUTCMonth();
      const moonEvents = getMoonEvents(year, monthIndex);
      return [...events, ...moonEvents].map((evt) => {
        if (evt.type === 'agendamento_atendimento') {
          return {
            title: evt.title,
            start: evt.start || (evt.date ? `${evt.date}T09:00:00` : undefined),
            color: '#ec4899',
            textColor: '#831843',
          };
        }
        if (evt.type === 'trabalho') return { title: evt.title, date: evt.date, color: '#2563eb' };
        if (evt.type === 'mensalidade') return { title: evt.title, date: evt.date, color: '#10b981' };
        if (evt.type === 'conta_pagar') return { title: evt.title, date: evt.date, color: evt.status === 'Pago' ? '#6b7280' : '#dc2626' };
        return evt;
      });
    };

    const renderCalendar = (events) => {
      if (!calendarEl) return;
      const today = new Date();
      const formattedEvents = mergeCalendarEvents(events, new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 1)));
      if (calendarInstance) {
        calendarInstance.removeAllEvents();
        calendarInstance.addEventSource(formattedEvents);
        return;
      }
      calendarInstance = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        locale: 'pt-br',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: formattedEvents,
        datesSet: (info) => {
          const currentStart = info.view.currentStart;
          // Use local date to avoid UTC timezone shift (e.g. JST midnight → previous day in UTC)
          const y = currentStart.getFullYear();
          const m = String(currentStart.getMonth() + 1).padStart(2, '0');
          const d = String(currentStart.getDate()).padStart(2, '0');
          const monthParam = `${y}-${m}-${d}`;
          fetch(`api/dashboard.php?action=calendar&month=${monthParam}`, { cache: 'no-store' })
            .then((res) => res.json())
            .then((data) => {
              const nextEvents = mergeCalendarEvents(data.calendar_events || [], currentStart);
              calendarInstance.removeAllEvents();
              calendarInstance.addEventSource(nextEvents);
            });
        },
      });
      calendarInstance.render();
    };

    const loadMensalidades = async () => {
      const response = await fetch(`api/mensalidades.php?action=list&t=${Date.now()}`, { cache: 'no-store' });
      const data = await response.json();
      const rows = (data.data || []).filter(i => i.type === 'mensal').slice(0, 6).map((item) => {
        const isento = item.isento_mensalidade == 1;
        const statusLabel = isento ? 'Isento' : item.paid ? 'Pago' : item.overdue ? 'Atraso' : 'Em dia';
        const statusClass = isento ? 'text-blue-600' : item.paid ? 'text-emerald-600' : item.overdue ? 'text-red-600' : 'text-amber-600';
        const rowBg = isento ? 'bg-blue-50' : item.overdue ? 'bg-[#ffcccc]' : item.paid ? 'bg-emerald-50' : '';
        return `
          <tr class="border-t border-slate-100 ${rowBg}">
            <td class="py-3">${item.name}</td>
            <td class="py-3">${item.grade}</td>
            <td class="py-3">Dia ${item.due_day}</td>
            <td class="py-3 text-right"><span class="${statusClass} font-semibold">${statusLabel}</span></td>
          </tr>`;
      });
      mensalidadesTable.innerHTML = rows.length ? rows.join('') : '<tr><td class="py-3" colspan="4">Nenhum registro.</td></tr>';
    };

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
      loadDashboard();
    });

    deleteEditModal.addEventListener('click', async () => {
      if (!currentEditAttendanceId) return;
      if (!confirm('Tem certeza que deseja excluir este atendimento? Esta ação não pode ser desfeita.')) return;
      const payload = new URLSearchParams();
      payload.append('action', 'delete');
      payload.append('attendance_id', currentEditAttendanceId);
      const response = await fetch('api/attendances.php', { method: 'POST', body: payload });
      const data = await response.json();
      if (!data.ok) { alert(data.message || 'Erro ao excluir'); return; }
      toggleEditModal(false);
      loadDashboard();
    });

    [closeEditModal, cancelEditModal].forEach((btn) => btn.addEventListener('click', () => toggleEditModal(false)));

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        toggleQuickAction(false);
        toggleEditModal(false);
      }
    });

    jobsTable.addEventListener('click', (event) => {
      const row = event.target.closest('tr[data-attendance]');
      if (row) openEditModal(row.getAttribute('data-attendance'));
    });

    loadDashboard();
    loadMensalidades();
  </script>
</body>
</html>