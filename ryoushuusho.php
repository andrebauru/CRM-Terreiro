<?php
$pageTitle = 'CRM Terreiro - 領収書';
$activePage = 'financeiro';
$extraHead = <<<'HTML'
<style>
  @media print {
    .no-print { display: none !important; }
    body { background: #fff !important; }
    .receipt-shell { box-shadow: none !important; border-color: #111 !important; }
  }
</style>
HTML;
require_once __DIR__ . '/app/views/partials/tw-head.php';
require_once __DIR__ . '/app/Helpers/FinancialReceipt.php';

$pdo = db();
$transactionId = (int)($_GET['id'] ?? 0);
$transaction = null;
$receiptSavedPath = null;

if ($transactionId > 0) {
    $stmt = $pdo->prepare(
        "SELECT ft.*, m.name AS medium_name, t.name AS tata_name
         FROM financial_transactions ft
         LEFT JOIN users m ON m.id = ft.medium_id
         LEFT JOIN users t ON t.id = ft.tata_id
         WHERE ft.id = ?
         LIMIT 1"
    );
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$valor = $transaction ? (int)$transaction['valor_total'] : (int)($_GET['valor'] ?? 0);
$dataRecibo = $transaction['data_pagamento'] ?? $transaction['data_realizacao'] ?? ($_GET['data'] ?? date('Y-m-d'));
$destinatario = trim((string)($_GET['destinatario'] ?? ($transaction['cliente_nome'] ?? $transaction['medium_name'] ?? '')));
$descricao = trim((string)($_GET['descricao'] ?? ($transaction['descricao_servico'] ?? '宗教儀式提供料として')));
$descricaoPt = trim((string)($_GET['descricao_pt'] ?? 'Referente a serviços de cerimônia religiosa'));
$npoNome = trim((string)($_crmSettings['company_name'] ?? 'CRM Terreiro'));
$npoEndereco = trim((string)($_GET['endereco'] ?? 'Tsu, Mie, Japão'));
$impostoRetido = $transaction ? (int)$transaction['taxa_gensen_paga'] : (int)($_GET['imposto'] ?? 0);
$valorLiquidoMedium = $transaction ? (int)$transaction['valor_liquido_medium'] : (int)($_GET['valor_liquido'] ?? max(0, $valor - $impostoRetido));
$mostrarSelo = $valor > 50000;

if ($transaction && (isset($_GET['save']) && $_GET['save'] === '1')) {
  try {
    $saved = financialReceiptSavePdf([
      'receipt_no' => (string)$transaction['id'],
      'data_realizacao' => (string)$dataRecibo,
      'data_pagamento' => (string)$dataRecibo,
      'valor_total' => $valor,
      'imposto_retido' => $impostoRetido,
      'valor_liquido_medium' => $valorLiquidoMedium,
      'destinatario' => $destinatario,
      'cliente_nome' => (string)($transaction['cliente_nome'] ?? ''),
      'cliente_telefone' => (string)($transaction['cliente_telefone'] ?? ''),
      'descricao_jp' => $descricao,
      'descricao_pt' => $descricaoPt,
      'npo_nome' => $npoNome,
      'npo_endereco' => $npoEndereco,
      'medium_name' => (string)($transaction['medium_name'] ?? '—'),
      'tata_name' => (string)($transaction['tata_name'] ?? '—'),
    ], __DIR__);
    $receiptSavedPath = $saved['relative_path'];
    $pdo->prepare('UPDATE financial_transactions SET receipt_path = ? WHERE id = ?')
      ->execute([$receiptSavedPath, $transactionId]);
    $transaction['receipt_path'] = $receiptSavedPath;
  } catch (Throwable $e) {
    $receiptSavedPath = null;
  }
}

if (!$receiptSavedPath && !empty($transaction['receipt_path'])) {
  $receiptSavedPath = (string)$transaction['receipt_path'];
}
?>
<body class="bg-slate-100 font-sans text-slate-900">
  <div class="min-h-screen flex overflow-x-hidden">
    <?php require_once __DIR__ . '/app/views/partials/tw-sidebar.php'; ?>

    <main class="flex-1 min-w-0 p-4 pt-16 md:p-8">
      <div class="max-w-4xl mx-auto">
        <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-4">
          <div>
            <h1 class="text-2xl font-bold">領収書 / Recibo</h1>
            <p class="text-sm text-slate-500">Modelo bilíngue JP/PT para trabalhos espirituais com retenção fiscal.</p>
          </div>
          <div class="flex gap-2 flex-wrap">
            <?php if ($transactionId > 0): ?>
              <a href="?id=<?= $transactionId ?>&save=1" class="px-4 py-2 rounded-xl border border-slate-200 font-bold hover:bg-slate-50">
                <i class="fa-solid fa-floppy-disk mr-1"></i>Salvar PDF
              </a>
            <?php endif; ?>
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-red-700 text-white font-bold hover:bg-red-800">
              <i class="fa-solid fa-print mr-1"></i>Imprimir
            </button>
          </div>
        </div>

        <?php if ($receiptSavedPath): ?>
          <div class="no-print mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <strong>Arquivo salvo:</strong> <?= htmlspecialchars($receiptSavedPath) ?>
            <a href="<?= htmlspecialchars($receiptSavedPath) ?>" target="_blank" class="ml-2 font-semibold underline">Abrir PDF</a>
          </div>
        <?php endif; ?>

        <?php if ($mostrarSelo): ?>
          <div class="no-print mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Lembrete fiscal:</strong> se este recibo for impresso e o valor exceder ¥50.000, colar selo de imposto de ¥200 (Shunyu Inshi). Recibos digitais normalmente dispensam o selo físico.
          </div>
        <?php endif; ?>

        <section class="receipt-shell bg-white border-2 border-slate-900 rounded-3xl shadow-xl p-8">
          <div class="flex items-start justify-between gap-4 border-b border-slate-300 pb-4 mb-6">
            <div>
              <p class="text-sm tracking-[0.25em] text-slate-500">領収書</p>
              <h2 class="text-3xl font-black">RECIBO</h2>
            </div>
            <div class="text-right text-sm">
              <p><span class="font-semibold">Data / 日付:</span> <?= htmlspecialchars(date('Y/m/d', strtotime($dataRecibo))) ?></p>
              <p><span class="font-semibold">No.:</span> <?= $transactionId > 0 ? htmlspecialchars((string)$transactionId) : '—' ?></p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-6 mb-6">
            <div>
              <p class="text-sm text-slate-500 mb-1">様 / Sr(a).</p>
              <div class="min-h-12 border-b border-slate-700 text-lg font-semibold pt-2"><?= htmlspecialchars($destinatario !== '' ? $destinatario : '________________________________') ?></div>
            </div>
            <div class="w-full md:w-64">
              <p class="text-sm text-slate-500 mb-1">金額 / Valor</p>
              <div class="border-b border-slate-900 py-2 text-4xl font-black text-center">¥ <?= number_format($valor, 0, ',', ',') ?>-</div>
            </div>
          </div>

          <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-700 mb-2">但し書き / Referente a</p>
            <p class="text-lg font-semibold"><?= htmlspecialchars($descricao) ?></p>
            <p class="text-sm text-slate-600 mt-1"><?= htmlspecialchars($descricaoPt) ?></p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-3 text-sm">
              <div class="rounded-2xl border border-slate-200 p-4">
                <p class="font-semibold mb-2">日本語</p>
                <p>上記金額を正に領収いたしました。</p>
                <p class="mt-2">源泉徴収税額: ¥ <?= number_format($impostoRetido, 0, ',', ',') ?></p>
                <p>支払予定額: ¥ <?= number_format($valorLiquidoMedium, 0, ',', ',') ?></p>
              </div>
            </div>
            <div class="space-y-3 text-sm">
              <div class="rounded-2xl border border-slate-200 p-4">
                <p class="font-semibold mb-2">Português</p>
                <p>Recebemos o valor acima referente ao serviço religioso descrito.</p>
                <p class="mt-2">Imposto retido na fonte: ¥ <?= number_format($impostoRetido, 0, ',', ',') ?></p>
                <p>Valor líquido do médium: ¥ <?= number_format($valorLiquidoMedium, 0, ',', ',') ?></p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-[1fr_120px] gap-6 items-end mb-6">
            <div>
              <p class="font-semibold text-lg"><?= htmlspecialchars($npoNome) ?></p>
              <p class="text-sm text-slate-600"><?= htmlspecialchars($npoEndereco) ?></p>
              <p class="text-sm text-slate-600 mt-2">Medium / Executor: <?= htmlspecialchars((string)($transaction['medium_name'] ?? $destinatario ?: '—')) ?></p>
              <p class="text-sm text-slate-600">Tata: <?= htmlspecialchars((string)($transaction['tata_name'] ?? '—')) ?></p>
            </div>
            <div class="h-[120px] border-2 border-slate-800 rounded-2xl flex items-center justify-center text-lg font-bold tracking-[0.2em]">
              印<br>HANKO
            </div>
          </div>

          <footer class="border-t border-dashed border-slate-300 pt-4 text-xs text-slate-600 leading-6">
            <p>JP: 本書類には、日本の税法に基づく源泉徴収（10.21%）の控除額が明記されています。控除額は税務署納付用として留保されます。</p>
            <p>PT: Este recibo informa a retenção de imposto de renda na fonte (Gensen Choshu - 10,21%) conforme a legislação tributária japonesa. O valor retido permanece reservado para recolhimento ao Zeimusho.</p>
          </footer>
        </section>
      </div>
    </main>
  </div>
</body>
</html>
