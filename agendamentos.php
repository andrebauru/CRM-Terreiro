<?php
$pageTitle = 'CRM Terreiro - Agendamento de Atendimento';
$activePage = 'agendamentos';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <header class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold">Agendamento de Atendimento</h1>
          <p class="text-slate-500">Agenda com horário e geração automática de receita a receber</p>
        </div>
      </header>

      <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1 bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
          <h2 class="text-lg font-semibold mb-4">Novo Agendamento</h2>
          <form id="agendamentoForm" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Nome *</label>
              <input id="agNome" type="text" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Nome do cliente" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-sm font-medium text-slate-700">Data *</label>
                <input id="agData" type="date" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">Horário *</label>
                <input id="agHora" type="time" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2" />
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Tipo de Atendimento *</label>
              <select id="agTipo" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2">
                <option value="servico">Serviços</option>
                <option value="trabalho">Trabalhos</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Serviço/Trabalho *</label>
              <select id="agReferencia" required class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2"></select>
            </div>
            <div class="rounded-2xl bg-pink-50 border border-pink-200 p-3">
              <p class="text-xs text-pink-700 font-semibold">Receita a receber gerada automaticamente</p>
              <p id="agValorPrevisto" class="text-lg font-bold text-pink-700 mt-1"><?= $_crmCurrSymbol ?>0</p>
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700">Observações</label>
              <textarea id="agObs" rows="2" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2"></textarea>
            </div>
            <button type="submit" class="w-full rounded-2xl bg-pink-600 py-3 text-white font-semibold shadow-lg hover:bg-pink-700">Salvar Agendamento</button>
          </form>
        </div>

        <div class="xl:col-span-2 space-y-6">
          <div class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold">Receitas a Receber (Agendadas)</h2>
              <span id="agTotalReceber" class="text-lg font-black text-pink-700"><?= $_crmCurrSymbol ?>0</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="text-slate-500">
                  <tr>
                    <th class="text-left pb-3">Nome</th>
                    <th class="text-left pb-3">Data/Hora</th>
                    <th class="text-left pb-3">Tipo</th>
                    <th class="text-left pb-3">Referência</th>
                    <th class="text-left pb-3">Status</th>
                    <th class="text-right pb-3">Valor Previsto</th>
                    <th class="text-right pb-3">Ação</th>
                  </tr>
                </thead>
                <tbody id="agTabela">
                  <tr><td colspan="7" class="py-3">Carregando...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="bg-white/90 backdrop-blur border border-slate-200 rounded-3xl p-6 shadow-xl shadow-slate-200/40">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold">Calendário de Atendimentos</h2>
              <span class="text-xs text-pink-600 font-semibold">Agendamentos em rosa</span>
            </div>
            <div id="agCalendar"></div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const agForm = document.getElementById('agendamentoForm');
    const agTipo = document.getElementById('agTipo');
    const agReferencia = document.getElementById('agReferencia');
    const agValorPrevisto = document.getElementById('agValorPrevisto');
    const agTabela = document.getElementById('agTabela');
    const agTotalReceber = document.getElementById('agTotalReceber');
    const agCalendarEl = document.getElementById('agCalendar');

    let boot = { services: [], jobs: [] };
    let agCalendar = null;

    const fmtMoney = (v) => formatBRLOrZero(String(v || 0));

    function optionsFromType(tipo) {
      const items = tipo === 'trabalho' ? boot.jobs : boot.services;
      return items.map(i => `<option value="${i.id}" data-price="${i.price}">${i.name} — ${fmtMoney(i.price)}</option>`).join('');
    }

    function refreshValorPrevisto() {
      const opt = agReferencia.options[agReferencia.selectedIndex];
      const price = Number(opt?.dataset?.price || 0);
      agValorPrevisto.textContent = fmtMoney(price);
    }

    function refreshReferenciaSelect() {
      agReferencia.innerHTML = optionsFromType(agTipo.value);
      refreshValorPrevisto();
    }

    async function loadBootstrap() {
      const r = await fetch('api/agendamentos.php?action=bootstrap', { cache: 'no-store' });
      const d = await r.json();
      boot = { services: d.services || [], jobs: d.jobs || [] };
      refreshReferenciaSelect();
    }

    async function loadTabela() {
      const r = await fetch('api/agendamentos.php?action=list', { cache: 'no-store' });
      const d = await r.json();
      const rows = d.data || [];
      const total = rows.reduce((sum, row) => sum + (row.status === 'agendado' ? Number(row.valor_previsto || 0) : 0), 0);
      agTotalReceber.textContent = fmtMoney(total);

      agTabela.innerHTML = rows.length ? rows.map(row => `
        <tr class="border-t border-slate-100 ${row.status === 'agendado' ? 'bg-pink-50/40' : ''}">
          <td class="py-3">${row.nome}</td>
          <td class="py-3 text-xs text-slate-600">${row.data_agendamento} ${String(row.hora_agendamento || '').slice(0,5)}</td>
          <td class="py-3 text-xs">${row.tipo_atendimento === 'trabalho' ? 'Trabalho' : 'Serviço'}</td>
          <td class="py-3 text-xs text-slate-600">${row.referencia_nome || '-'}</td>
          <td class="py-3 text-xs">
            ${row.status === 'realizado'
              ? '<span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-semibold">Realizado</span>'
              : (row.status === 'cancelado'
                ? '<span class="px-2 py-1 rounded-full bg-slate-200 text-slate-600 font-semibold">Cancelado</span>'
                : '<span class="px-2 py-1 rounded-full bg-pink-100 text-pink-700 font-semibold">Agendado</span>')}
          </td>
          <td class="py-3 text-right font-semibold">${fmtMoney(row.valor_previsto)}</td>
          <td class="py-3 text-right">
            ${row.status === 'agendado'
              ? `<button class="text-xs text-emerald-700 font-bold mr-3" onclick="convertAgendamento(${row.id})">Converter</button>`
              : ''}
            ${row.converted_attendance_id
              ? `<a class="text-xs text-blue-600 font-bold mr-3" href="atendimentos.php">Atendimento #${row.converted_attendance_id}</a>`
              : ''}
            <button class="text-xs text-red-600 font-bold" onclick="deleteAgendamento(${row.id})">Excluir</button>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="7" class="py-3 text-center text-slate-400">Nenhum agendamento.</td></tr>';
    }

    async function loadCalendar(monthParam = null) {
      const query = monthParam ? `&month=${encodeURIComponent(monthParam)}` : '';
      const r = await fetch(`api/agendamentos.php?action=calendar${query}`, { cache: 'no-store' });
      const d = await r.json();
      const events = (d.events || []).map(e => ({ ...e, color: '#ec4899', textColor: '#831843' }));

      if (!agCalendar) {
        agCalendar = new FullCalendar.Calendar(agCalendarEl, {
          initialView: 'dayGridMonth',
          locale: 'pt-br',
          height: 'auto',
          headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
          events,
          datesSet: (info) => {
            const y = info.view.currentStart.getFullYear();
            const m = String(info.view.currentStart.getMonth() + 1).padStart(2, '0');
            loadCalendar(`${y}-${m}-01`);
          }
        });
        agCalendar.render();
      } else {
        agCalendar.removeAllEvents();
        agCalendar.addEventSource(events);
      }
    }

    async function deleteAgendamento(id) {
      if (!confirm('Excluir este agendamento?')) return;
      const body = new URLSearchParams({ action: 'delete', id });
      const r = await fetch('api/agendamentos.php', { method: 'POST', body });
      const d = await r.json();
      if (!d.ok) return alert(d.message || 'Erro ao excluir');
      await Promise.all([loadTabela(), loadCalendar()]);
    }

    async function convertAgendamento(id) {
      if (!confirm('Converter este agendamento em atendimento real?')) return;
      const body = new URLSearchParams({ action: 'convert_to_attendance', id });
      const r = await fetch('api/agendamentos.php', { method: 'POST', body });
      const d = await r.json();
      if (!d.ok) return alert(d.message || 'Erro ao converter');
      alert(`Convertido com sucesso. Atendimento #${d.attendance_id}`);
      await Promise.all([loadTabela(), loadCalendar()]);
    }
    window.deleteAgendamento = deleteAgendamento;
    window.convertAgendamento = convertAgendamento;

    agTipo.addEventListener('change', refreshReferenciaSelect);
    agReferencia.addEventListener('change', refreshValorPrevisto);

    agForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = new URLSearchParams({
        action: 'create',
        nome: document.getElementById('agNome').value.trim(),
        data_agendamento: document.getElementById('agData').value,
        hora_agendamento: document.getElementById('agHora').value,
        tipo_atendimento: agTipo.value,
        referencia_id: agReferencia.value,
        observacoes: document.getElementById('agObs').value,
      });

      const r = await fetch('api/agendamentos.php', { method: 'POST', body });
      const d = await r.json();
      if (!d.ok) {
        alert(d.message || 'Erro ao salvar agendamento');
        return;
      }

      agForm.reset();
      document.getElementById('agData').value = new Date().toISOString().split('T')[0];
      document.getElementById('agHora').value = '09:00';
      refreshReferenciaSelect();
      await Promise.all([loadTabela(), loadCalendar()]);
    });

    document.addEventListener('DOMContentLoaded', async () => {
      document.getElementById('agData').value = new Date().toISOString().split('T')[0];
      document.getElementById('agHora').value = '09:00';
      await loadBootstrap();
      await Promise.all([loadTabela(), loadCalendar()]);
    });
  </script>
</body>
</html>
