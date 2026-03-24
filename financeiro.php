<?php
$pageTitle = 'CRM Terreiro - Financeiro';
$activePage = 'financeiro';
require_once __DIR__ . '/app/views/partials/tw-head.php';
?>
<body class="bg-[#f8fafc] font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Financeiro</h1>
          <p class="text-slate-500">Controle de caixa, contas e entradas</p>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-slate-500 font-semibold">Mês:</label>
          <input id="mesFilter" type="month" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm" />
        </div>
      </div>

      <div id="dashCards" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Entradas (mês)</p>
          <p id="cardEntradas" class="text-2xl font-black text-green-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Saídas (mês)</p>
          <p id="cardSaidas" class="text-2xl font-black text-red-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Saldo</p>
          <p id="cardSaldo" class="text-2xl font-black text-slate-700 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Crédito Casa</p>
          <p id="cardCredito" class="text-2xl font-black text-amber-500 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Mensalidades Pagas</p>
          <p id="cardMensPagas" class="text-xl font-bold text-green-600 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Mensalidades Pendentes</p>
          <p id="cardMensPend" class="text-xl font-bold text-amber-500 mt-1"><?= $_crmCurrSymbol ?>0</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Contas Pendentes</p>
          <p id="cardContasPend" class="text-xl font-bold text-red-600 mt-1">0 — <?= $_crmCurrSymbol ?>0</p>
        </div>
      </div>

      <div class="flex flex-wrap gap-1 mb-4 border-b border-slate-200">
        <button onclick="showTab('caixa')"    id="tab-caixa"    class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Caixa</button>
        <button onclick="showTab('contas')"   id="tab-contas"   class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Contas a Pagar</button>
        <button onclick="showTab('entradas')" id="tab-entradas" class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Entradas</button>
        <button onclick="showTab('credito')"  id="tab-credito"  class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Crédito Casa</button>
        <button onclick="showTab('split')"    id="tab-split"    class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Split / Recibos</button>
        <?php if ($_SESSION['user_role'] === 'admin'): ?><button onclick="showTab('admin')" id="tab-admin" class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Admin — Pagamentos</button><?php endif; ?>
      </div>
        <button onclick="showTab('split')"    id="tab-split"    class="tab-btn px-4 py-2 font-semibold text-sm rounded-t-lg">Split / Recibos</button>
      </div>

      <section id="pane-caixa" class="tab-pane hidden">
        <div id="caixaSummary" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4"></div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-left">Origem</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-right">Valor</th>
              </tr>
            </thead>
            <tbody id="caixaBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-contas" class="tab-pane hidden">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
          <button onclick="carryOverContas()" class="px-3 py-2 rounded-lg bg-amber-100 text-amber-700 font-bold hover:bg-amber-200 text-sm" title="Mover contas vencidas para o próximo mês">
            <i class="fa-solid fa-arrow-right-arrow-left mr-1"></i> Carry-over Vencidas
          </button>
          <button onclick="openContaModal()" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800 text-sm">
            <i class="fa-solid fa-plus mr-1"></i> Nova Conta
          </button>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
          <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-3 py-3 text-left">Descrição</th>
                <th class="px-3 py-3 text-left">Fornecedor</th>
                <th class="px-3 py-3 text-left">Categoria</th>
                <th class="px-3 py-3 text-left">Vencimento</th>
                <th class="px-3 py-3 text-left">Parcela</th>
                <th class="px-3 py-3 text-left">Status</th>
                <th class="px-3 py-3 text-right">Valor</th>
                <th class="px-3 py-3 text-right">Pago</th>
                <th class="px-3 py-3"></th>
              </tr>
            </thead>
            <tbody id="contasBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-entradas" class="tab-pane hidden">
        <div class="flex justify-end mb-3">
          <button onclick="openEntradaModal()" class="px-4 py-2 rounded-lg bg-green-600 text-white font-bold hover:bg-green-700 text-sm">
            <i class="fa-solid fa-plus mr-1"></i> Nova Entrada
          </button>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
          <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-left">Origem</th>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-right">Valor</th>
                <th class="px-4 py-3 text-right">Crédito Casa (10%)</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody id="entradasBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-credito" class="tab-pane hidden">
        <p class="text-sm text-slate-500 mb-3">10% de cada entrada é automaticamente reservado como Crédito Casa.</p>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-left">Descrição</th>
                <th class="px-4 py-3 text-right">Valor Original</th>
                <th class="px-4 py-3 text-right">% Crédito</th>
                <th class="px-4 py-3 text-right">Valor Crédito</th>
              </tr>
            </thead>
            <tbody id="creditoBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-admin" class="tab-pane hidden">
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm mb-6">
          <h2 class="text-lg font-bold mb-4">Resumo de Pagamentos aos Médiuns</h2>
          <div id="adminSummaryCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <div class="rounded-lg bg-slate-50 p-3 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold">Total Realizado</p>
              <p id="adminTotalRealizado" class="text-lg font-bold text-slate-800 mt-1">¥0</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold">Imposto Retido</p>
              <p id="adminTotalImposto" class="text-lg font-bold text-rose-600 mt-1">¥0</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold">Valor Líquido Total</p>
              <p id="adminTotalLiquido" class="text-lg font-bold text-slate-800 mt-1">¥0</p>
            </div>
            <div class="rounded-lg bg-green-50 p-3 border border-green-200">
              <p class="text-xs text-green-700 font-semibold">Já Pagos</p>
              <p id="adminTotalPago" class="text-lg font-bold text-green-700 mt-1">¥0</p>
            </div>
            <div class="rounded-lg bg-amber-50 p-3 border border-amber-200">
              <p class="text-xs text-amber-700 font-semibold">A Pagar</p>
              <p id="adminTotalPendente" class="text-lg font-bold text-amber-700 mt-1">¥0</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
          <table class="w-full text-sm min-w-[1200px]">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Médium</th>
                <th class="px-4 py-3 text-center">Transações</th>
                <th class="px-4 py-3 text-right">Total Realizado</th>
                <th class="px-4 py-3 text-right">Imposto (Gensen)</th>
                <th class="px-4 py-3 text-right">Valor Líquido</th>
                <th class="px-4 py-3 text-right">Já Pagos</th>
                <th class="px-4 py-3 text-right">A Pagar</th>
                <th class="px-4 py-3 text-left">Última Transação</th>
              </tr>
            </thead>
            <tbody id="adminPayablesBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>

      <section id="pane-split" class="tab-pane hidden space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4 mb-4">
              <div>
                <h2 class="text-lg font-bold">Configuração do Médium</h2>
                <p class="text-sm text-slate-500">Defina os percentuais usados no split de trabalhos espirituais.</p>
              </div>
              <div id="splitPercentTotal" class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">Total: 100%</div>
            </div>
            <form id="formMediumConfig" class="space-y-4">
              <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Médium</label>
                <select id="splitMediumUser" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Espaço %</label><input id="pctEspaco" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Treinamento %</label><input id="pctTreinamento" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Material %</label><input id="pctMaterial" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Tata %</label><input id="pctTata" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Executor %</label><input id="pctExecutor" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
              </div>
              <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">Salvar Percentuais</button>
              </div>
            </form>
          </div>

          <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4 mb-4">
              <div>
                <h2 class="text-lg font-bold">Preview do Split</h2>
                <p class="text-sm text-slate-500">Imposto Gensen de 10,21% apenas sobre Tata e Executor.</p>
              </div>
              <div class="text-xs px-3 py-1 rounded-full bg-amber-50 text-amber-700 font-bold">10.21% Gensen</div>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-semibold text-slate-600 mb-1">Valor total do trabalho</label>
              <input id="splitPreviewValor" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?= $_crmCurrSymbol ?>0" />
            </div>
            <div id="splitPreviewGrid" class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm"></div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h2 class="text-lg font-bold">Cadastrar Trabalho com Split</h2>
              <p class="text-sm text-slate-500">Registre cliente, valor, Tata e datas. O recibo é criado automaticamente com a data de pagamento, sem marcar o trabalho como pago.</p>
            </div>
          </div>
          <form id="formSplitTransaction" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Cliente</label><input id="splitClienteNome" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Telefone do cliente</label><input id="splitClienteTelefone" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Tata</label><select id="splitTataUser" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select></div>
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Status</label><select id="splitStatusPagamento" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"><option value="pendente">Pendente</option><option value="processando">Processando</option><option value="pago">Pago</option><option value="cancelado">Cancelado</option></select></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Descrição</label><input id="splitDescricaoServico" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" value="宗教儀式提供料として" /></div>
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Valor total</label><input id="splitValorTotal" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?= $_crmCurrSymbol ?>0" /></div>
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Data de realização</label><input id="splitDataRealizacao" type="date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="block text-sm font-semibold text-slate-600 mb-1">Data de pagamento do recibo</label><input id="splitDataPagamento" type="date" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" /></div>
              <div class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-800 flex items-center">O status financeiro continua o que você escolher acima; gerar recibo não muda para <strong class="ml-1">pago</strong>.</div>
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" id="btnSplitPreviewSync" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Atualizar Preview</button>
              <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700">Salvar Trabalho</button>
            </div>
          </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto">
          <table class="w-full text-sm min-w-[1100px]">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 text-left">Data</th>
                <th class="px-4 py-3 text-left">Cliente</th>
                <th class="px-4 py-3 text-left">Médium / Tata</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3 text-right">Gensen</th>
                <th class="px-4 py-3 text-right">Líquido Médium</th>
                <th class="px-4 py-3 text-right">Recibo</th>
              </tr>
            </thead>
            <tbody id="splitTransactionsBody" class="divide-y divide-slate-100"></tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- MODAL CONTA A PAGAR -->
  <div id="modalConta" class="fixed inset-0 bg-black/60 hidden z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 id="modalContaTitulo" class="text-lg font-bold">Nova Conta</h2>
        <button onclick="closeContaModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="formConta" class="p-6 space-y-4" onsubmit="submitConta(event)">
        <input type="hidden" id="contaId" />
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Descrição *</label>
          <input id="contaDescricao" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Aluguel, Energia..." />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Valor (<?= $_crmCurrSymbol ?>) *</label>
            <input id="contaValor" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?= $_crmCurrSymbol ?>0" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Categoria</label>
            <div class="flex gap-1">
              <select id="contaCategoria" class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Selecione...</option>
              </select>
              <button type="button" onclick="openCategoriaModal()" class="px-2 py-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 text-sm" title="Gerenciar categorias">
                <i class="fa-solid fa-gear"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Fornecedor</label>
            <input id="contaFornecedor" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Nome do fornecedor" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Vencimento *</label>
            <input id="contaVencimento" type="date" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Recorrência</label>
            <select id="contaRecorrencia" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
              <option value="nenhuma">Nenhuma</option>
              <option value="mensal">Mensal</option>
              <option value="bimestral">Bimestral</option>
              <option value="trimestral">Trimestral</option>
              <option value="semestral">Semestral</option>
              <option value="anual">Anual</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Parcelamento</label>
            <div class="flex gap-1">
              <input id="contaParcelaNum" type="number" min="0" placeholder="Parcela" class="w-1/2 border border-slate-200 rounded-lg px-2 py-2 text-sm" />
              <span class="self-center text-slate-400 text-sm">/</span>
              <input id="contaParcelaTotal" type="number" min="0" placeholder="Total" class="w-1/2 border border-slate-200 rounded-lg px-2 py-2 text-sm" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 py-2 rounded-lg bg-red-700 text-white font-bold hover:bg-red-800">Salvar</button>
          <button type="button" onclick="closeContaModal()" class="flex-1 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CATEGORIAS -->
  <div id="modalCategoria" class="fixed inset-0 bg-black/60 hidden z-[70] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 class="text-lg font-bold">Categorias</h2>
        <button onclick="closeCategoriaModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <div class="p-6">
        <div class="flex gap-2 mb-4">
          <input id="novaCategoriaInput" placeholder="Nova categoria..." class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm" />
          <button onclick="addCategoria()" class="px-4 py-2 rounded-lg bg-red-700 text-white font-bold text-sm hover:bg-red-800">
            <i class="fa-solid fa-plus"></i>
          </button>
        </div>
        <div id="categoriasLista" class="max-h-48 overflow-y-auto space-y-1"></div>
      </div>
    </div>
  </div>

  <!-- MODAL ENTRADA -->
  <div id="modalEntrada" class="fixed inset-0 bg-black/60 hidden z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h2 id="modalEntradaTitulo" class="text-lg font-bold">Nova Entrada</h2>
        <button onclick="closeEntradaModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
      </div>
      <form id="formEntrada" class="p-6 space-y-4" onsubmit="submitEntrada(event)">
        <input type="hidden" id="entradaId" />
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Descrição *</label>
          <input id="entradaDescricao" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Doação, Serviço..." />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Valor (<?= $_crmCurrSymbol ?>) *</label>
            <input id="entradaValor" type="text" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="<?= $_crmCurrSymbol ?>0" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-600 mb-1">Origem</label>
            <select id="entradaOrigem" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
              <option value="manual">Manual</option>
              <option value="mensalidade">Mensalidade</option>
              <option value="trabalho">Trabalho</option>
              <option value="doacao">Doação</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-600 mb-1">Data *</label>
          <input id="entradaData" type="date" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" />
        </div>
        <p class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2">
          <i class="fa-solid fa-circle-info mr-1"></i>
          10% do valor será automaticamente reservado como Crédito Casa.
        </p>
        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 py-2 rounded-lg bg-green-600 text-white font-bold hover:bg-green-700">Salvar</button>
          <button type="button" onclick="closeEntradaModal()" class="flex-1 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <style>
    .tab-btn { color: #64748b; border-bottom: 2px solid transparent; }
    .tab-btn.active { color: #dc2626; border-bottom-color: #dc2626; background: #fff1f2; }
  </style>

  <?php require_once __DIR__ . '/app/views/partials/tw-scripts.php'; ?>
  <script>
    const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    const currentUserRole = <?= json_encode((string)($_SESSION['user_role'] ?? 'user')) ?>;
    const gensenRate = 0.1021;

    function formatBRLCents(cents) {
      return formatBRLOrZero(String(cents || 0));
    }

    function parseCurrency(str) {
      return parseCurrencyInput(str);
    }

    // Real-time currency mask for value inputs
    document.getElementById('contaValor').addEventListener('input', function () {
      this.value = formatBRL(this.value);
    });
    document.getElementById('entradaValor').addEventListener('input', function () {
      this.value = formatBRL(this.value);
    });
    document.getElementById('splitPreviewValor').addEventListener('input', function () {
      this.value = formatBRL(this.value);
      renderSplitPreview();
    });
    document.getElementById('splitValorTotal').addEventListener('input', function () {
      this.value = formatBRL(this.value);
    });

    async function api(params) {
      const isGet = !params.method || params.method === 'GET';
      let url = 'api/financeiro.php?action=' + params.action;
      if (params.query) url += '&' + new URLSearchParams(params.query).toString();
      const opts = isGet ? { method: 'GET' } : { method: 'POST', body: new URLSearchParams(params.body || {}) };
      const r = await fetch(url, opts);
      return r.json();
    }

    function toast(msg, ok = true) {
      const t = document.createElement('div');
      t.className = `fixed bottom-6 right-6 z-[100] px-4 py-3 rounded-xl shadow-lg text-white text-sm font-semibold transition-all ${ok ? 'bg-green-600' : 'bg-red-600'}`;
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3000);
    }

    function confirmDelete(msg, cb) { if (confirm(msg)) cb(); }

    let currentTab = 'caixa';

    function getMes() {
      return document.getElementById('mesFilter').value || new Date().toISOString().slice(0, 7);
    }

    function showTab(name) {
      currentTab = name;
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.getElementById('pane-' + name).classList.remove('hidden');
      document.getElementById('tab-' + name).classList.add('active');
      loadTab(name);
    }

    function loadTab(name) {
      if (name === 'caixa')    loadCaixa();
      if (name === 'contas')   loadContas();
      if (name === 'entradas') loadEntradas();
      if (name === 'credito')  loadCredito();
      if (name === 'split')    loadSplitTab();
      if (name === 'admin')    loadAdminPayables();
    }

    function getSplitConfigPayload() {
      return {
        pct_espaco: parseFloat(document.getElementById('pctEspaco').value || '0'),
        pct_treinamento: parseFloat(document.getElementById('pctTreinamento').value || '0'),
        pct_material: parseFloat(document.getElementById('pctMaterial').value || '0'),
        pct_tata: parseFloat(document.getElementById('pctTata').value || '0'),
        pct_executor: parseFloat(document.getElementById('pctExecutor').value || '0'),
      };
    }

    function setSplitConfigValues(config = {}) {
      document.getElementById('pctEspaco').value = config.pct_espaco ?? 20;
      document.getElementById('pctTreinamento').value = config.pct_treinamento ?? 10;
      document.getElementById('pctMaterial').value = config.pct_material ?? 20;
      document.getElementById('pctTata').value = config.pct_tata ?? 10;
      document.getElementById('pctExecutor').value = config.pct_executor ?? 40;
      updateSplitPercentTotal();
      renderSplitPreview();
    }

    function updateSplitPercentTotal() {
      const cfg = getSplitConfigPayload();
      const total = Object.values(cfg).reduce((sum, n) => sum + (parseFloat(n) || 0), 0);
      const badge = document.getElementById('splitPercentTotal');
      badge.textContent = `Total: ${total.toFixed(2)}%`;
      badge.className = `px-3 py-1 rounded-full text-xs font-bold ${Math.abs(total - 100) < 0.01 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`;
    }

    function calcularSplitLocal(valorTotal) {
      const cfg = getSplitConfigPayload();
      const totalPct = Object.values(cfg).reduce((sum, n) => sum + (parseFloat(n) || 0), 0) || 100;
      const normalized = Object.fromEntries(Object.entries(cfg).map(([k, v]) => [k, ((parseFloat(v) || 0) / totalPct) * 100]));
      const keys = Object.keys(normalized);
      const brutos = {};
      let acumulado = 0;
      keys.forEach((key, index) => {
        if (index === keys.length - 1) {
          brutos[key] = Math.max(0, valorTotal - acumulado);
          return;
        }
        const valor = Math.round(valorTotal * (normalized[key] / 100));
        brutos[key] = valor;
        acumulado += valor;
      });

      const impostoTata = Math.round((brutos.pct_tata || 0) * gensenRate);
      const impostoExecutor = Math.round((brutos.pct_executor || 0) * gensenRate);
      return {
        valor_total: valorTotal,
        brutos,
        imposto_tata: impostoTata,
        imposto_executor: impostoExecutor,
        imposto_total: impostoTata + impostoExecutor,
        liquido_tata: (brutos.pct_tata || 0) - impostoTata,
        liquido_executor: (brutos.pct_executor || 0) - impostoExecutor,
      };
    }

    function renderSplitPreview() {
      updateSplitPercentTotal();
      const valor = parseCurrency(document.getElementById('splitPreviewValor').value || '0');
      const split = calcularSplitLocal(valor);
      document.getElementById('splitPreviewGrid').innerHTML = `
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3"><p class="text-xs text-slate-500">Espaço</p><p class="font-bold">${formatBRLCents(split.brutos.pct_espaco || 0)}</p></div>
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3"><p class="text-xs text-slate-500">Treinamento</p><p class="font-bold">${formatBRLCents(split.brutos.pct_treinamento || 0)}</p></div>
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3"><p class="text-xs text-slate-500">Material</p><p class="font-bold">${formatBRLCents(split.brutos.pct_material || 0)}</p></div>
        <div class="rounded-xl border border-amber-100 bg-amber-50 p-3"><p class="text-xs text-amber-700">Tata líquido</p><p class="font-bold text-amber-800">${formatBRLCents(split.liquido_tata || 0)}</p><p class="text-[11px] text-amber-700">Retido: ${formatBRLCents(split.imposto_tata || 0)}</p></div>
        <div class="rounded-xl border border-sky-100 bg-sky-50 p-3"><p class="text-xs text-sky-700">Executor líquido</p><p class="font-bold text-sky-800">${formatBRLCents(split.liquido_executor || 0)}</p><p class="text-[11px] text-sky-700">Retido: ${formatBRLCents(split.imposto_executor || 0)}</p></div>
        <div class="rounded-xl border border-rose-100 bg-rose-50 p-3"><p class="text-xs text-rose-700">Zeimusho</p><p class="font-bold text-rose-800">${formatBRLCents(split.imposto_total || 0)}</p></div>`;
    }

    async function loadSplitUsers() {
      const d = await api({ action: 'list_financial_users' });
      if (!d.ok) return;
      const mediumSel = document.getElementById('splitMediumUser');
      const tataSel = document.getElementById('splitTataUser');
      const users = d.data || [];
      mediumSel.innerHTML = users.map(u => `<option value="${u.id}">${u.name}${u.role ? ` — ${u.role}` : ''}</option>`).join('');
      tataSel.innerHTML = '<option value="">Sem Tata</option>' + users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
      const preferred = users.find(u => String(u.id) === String(currentUserId));
      if (preferred) mediumSel.value = String(preferred.id);
      if (currentUserRole !== 'admin') {
        mediumSel.value = String(currentUserId);
        mediumSel.disabled = true;
      }
    }

    function getSplitPrefillFromQuery() {
      const params = new URLSearchParams(window.location.search);
      if (params.get('tab') !== 'split' && !params.get('source')) {
        return null;
      }
      return {
        cliente_nome: params.get('cliente_nome') || '',
        cliente_telefone: params.get('cliente_telefone') || '',
        descricao_servico: params.get('descricao_servico') || '',
        valor_total: params.get('valor_total') || '',
        data_realizacao: params.get('data_realizacao') || '',
        data_pagamento: params.get('data_pagamento') || '',
        status_pagamento: params.get('status_pagamento') || 'pendente',
      };
    }

    function applySplitPrefillFromQuery() {
      if (window._splitPrefillApplied) return;
      const prefill = getSplitPrefillFromQuery();
      if (!prefill) return;

      if (prefill.cliente_nome) document.getElementById('splitClienteNome').value = prefill.cliente_nome;
      if (prefill.cliente_telefone) document.getElementById('splitClienteTelefone').value = prefill.cliente_telefone;
      if (prefill.descricao_servico) document.getElementById('splitDescricaoServico').value = prefill.descricao_servico;
      if (prefill.valor_total) {
        document.getElementById('splitValorTotal').value = formatBRL(prefill.valor_total);
        document.getElementById('splitPreviewValor').value = formatBRL(prefill.valor_total);
      }
      if (prefill.data_realizacao) document.getElementById('splitDataRealizacao').value = prefill.data_realizacao;
      if (prefill.data_pagamento) document.getElementById('splitDataPagamento').value = prefill.data_pagamento;
      if (prefill.status_pagamento) document.getElementById('splitStatusPagamento').value = prefill.status_pagamento;

      renderSplitPreview();
      window._splitPrefillApplied = true;
      toast('Dados do trabalho carregados no Split');
    }

    async function loadMediumConfig() {
      const userId = document.getElementById('splitMediumUser').value || currentUserId;
      const valorPreview = parseCurrency(document.getElementById('splitPreviewValor').value || '0') || 100000;
      const d = await api({ action: 'get_medium_config', query: { user_id: userId, valor_preview: valorPreview } });
      if (!d.ok) return toast(d.message || 'Erro ao carregar configuração', false);
      setSplitConfigValues(d.data || {});
    }

    async function saveMediumConfig(e) {
      e.preventDefault();
      const userId = document.getElementById('splitMediumUser').value || currentUserId;
      const body = { user_id: userId, valor_preview: parseCurrency(document.getElementById('splitPreviewValor').value || '0') || 100000, ...getSplitConfigPayload() };
      const d = await api({ action: 'save_medium_config', method: 'POST', body });
      if (d.ok) {
        setSplitConfigValues(d.data || {});
        toast('Percentuais salvos com sucesso');
      } else {
        toast(d.message || 'Erro ao salvar percentuais', false);
      }
    }

    async function submitSplitTransaction(e) {
      e.preventDefault();
      const body = {
        medium_id: document.getElementById('splitMediumUser').value || currentUserId,
        tata_id: document.getElementById('splitTataUser').value,
        cliente_nome: document.getElementById('splitClienteNome').value,
        cliente_telefone: document.getElementById('splitClienteTelefone').value,
        descricao_servico: document.getElementById('splitDescricaoServico').value,
        valor_total: parseCurrency(document.getElementById('splitValorTotal').value),
        data_realizacao: document.getElementById('splitDataRealizacao').value,
        data_pagamento: document.getElementById('splitDataPagamento').value,
        status_pagamento: document.getElementById('splitStatusPagamento').value,
      };
      const d = await api({ action: 'registrar_split_trabalho', method: 'POST', body });
      if (d.ok) {
        toast(d.receipt_path ? 'Trabalho salvo e recibo gerado' : 'Trabalho financeiro salvo');
        document.getElementById('formSplitTransaction').reset();
        document.getElementById('splitDescricaoServico').value = '宗教儀式提供料として';
        document.getElementById('splitDataRealizacao').value = new Date().toISOString().slice(0, 10);
        document.getElementById('splitDataPagamento').value = new Date().toISOString().slice(0, 10);
        loadFinancialTransactions();
        if (d.receipt_view_url) window.open(d.receipt_view_url, '_blank');
      } else {
        toast(d.message || 'Erro ao salvar trabalho', false);
      }
    }

    async function loadFinancialTransactions() {
      const d = await api({ action: 'list_financial_transactions' });
      const body = document.getElementById('splitTransactionsBody');
      if (!d.ok) {
        body.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500">Erro ao carregar transações</td></tr>';
        return;
      }

      body.innerHTML = (d.data || []).length === 0
        ? '<tr><td colspan="8" class="px-4 py-6 text-center text-slate-400">Nenhuma transação registrada</td></tr>'
        : d.data.map(t => {
          const options = ['pendente', 'processando', 'pago', 'cancelado']
            .map(status => `<option value="${status}" ${status === t.status_pagamento ? 'selected' : ''}>${status}</option>`).join('');
          return `
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3"><div>${t.data_realizacao || '-'}</div><div class="text-xs text-slate-500">Pgto: ${t.data_pagamento || '-'}</div></td>
              <td class="px-4 py-3"><div class="font-medium">${t.cliente_nome || '-'}</div><div class="text-xs text-slate-500">${t.cliente_telefone || ''}</div></td>
              <td class="px-4 py-3"><div>${t.medium_name || '-'}</div><div class="text-xs text-slate-500">Tata: ${t.tata_name || '-'}</div></td>
              <td class="px-4 py-3"><div class="flex items-center gap-2"><select onchange="updateFinancialStatus(${t.id}, this.value, '${esc(t.data_pagamento || '')}')" class="border border-slate-200 rounded px-2 py-1 text-xs">${options}</select></div></td>
              <td class="px-4 py-3 text-right font-semibold">${formatBRLCents(t.valor_total)}</td>
              <td class="px-4 py-3 text-right text-rose-600">${formatBRLCents(t.taxa_gensen_paga)}</td>
              <td class="px-4 py-3 text-right text-sky-700 font-semibold">${formatBRLCents(t.valor_liquido_medium)}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex gap-2 justify-end">
                  <button onclick="generateReceipt(${t.id})" class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700 font-bold hover:bg-amber-200">Gerar</button>
                  ${t.receipt_path ? `<a href="${t.receipt_path}" target="_blank" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">PDF</a>` : ''}
                  <a href="ryoushuusho.php?id=${t.id}" target="_blank" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Visualizar</a>
                </div>
              </td>
            </tr>`;
        }).join('');
    }

    async function updateFinancialStatus(id, status_pagamento, data_pagamento_atual = '') {
      const body = { id, status_pagamento };
      if (status_pagamento === 'pago') {
        body.data_pagamento = data_pagamento_atual || new Date().toISOString().slice(0, 10);
      }
      const d = await api({ action: 'update_financial_status', method: 'POST', body });
      if (!d.ok) {
        toast(d.message || 'Erro ao atualizar status', false);
        return;
      }
      if (d.receipt_regenerated) {
        toast('Pago confirmado e recibo atualizado');
        loadFinancialTransactions();
        window.open(d.view_url || `ryoushuusho.php?id=${id}`, '_blank');
        return;
      }
      toast('Status atualizado');
      loadFinancialTransactions();
    }

    async function generateReceipt(id) {
      const d = await api({ action: 'generate_receipt', method: 'POST', body: { id } });
      if (d.ok) {
        toast(`Recibo salvo em ${d.path}`);
        loadFinancialTransactions();
        window.open(d.view_url || `ryoushuusho.php?id=${id}`, '_blank');
      } else {
        toast(d.message || 'Erro ao gerar recibo', false);
      }
    }

    async function loadSplitTab() {
      if (!window._splitTabBootstrapped) {
        await loadSplitUsers();
        document.getElementById('splitDataRealizacao').value = new Date().toISOString().slice(0, 10);
        document.getElementById('splitDataPagamento').value = new Date().toISOString().slice(0, 10);
        document.getElementById('formMediumConfig').addEventListener('submit', saveMediumConfig);
        document.getElementById('formSplitTransaction').addEventListener('submit', submitSplitTransaction);
        document.getElementById('splitMediumUser').addEventListener('change', loadMediumConfig);
        ['pctEspaco','pctTreinamento','pctMaterial','pctTata','pctExecutor'].forEach(id => {
          document.getElementById(id).addEventListener('input', renderSplitPreview);
        });
        document.getElementById('btnSplitPreviewSync').addEventListener('click', () => {
          document.getElementById('splitPreviewValor').value = document.getElementById('splitValorTotal').value || '';
          renderSplitPreview();
        });
        window._splitTabBootstrapped = true;
      }
      await loadMediumConfig();
      applySplitPrefillFromQuery();
      await loadFinancialTransactions();
    }

    async function loadDashboard() {
      const mes = getMes();
      const d = await api({ action: 'dashboard', query: { mes } });
      if (!d.ok) return;
      const r = d.data;
      const saldoColor = r.saldo >= 0 ? 'text-slate-700' : 'text-red-600';
      document.getElementById('cardEntradas').textContent = formatBRLCents(r.total_entradas);
      document.getElementById('cardSaidas').textContent = formatBRLCents(r.total_saidas);
      document.getElementById('cardSaldo').className = `text-2xl font-black mt-1 ${saldoColor}`;
      document.getElementById('cardSaldo').textContent = formatBRLCents(r.saldo);
      document.getElementById('cardCredito').textContent = formatBRLCents(r.total_credito_casa);
      document.getElementById('cardMensPagas').textContent = formatBRLCents(r.mensalidades_pagas);
      document.getElementById('cardMensPend').textContent = formatBRLCents(r.mensalidades_pendentes);
      document.getElementById('cardContasPend').textContent =
        `${r.contas_pendentes_qtd} — ${formatBRLCents(r.contas_pendentes_valor)}`;
    }

    async function loadCaixa() {
      const mes = getMes() + '-01';
      const d = await api({ action: 'list_caixa', query: { month: mes } });
      if (!d.ok) return;
      const s = d.summary;
      document.getElementById('caixaSummary').innerHTML = `
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase">Saldo Inicial</p>
          <p class="text-xl font-black ${s.saldo_inicial >= 0 ? 'text-slate-700' : 'text-red-600'} mt-1">${formatBRLCents(s.saldo_inicial)}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase">Entradas / Saídas (realizadas)</p>
          <p class="text-xl font-black text-green-600 mt-1">${formatBRLCents(s.entradas)} / <span class="text-red-600">${formatBRLCents(s.saidas)}</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
          <p class="text-xs text-slate-400 font-semibold uppercase">Saldo Final</p>
          <p class="text-xl font-black ${s.saldo_final >= 0 ? 'text-slate-700' : 'text-red-600'} mt-1">${formatBRLCents(s.saldo_final)}</p>
        </div>
      `;
      const body = document.getElementById('caixaBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Nenhum movimento neste mês</td></tr>'
        : d.data.map(m => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3">${m.data_movimento}</td>
            <td class="px-4 py-3">${m.descricao || '-'}</td>
            <td class="px-4 py-3 capitalize">${m.origem.replace('_', ' ')}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${m.status === 'realizado' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'}">
                ${m.status}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold ${m.tipo === 'entrada' ? 'text-green-600' : 'text-red-600'}">
              ${m.tipo === 'entrada' ? '+' : '-'} ${formatBRLCents(m.valor)}
            </td>
          </tr>`).join('');
    }

    async function loadContas() {
      const d = await api({ action: 'list_contas' });
      if (!d.ok) return;
      const body = document.getElementById('contasBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="9" class="px-4 py-6 text-center text-slate-400">Nenhuma conta cadastrada</td></tr>'
        : d.data.map(c => {
          const parcelaStr = (c.parcela_num && c.parcela_total) ? `${c.parcela_num}/${c.parcela_total}` : '-';
          const statusCls = c.status === 'Pago' ? 'bg-green-100 text-green-700'
            : (c.status === 'Vencido' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700');
          const valorPago = c.valor_pago ? formatBRLCents(c.valor_pago) : '-';
          return `<tr class="hover:bg-slate-50">
            <td class="px-3 py-3 font-medium">${c.descricao}</td>
            <td class="px-3 py-3 text-slate-500 text-xs">${c.fornecedor || '-'}</td>
            <td class="px-3 py-3 text-slate-500 text-xs">${c.categoria || '-'}</td>
            <td class="px-3 py-3 text-xs">${c.data_vencimento}</td>
            <td class="px-3 py-3 text-xs">${parcelaStr}</td>
            <td class="px-3 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusCls}">${c.status}</span>
            </td>
            <td class="px-3 py-3 text-right font-semibold">${formatBRLCents(c.valor)}</td>
            <td class="px-3 py-3 text-right text-green-600 text-xs">${valorPago}</td>
            <td class="px-3 py-3 text-right">
              <div class="flex gap-1 justify-end flex-wrap">
                ${c.status !== 'Pago' ? `<button onclick="pagarConta(${c.id})" class="px-2 py-1 rounded text-xs bg-green-100 text-green-700 font-bold hover:bg-green-200">Pagar</button>` : ''}
                <button onclick="editConta(${c.id})" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">Editar</button>
                <button onclick="deleteConta(${c.id})" class="px-2 py-1 rounded text-xs bg-red-100 text-red-600 font-bold hover:bg-red-200">Excluir</button>
              </div>
            </td>
          </tr>`;
        }).join('');
      // cache for editConta
      window._contasCache = d.data;
    }

    function editConta(id) {
      const c = (window._contasCache || []).find(x => x.id == id);
      if (!c) return;
      openContaModal();
      document.getElementById('contaId').value = c.id;
      document.getElementById('modalContaTitulo').textContent = 'Editar Conta';
      document.getElementById('contaDescricao').value = c.descricao || '';
      document.getElementById('contaCategoria').value = c.categoria || '';
      document.getElementById('contaValor').value = formatBRL(String(c.valor));
      document.getElementById('contaFornecedor').value = c.fornecedor || '';
      document.getElementById('contaVencimento').value = c.data_vencimento || '';
      document.getElementById('contaRecorrencia').value = c.recorrencia || 'nenhuma';
      document.getElementById('contaParcelaNum').value = c.parcela_num || '';
      document.getElementById('contaParcelaTotal').value = c.parcela_total || '';
    }

    function openContaModal() {
      loadCategorias();
      document.getElementById('contaId').value = '';
      document.getElementById('modalContaTitulo').textContent = 'Nova Conta';
      document.getElementById('formConta').reset();
      document.getElementById('contaVencimento').value = new Date().toISOString().slice(0, 10);
      document.getElementById('modalConta').classList.remove('hidden');
    }

    function closeContaModal() {
      document.getElementById('modalConta').classList.add('hidden');
      document.getElementById('formConta').reset();
    }

    async function submitConta(e) {
      e.preventDefault();
      const id = document.getElementById('contaId').value;
      const action = id ? 'update_conta' : 'create_conta';
      const descricao = document.getElementById('contaDescricao').value;
      const valor = parseCurrency(document.getElementById('contaValor').value);
      const categoria = document.getElementById('contaCategoria').value;
      const vencimento = document.getElementById('contaVencimento').value;
      const fornecedor = document.getElementById('contaFornecedor').value;
      const recorrencia = document.getElementById('contaRecorrencia').value;
      const parcela_num = document.getElementById('contaParcelaNum').value || 0;
      const parcela_total = document.getElementById('contaParcelaTotal').value || 0;
      const body = { descricao, valor, categoria, data_vencimento: vencimento, fornecedor, recorrencia, parcela_num, parcela_total };
      if (id) body.id = id;
      const d = await api({ action, method: 'POST', body });
      if (d.ok) { toast('Conta salva!'); closeContaModal(); loadContas(); loadDashboard(); }
      else toast(d.message || 'Erro ao salvar', false);
    }

    async function pagarConta(id) {
      const valorStr = prompt('Valor pago (deixe em branco para valor total):');
      if (valorStr === null) return;
      const body = { id };
      if (valorStr.trim()) body.valor_pago = parseCurrency(valorStr);
      const d = await api({ action: 'pay_conta', method: 'POST', body });
      if (d.ok) { toast('Pago!'); loadContas(); loadDashboard(); if (currentTab === 'caixa') loadCaixa(); }
      else toast(d.message || 'Erro', false);
    }

    /* ── Categorias ───────────────────────────── */
    async function loadCategorias() {
      const d = await api({ action: 'list_categorias' });
      if (!d.ok) return;
      const sel = document.getElementById('contaCategoria');
      const prev = sel.value;
      sel.innerHTML = '<option value="">Selecione...</option>';
      d.data.forEach(c => {
        const o = document.createElement('option');
        o.value = c.nome;
        o.textContent = c.nome;
        sel.appendChild(o);
      });
      if (prev) sel.value = prev;
      // Update the categorias list modal too
      const lista = document.getElementById('categoriasLista');
      if (lista) {
        lista.innerHTML = d.data.map(c => `
          <div class="flex items-center justify-between bg-slate-50 px-3 py-2 rounded-lg text-sm">
            <span>${c.nome}</span>
            <button onclick="deleteCategoria(${c.id})" class="text-red-500 hover:text-red-700 text-xs font-bold"><i class="fa-solid fa-trash"></i></button>
          </div>`).join('');
      }
    }

    function openCategoriaModal() {
      loadCategorias();
      document.getElementById('modalCategoria').classList.remove('hidden');
    }

    function closeCategoriaModal() {
      document.getElementById('modalCategoria').classList.add('hidden');
    }

    async function addCategoria() {
      const nome = document.getElementById('novaCategoriaInput').value.trim();
      if (!nome) return;
      const d = await api({ action: 'create_categoria', method: 'POST', body: { nome } });
      if (d.ok) { document.getElementById('novaCategoriaInput').value = ''; loadCategorias(); toast('Categoria criada!'); }
      else toast(d.message || 'Erro', false);
    }

    async function deleteCategoria(id) {
      if (!confirm('Excluir esta categoria?')) return;
      const d = await api({ action: 'delete_categoria', method: 'POST', body: { id } });
      if (d.ok) { loadCategorias(); toast('Categoria excluída'); }
      else toast(d.message || 'Erro', false);
    }

    async function carryOverContas() {
      if (!confirm('Mover todas as contas vencidas não-pagas para o próximo mês?')) return;
      const d = await api({ action: 'carry_over', method: 'POST' });
      if (d.ok) {
        toast(`${d.carried || 0} conta(s) movidas para o próximo mês`);
        loadContas(); loadDashboard();
      } else toast(d.message || 'Erro', false);
    }

    async function deleteConta(id) {
      confirmDelete('Excluir esta conta?', async () => {
        const d = await api({ action: 'delete_conta', method: 'POST', body: { id } });
        if (d.ok) { toast('Conta excluída'); loadContas(); loadDashboard(); }
        else toast(d.message || 'Erro', false);
      });
    }

    async function loadEntradas() {
      const d = await api({ action: 'list_entradas' });
      if (!d.ok) return;
      const body = document.getElementById('entradasBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Nenhuma entrada cadastrada</td></tr>'
        : d.data.map(e => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium">${e.descricao}</td>
            <td class="px-4 py-3 capitalize text-slate-500">${e.origem}</td>
            <td class="px-4 py-3">${e.data_entrada}</td>
            <td class="px-4 py-3 text-right font-semibold text-green-600">${formatBRLCents(e.valor)}</td>
            <td class="px-4 py-3 text-right text-amber-600">${formatBRLCents(Math.round(e.valor * 0.10))}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-1 justify-end">
                <button onclick="openEntradaModal(${e.id},'${esc(e.descricao)}','${e.origem}',${e.valor},'${e.data_entrada}')" class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">Editar</button>
                <button onclick="deleteEntrada(${e.id})" class="px-2 py-1 rounded text-xs bg-red-100 text-red-600 font-bold hover:bg-red-200">Excluir</button>
              </div>
            </td>
          </tr>`).join('');
    }

    function openEntradaModal(id, descricao, origem, valor, data) {
      document.getElementById('entradaId').value = id || '';
      document.getElementById('modalEntradaTitulo').textContent = id ? 'Editar Entrada' : 'Nova Entrada';
      document.getElementById('entradaDescricao').value = descricao || '';
      document.getElementById('entradaOrigem').value = origem || 'manual';
      document.getElementById('entradaValor').value = id ? formatBRL(String(valor)) : '';
      document.getElementById('entradaData').value = data || new Date().toISOString().slice(0, 10);
      document.getElementById('modalEntrada').classList.remove('hidden');
    }

    function closeEntradaModal() {
      document.getElementById('modalEntrada').classList.add('hidden');
      document.getElementById('formEntrada').reset();
    }

    async function submitEntrada(e) {
      e.preventDefault();
      const id = document.getElementById('entradaId').value;
      const action = id ? 'update_entrada' : 'create_entrada';
      const descricao = document.getElementById('entradaDescricao').value;
      const valor = parseCurrency(document.getElementById('entradaValor').value);
      const origem = document.getElementById('entradaOrigem').value;
      const data_entrada = document.getElementById('entradaData').value;
      const body = { descricao, valor, origem, data_entrada };
      if (id) body.id = id;
      const d = await api({ action, method: 'POST', body });
      if (d.ok) { toast('Entrada salva!'); closeEntradaModal(); loadEntradas(); loadDashboard(); }
      else toast(d.message || 'Erro ao salvar', false);
    }

    async function deleteEntrada(id) {
      confirmDelete('Excluir esta entrada? O crédito casa associado também será removido.', async () => {
        const d = await api({ action: 'delete_entrada', method: 'POST', body: { id } });
        if (d.ok) { toast('Entrada excluída'); loadEntradas(); loadDashboard(); }
        else toast(d.message || 'Erro', false);
      });
    }

    async function loadCredito() {
      const d = await api({ action: 'list_credito_casa' });
      if (!d.ok) return;
      const body = document.getElementById('creditoBody');
      body.innerHTML = d.data.length === 0
        ? '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Nenhum registro de crédito casa</td></tr>'
        : d.data.map(c => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3">${c.data}</td>
            <td class="px-4 py-3">${c.descricao || c.entrada_descricao || '-'}</td>
            <td class="px-4 py-3 text-right">${formatBRLCents(c.valor_original)}</td>
            <td class="px-4 py-3 text-right">${parseFloat(c.percentual).toFixed(0)}%</td>
            <td class="px-4 py-3 text-right font-semibold text-amber-600">${formatBRLCents(c.valor_credito)}</td>
          </tr>`).join('');
    }

    async function loadAdminPayables() {
      const d = await api({ action: 'list_admin_payables' });
      if (!d.ok) {
        document.getElementById('adminPayablesBody').innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-red-500">Erro ao carregar dados</td></tr>';
        return;
      }

      const totals = d.totals || {};
      document.getElementById('adminTotalRealizado').textContent = formatBRLCents(totals.valor_total_realizado || 0);
      document.getElementById('adminTotalImposto').textContent = formatBRLCents(totals.imposto_total || 0);
      document.getElementById('adminTotalLiquido').textContent = formatBRLCents(totals.valor_liquido_total || 0);
      document.getElementById('adminTotalPago').textContent = formatBRLCents(totals.valor_pago_total || 0);
      document.getElementById('adminTotalPendente').textContent = formatBRLCents(totals.valor_pendente_total || 0);

      const body = document.getElementById('adminPayablesBody');
      body.innerHTML = (d.data || []).length === 0
        ? '<tr><td colspan="8" class="px-4 py-6 text-center text-slate-400">Nenhum médium registrado</td></tr>'
        : d.data.map(m => `
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <div class="font-medium">${m.medium_name || 'Sem médium'}</div>
              <div class="text-xs text-slate-500">${m.medium_phone || '-'}</div>
            </td>
            <td class="px-4 py-3 text-center text-slate-600">${m.total_transacoes || 0}</td>
            <td class="px-4 py-3 text-right font-semibold">${formatBRLCents(m.valor_total_realizado || 0)}</td>
            <td class="px-4 py-3 text-right text-rose-600">${formatBRLCents(m.imposto_total || 0)}</td>
            <td class="px-4 py-3 text-right font-semibold text-blue-700">${formatBRLCents(m.valor_liquido_medium || 0)}</td>
            <td class="px-4 py-3 text-right text-green-600">${formatBRLCents(m.valor_pago || 0)}</td>
            <td class="px-4 py-3 text-right font-bold ${(m.valor_pendente || 0) > 0 ? 'text-amber-600' : 'text-slate-400'}">${formatBRLCents(m.valor_pendente || 0)}</td>
            <td class="px-4 py-3 text-xs text-slate-500">${m.ultima_transacao || '-'}</td>
          </tr>`).join('');
    }

    function esc(s) {
      return String(s).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', () => {
      const today = new Date();
      document.getElementById('mesFilter').value =
        today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
      document.getElementById('mesFilter').addEventListener('change', () => {
        loadDashboard();
        loadTab(currentTab);
      });
      loadDashboard();
      const initialTab = new URLSearchParams(window.location.search).get('tab') || 'caixa';
      showTab(['caixa', 'contas', 'entradas', 'credito', 'split'].includes(initialTab) ? initialTab : 'caixa');
    });
  </script>
</body>
</html>