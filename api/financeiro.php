<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';
require_once __DIR__ . '/../app/Helpers/FinanceSplit.php';
require_once __DIR__ . '/../app/Helpers/FinancialReceipt.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

// ─── Helpers ───────────────────────────────────────────────────────────────

function monthStart(DateTime $date): string
{
    return $date->format('Y-m-01');
}

function monthBounds(string $ms): array
{
    $start = new DateTime($ms);
    $end   = (clone $start)->modify('first day of next month');
    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

function calcularCreditoCasa(int $valorCentavos): int
{
    return (int)round($valorCentavos * 0.10);
}

function obterMediumConfig(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT user_id, pct_espaco, pct_treinamento, pct_material, pct_tata, pct_executor
         FROM medium_configs
         WHERE user_id = ?'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    $config = $row ?: array_merge(['user_id' => $userId], CRM_MEDIUM_SPLIT_DEFAULTS);
    foreach (CRM_MEDIUM_SPLIT_DEFAULTS as $key => $value) {
        $config[$key] = isset($config[$key]) ? (float)$config[$key] : $value;
    }

    return $config;
}

function salvarMediumConfig(PDO $pdo, int $userId, array $payload): array
{
    $config = [];
    foreach (CRM_MEDIUM_SPLIT_DEFAULTS as $key => $value) {
        $config[$key] = isset($payload[$key]) ? (float)$payload[$key] : $value;
    }

    $pdo->prepare(
        'INSERT INTO medium_configs (user_id, pct_espaco, pct_treinamento, pct_material, pct_tata, pct_executor)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            pct_espaco = VALUES(pct_espaco),
            pct_treinamento = VALUES(pct_treinamento),
            pct_material = VALUES(pct_material),
            pct_tata = VALUES(pct_tata),
            pct_executor = VALUES(pct_executor)'
    )->execute([
        $userId,
        $config['pct_espaco'],
        $config['pct_treinamento'],
        $config['pct_material'],
        $config['pct_tata'],
        $config['pct_executor'],
    ]);

    return obterMediumConfig($pdo, $userId);
}

function validarStatusFinanceiro(?string $status): string
{
    $status = strtolower(trim((string)$status));
    $permitidos = ['pendente', 'processando', 'pago', 'cancelado'];
    return in_array($status, $permitidos, true) ? $status : 'pendente';
}

function obterFinancialTransaction(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT ft.*, m.name AS medium_name, t.name AS tata_name
         FROM financial_transactions ft
         LEFT JOIN users m ON m.id = ft.medium_id
         LEFT JOIN users t ON t.id = ft.tata_id
         WHERE ft.id = ?
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function montarDadosRecibo(PDO $pdo, array $transaction): array
{
    $settings = [];
    try {
        $settings = $pdo->query('SELECT company_name FROM settings LIMIT 1')->fetch() ?: [];
    } catch (Throwable $e) {
        $settings = [];
    }

    return [
        'receipt_no' => (string)($transaction['id'] ?? '—'),
        'data_realizacao' => (string)($transaction['data_realizacao'] ?? date('Y-m-d')),
        'data_pagamento' => (string)($transaction['data_pagamento'] ?? $transaction['data_realizacao'] ?? date('Y-m-d')),
        'valor_total' => (int)($transaction['valor_total'] ?? 0),
        'imposto_retido' => (int)($transaction['taxa_gensen_paga'] ?? 0),
        'valor_liquido_medium' => (int)($transaction['valor_liquido_medium'] ?? 0),
        'destinatario' => (string)($transaction['cliente_nome'] ?? $transaction['medium_name'] ?? '________________________________'),
        'cliente_nome' => (string)($transaction['cliente_nome'] ?? ''),
        'cliente_telefone' => (string)($transaction['cliente_telefone'] ?? ''),
        'descricao_jp' => (string)($transaction['descricao_servico'] ?: '宗教儀式提供料として'),
        'descricao_pt' => 'Referente a serviços de cerimônia religiosa',
        'npo_nome' => (string)($settings['company_name'] ?? 'CRM Terreiro'),
        'npo_endereco' => 'Tsu, Mie, Japão',
        'medium_name' => (string)($transaction['medium_name'] ?? '—'),
        'tata_name' => (string)($transaction['tata_name'] ?? '—'),
    ];
}

// ─── Sync mensalidades mensais → caixa_movimentos ─────────────────────────

function ensureMonthlyMensalidades(PDO $pdo, string $monthStart): void
{
    $stmt = $pdo->query(
        "SELECT id, name, mensalidade_value, due_day
         FROM filhos
         WHERE COALESCE(status, 'ativo') = 'ativo'
           AND isento_mensalidade = 0
         ORDER BY id ASC"
    );
    $filhos = $stmt->fetchAll();

    foreach ($filhos as $filho) {
        $day     = str_pad((string)(int)$filho['due_day'], 2, '0', STR_PAD_LEFT);
        $dueDate = (new DateTime($monthStart))->format("Y-m-{$day}");
        $pdo->prepare(
            "INSERT INTO caixa_movimentos
                (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
             VALUES ('entrada', 'mensalidade', ?, ?, ?, ?, 'previsto', ?)
             ON DUPLICATE KEY UPDATE
                valor = VALUES(valor), data_movimento = VALUES(data_movimento)"
        )->execute([
            (int)$filho['id'],
            $monthStart,
            $dueDate,
            (int)$filho['mensalidade_value'],
            'Mensalidade — ' . $filho['name'],
        ]);
    }

    $paidStmt = $pdo->prepare(
        "SELECT filho_id FROM mensalidades_pagas WHERE paid_month = ?"
    );
    $paidStmt->execute([$monthStart]);
    $paidIds = array_map('intval', array_column($paidStmt->fetchAll(), 'filho_id'));

    if ($paidIds) {
        $in = implode(',', array_fill(0, count($paidIds), '?'));
        $pdo->prepare(
            "UPDATE caixa_movimentos SET status = 'realizado'
             WHERE origem = 'mensalidade' AND mes = ? AND referencia_id IN ($in)"
        )->execute(array_merge([$monthStart], $paidIds));
    }
}

// ─── Main ──────────────────────────────────────────────────────────────────

try {
    $pdo = db();

    // ── DASHBOARD ──────────────────────────────────────────────────────────
    if ($action === 'dashboard') {
        $mes      = $_GET['mes'] ?? date('Y-m');
        $mesStart = $mes . '-01';

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM entradas
             WHERE DATE_FORMAT(data_entrada, '%Y-%m') = ?"
        );
        $stmt->execute([$mes]);
        $totalEntradas = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM contas_pagar
             WHERE status = 'Pago' AND DATE_FORMAT(data_pagamento, '%Y-%m') = ?"
        );
        $stmt->execute([$mes]);
        $totalSaidas = (int)$stmt->fetchColumn();

        $totalCreditoCasa = (int)$pdo
            ->query("SELECT COALESCE(SUM(valor_credito), 0) FROM credito_casa")
            ->fetchColumn();

        $row = $pdo
            ->query("SELECT COUNT(*), COALESCE(SUM(valor), 0) FROM contas_pagar WHERE status = 'Pendente'")
            ->fetch(PDO::FETCH_NUM);
        $contasPendentesQtd   = (int)$row[0];
        $contasPendentesValor = (int)$row[1];

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM caixa_movimentos
             WHERE origem = 'mensalidade' AND mes = ? AND tipo = 'entrada'"
        );
        $stmt->execute([$mesStart]);
        $totalMens = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM caixa_movimentos
             WHERE origem = 'mensalidade' AND mes = ? AND tipo = 'entrada' AND status = 'realizado'"
        );
        $stmt->execute([$mesStart]);
        $mensPagas = (int)$stmt->fetchColumn();

        jsonResponse(['ok' => true, 'data' => [
            'total_entradas'         => $totalEntradas,
            'total_saidas'           => $totalSaidas,
            'saldo'                  => $totalEntradas - $totalSaidas,
            'total_credito_casa'     => $totalCreditoCasa,
            'contas_pendentes_qtd'   => $contasPendentesQtd,
            'contas_pendentes_valor' => $contasPendentesValor,
            'mensalidades_total'     => $totalMens,
            'mensalidades_pagas'     => $mensPagas,
            'mensalidades_pendentes' => $totalMens - $mensPagas,
        ]]);
    }

    // ── SPLIT FINANCEIRO / GENSEN ────────────────────────────────────────
    if ($action === 'get_medium_config') {
        $targetUserId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? $_apiUserId);
        if ($targetUserId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Usuário inválido'], 422);
        }
        if ($_apiUserRole !== 'admin' && $targetUserId !== $_apiUserId) {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $config = obterMediumConfig($pdo, $targetUserId);
        jsonResponse([
            'ok' => true,
            'data' => $config,
            'split_preview' => calcularSplitTrabalho((int)($_GET['valor_preview'] ?? 100000), $config),
        ]);
    }

    if ($action === 'save_medium_config') {
        $targetUserId = (int)($_POST['user_id'] ?? $_apiUserId);
        if ($targetUserId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Usuário inválido'], 422);
        }
        if ($_apiUserRole !== 'admin' && $targetUserId !== $_apiUserId) {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $config = salvarMediumConfig($pdo, $targetUserId, $_POST);
        jsonResponse([
            'ok' => true,
            'data' => $config,
            'split_preview' => calcularSplitTrabalho((int)($_POST['valor_preview'] ?? 100000), $config),
        ]);
    }

    if ($action === 'list_financial_users') {
        $stmt = $pdo->query(
            "SELECT id, name, phone, role
             FROM users
             WHERE is_active = 1
             ORDER BY name ASC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'list_financial_transactions') {
        if ($_apiUserRole === 'admin') {
            $stmt = $pdo->query(
                "SELECT ft.*, m.name AS medium_name, t.name AS tata_name
                 FROM financial_transactions ft
                 LEFT JOIN users m ON m.id = ft.medium_id
                 LEFT JOIN users t ON t.id = ft.tata_id
                 ORDER BY COALESCE(ft.data_pagamento, ft.data_realizacao) DESC, ft.id DESC"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT ft.*, m.name AS medium_name, t.name AS tata_name
                 FROM financial_transactions ft
                 LEFT JOIN users m ON m.id = ft.medium_id
                 LEFT JOIN users t ON t.id = ft.tata_id
                 WHERE ft.medium_id = ?
                 ORDER BY COALESCE(ft.data_pagamento, ft.data_realizacao) DESC, ft.id DESC"
            );
            $stmt->execute([$_apiUserId]);
        }

        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'list_admin_payables') {
        if ($_apiUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso restrito a admin'], 403);
        }

        $stmt = $pdo->query(
            "SELECT m.id AS medium_id, m.name AS medium_name, m.phone AS medium_phone,
                    COUNT(ft.id) AS total_transacoes,
                    SUM(ft.valor_total) AS valor_total_realizado,
                    SUM(ft.taxa_gensen_paga) AS imposto_total,
                    SUM(ft.valor_liquido_medium) AS valor_liquido_medium,
                    SUM(CASE WHEN ft.status_pagamento = 'pago' THEN ft.valor_liquido_medium ELSE 0 END) AS valor_pago,
                    SUM(CASE WHEN ft.status_pagamento != 'pago' THEN ft.valor_liquido_medium ELSE 0 END) AS valor_pendente,
                    MAX(COALESCE(ft.data_pagamento, ft.data_realizacao)) AS ultima_transacao
             FROM users m
             LEFT JOIN financial_transactions ft ON ft.medium_id = m.id
             WHERE m.is_active = 1
             GROUP BY m.id
             ORDER BY m.name ASC"
        );

        $payables = $stmt->fetchAll();

        $totalRealizado = 0;
        $totalImposto = 0;
        $totalLiquido = 0;
        $totalPago = 0;
        $totalPendente = 0;

        foreach ($payables as $p) {
            $totalRealizado += (int)($p['valor_total_realizado'] ?? 0);
            $totalImposto += (int)($p['imposto_total'] ?? 0);
            $totalLiquido += (int)($p['valor_liquido_medium'] ?? 0);
            $totalPago += (int)($p['valor_pago'] ?? 0);
            $totalPendente += (int)($p['valor_pendente'] ?? 0);
        }

        jsonResponse([
            'ok' => true,
            'data' => $payables,
            'totals' => [
                'valor_total_realizado' => $totalRealizado,
                'imposto_total' => $totalImposto,
                'valor_liquido_total' => $totalLiquido,
                'valor_pago_total' => $totalPago,
                'valor_pendente_total' => $totalPendente,
            ],
        ]);
    }

    if ($action === 'registrar_split_trabalho') {
        $mediumId = (int)($_POST['medium_id'] ?? $_apiUserId);
        $tataId = ((int)($_POST['tata_id'] ?? 0)) ?: null;
        $clienteNome = trim((string)($_POST['cliente_nome'] ?? '')) ?: null;
        $clienteTelefone = trim((string)($_POST['cliente_telefone'] ?? '')) ?: null;
        $descricaoServico = trim((string)($_POST['descricao_servico'] ?? '')) ?: '宗教儀式提供料として';
        $valorTotal = (int)($_POST['valor_total'] ?? 0);
        $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d');
        $dataPagamento = trim((string)($_POST['data_pagamento'] ?? '')) ?: $dataRealizacao;
        $statusPagamento = validarStatusFinanceiro($_POST['status_pagamento'] ?? 'pendente');

        if ($mediumId <= 0 || $valorTotal <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Medium e valor total são obrigatórios'], 422);
        }
        if ($_apiUserRole !== 'admin' && $mediumId !== $_apiUserId) {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $config = obterMediumConfig($pdo, $mediumId);
        $split = calcularSplitTrabalho($valorTotal, $config);

        $pdo->prepare(
            'INSERT INTO financial_transactions
                (medium_id, tata_id, cliente_nome, cliente_telefone, descricao_servico, valor_total, taxa_gensen_paga, valor_liquido_medium, valor_liquido_tata, status_pagamento, data_realizacao, data_pagamento)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $mediumId,
            $tataId,
            $clienteNome,
            $clienteTelefone,
            $descricaoServico,
            $split['valor_total'],
            $split['impostos']['total_retido'],
            $split['liquidos']['executor'],
            $split['liquidos']['tata'],
            $statusPagamento,
            $dataRealizacao,
            $dataPagamento,
        ]);

        $newId = (int)$pdo->lastInsertId();
        $receiptPath = null;
        try {
            $transaction = obterFinancialTransaction($pdo, $newId);
            if ($transaction) {
                $saved = financialReceiptSavePdf(montarDadosRecibo($pdo, $transaction), dirname(__DIR__));
                $receiptPath = $saved['relative_path'];
                $pdo->prepare('UPDATE financial_transactions SET receipt_path = ? WHERE id = ?')
                    ->execute([$receiptPath, $newId]);
            }
        } catch (Throwable $e) {
            error_log('[financial_transactions receipt] ' . $e->getMessage());
        }

        jsonResponse([
            'ok' => true,
            'id' => $newId,
            'data' => $split,
            'receipt_path' => $receiptPath,
            'receipt_view_url' => 'ryoushuusho.php?id=' . $newId,
        ]);
    }

    if ($action === 'generate_receipt') {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }

        $transaction = obterFinancialTransaction($pdo, $id);
        if (!$transaction) {
            jsonResponse(['ok' => false, 'message' => 'Transação não encontrada'], 404);
        }
        if ($_apiUserRole !== 'admin' && (int)$transaction['medium_id'] !== $_apiUserId) {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $receiptData = montarDadosRecibo($pdo, $transaction);
        $saved = financialReceiptSavePdf($receiptData, dirname(__DIR__));
        $pdo->prepare('UPDATE financial_transactions SET receipt_path = ? WHERE id = ?')
            ->execute([$saved['relative_path'], $id]);

        jsonResponse([
            'ok' => true,
            'path' => $saved['relative_path'],
            'url' => $saved['relative_path'],
            'view_url' => 'ryoushuusho.php?id=' . $id,
            'filename' => $saved['filename'],
        ]);
    }

    if ($action === 'update_financial_status') {
        $id = (int)($_POST['id'] ?? 0);
        $statusPagamento = validarStatusFinanceiro($_POST['status_pagamento'] ?? 'pendente');
        $dataPagamentoInput = trim((string)($_POST['data_pagamento'] ?? ''));
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }

        $transaction = obterFinancialTransaction($pdo, $id);
        if (!$transaction) {
            jsonResponse(['ok' => false, 'message' => 'Transação não encontrada'], 404);
        }
        if ($_apiUserRole !== 'admin' && (int)$transaction['medium_id'] !== $_apiUserId) {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $dataPagamento = $transaction['data_pagamento'] ?? null;
        $receiptPath = $transaction['receipt_path'] ?? null;
        if ($statusPagamento === 'pago') {
            $dataPagamento = $dataPagamentoInput !== ''
                ? $dataPagamentoInput
                : ((string)($transaction['data_pagamento'] ?? '') !== '' ? $transaction['data_pagamento'] : date('Y-m-d'));
        }

        $pdo->prepare('UPDATE financial_transactions SET status_pagamento = ?, data_pagamento = ? WHERE id = ?')
            ->execute([$statusPagamento, $dataPagamento, $id]);

        if ($statusPagamento === 'pago') {
            $transaction['status_pagamento'] = $statusPagamento;
            $transaction['data_pagamento'] = $dataPagamento;
            $saved = financialReceiptSavePdf(montarDadosRecibo($pdo, $transaction), dirname(__DIR__));
            $receiptPath = $saved['relative_path'];
            $pdo->prepare('UPDATE financial_transactions SET receipt_path = ? WHERE id = ?')
                ->execute([$receiptPath, $id]);
        }

        jsonResponse([
            'ok' => true,
            'id' => $id,
            'status_pagamento' => $statusPagamento,
            'data_pagamento' => $dataPagamento,
            'receipt_path' => $receiptPath,
            'view_url' => 'ryoushuusho.php?id=' . $id,
            'receipt_regenerated' => $statusPagamento === 'pago',
        ]);
    }

    // ── CONTAS A PAGAR ─────────────────────────────────────────────────────
    // Carry over: marca contas vencidas e não-pagas como 'Vencido'
    $pdo->exec(
        "UPDATE contas_pagar SET status = 'Vencido'
         WHERE status = 'Pendente' AND data_vencimento < CURDATE()"
    );

    if ($action === 'list_contas') {
        $stmt = $pdo->query(
            "SELECT id, descricao, categoria, valor, data_vencimento, status,
                    data_pagamento, fornecedor, recorrencia,
                    parcela_num, parcela_total, parcela_grupo_id, valor_pago, mes_referencia
             FROM contas_pagar
             ORDER BY FIELD(status,'Pendente','Vencido','Pago'), data_vencimento ASC, id DESC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create_conta') {
        $descricao   = requireField('descricao', 'Descrição obrigatória');
        $valor       = (int)($_POST['valor'] ?? 0);
        $dataVenc    = $_POST['data_vencimento'] ?? date('Y-m-d');
        $categoria   = trim($_POST['categoria'] ?? '') ?: null;
        $fornecedor  = trim($_POST['fornecedor'] ?? '') ?: null;
        $recorrencia = trim($_POST['recorrencia'] ?? 'nenhuma');
        $parcelaNum  = (int)($_POST['parcela_num'] ?? 0);
        $parcelaTotal= (int)($_POST['parcela_total'] ?? 0);

        if ($parcelaTotal > 1 && $parcelaNum === 0) {
            // Cria todas as parcelas de uma vez
            $grupoId = date('YmdHis') . rand(100, 999);
            $valorParcela = (int)round($valor / $parcelaTotal);
            for ($i = 1; $i <= $parcelaTotal; $i++) {
                $dt = (new DateTime($dataVenc))->modify('+' . ($i - 1) . ' months')->format('Y-m-d');
                $mesRef = substr($dt, 0, 7);
                $pdo->prepare(
                    'INSERT INTO contas_pagar (descricao, valor, categoria, data_vencimento, fornecedor, recorrencia, parcela_num, parcela_total, parcela_grupo_id, mes_referencia)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([$descricao, $valorParcela, $categoria, $dt, $fornecedor, $recorrencia, $i, $parcelaTotal, $grupoId, $mesRef]);
            }
            jsonResponse(['ok' => true, 'parcelas' => $parcelaTotal]);
        } else {
            $mesRef = substr($dataVenc, 0, 7);
            $pdo->prepare(
                'INSERT INTO contas_pagar (descricao, valor, categoria, data_vencimento, fornecedor, recorrencia, parcela_num, parcela_total, mes_referencia)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$descricao, $valor, $categoria, $dataVenc, $fornecedor, $recorrencia, $parcelaNum ?: null, $parcelaTotal ?: null, $mesRef]);

            $contaId = (int)$pdo->lastInsertId();

            // Gerar recorrência futura (até 12 meses) se não for parcela
            if ($recorrencia !== 'nenhuma' && $parcelaTotal <= 1) {
                $meses = ['mensal' => 1, 'bimestral' => 2, 'trimestral' => 3, 'semestral' => 6, 'anual' => 12];
                $step = $meses[$recorrencia] ?? 0;
                if ($step > 0) {
                    for ($m = $step; $m <= 12; $m += $step) {
                        $dt = (new DateTime($dataVenc))->modify("+{$m} months")->format('Y-m-d');
                        $mesRef2 = substr($dt, 0, 7);
                        $pdo->prepare(
                            'INSERT INTO contas_pagar (descricao, valor, categoria, data_vencimento, fornecedor, recorrencia, mes_referencia)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        )->execute([$descricao, $valor, $categoria, $dt, $fornecedor, $recorrencia, $mesRef2]);
                    }
                }
            }

            jsonResponse(['ok' => true, 'id' => $contaId]);
        }
    }

    if ($action === 'update_conta') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $descricao   = requireField('descricao', 'Descrição obrigatória');
        $valor       = (int)($_POST['valor'] ?? 0);
        $dataVenc    = $_POST['data_vencimento'] ?? date('Y-m-d');
        $categoria   = trim($_POST['categoria'] ?? '') ?: null;
        $fornecedor  = trim($_POST['fornecedor'] ?? '') ?: null;
        $recorrencia = trim($_POST['recorrencia'] ?? 'nenhuma');
        $parcelaNum  = (int)($_POST['parcela_num'] ?? 0) ?: null;
        $parcelaTotal= (int)($_POST['parcela_total'] ?? 0) ?: null;
        $pdo->prepare(
            'UPDATE contas_pagar SET descricao=?, valor=?, categoria=?, data_vencimento=?, fornecedor=?, recorrencia=?, parcela_num=?, parcela_total=? WHERE id=?'
        )->execute([$descricao, $valor, $categoria, $dataVenc, $fornecedor, $recorrencia, $parcelaNum, $parcelaTotal, $id]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete_conta') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $pdo->prepare('DELETE FROM contas_pagar WHERE id=?')->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'pay_conta') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $stmt = $pdo->prepare('SELECT * FROM contas_pagar WHERE id = ?');
        $stmt->execute([$id]);
        $conta = $stmt->fetch();
        if (!$conta) jsonResponse(['ok' => false, 'message' => 'Conta não encontrada'], 404);

        $today    = date('Y-m-d');
        $mesStart = date('Y-m-01');
        $valorPago = isset($_POST['valor_pago']) && $_POST['valor_pago'] !== ''
            ? (int)$_POST['valor_pago']
            : (int)$conta['valor'];
        $valorTotal = (int)$conta['valor'];
        $jaAcumulado = (int)($conta['valor_pago'] ?? 0);
        $novoAcumulado = $jaAcumulado + $valorPago;

        if ($novoAcumulado >= $valorTotal) {
            // Pago totalmente
            $pdo->prepare('UPDATE contas_pagar SET status="Pago", data_pagamento=?, valor_pago=? WHERE id=?')
                ->execute([$today, $valorTotal, $id]);
        } else {
            // Pagamento parcial — registra o acumulado
            $pdo->prepare('UPDATE contas_pagar SET valor_pago=? WHERE id=?')
                ->execute([$novoAcumulado, $id]);
        }

        // Registra no caixa
        $pdo->prepare(
            "INSERT INTO caixa_movimentos
                (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
             VALUES ('saida', 'conta_pagar', ?, ?, ?, ?, 'realizado', ?)
             ON DUPLICATE KEY UPDATE
                valor=VALUES(valor), data_movimento=VALUES(data_movimento), status='realizado'"
        )->execute([$id, $mesStart, $today, $valorPago, 'Conta — ' . $conta['descricao']]);
        jsonResponse(['ok' => true]);
    }

    // Carry-over: contas vencidas não-pagas movem para o próximo mês
    if ($action === 'carry_over') {
        $stmt = $pdo->query(
            "SELECT id, descricao, valor, categoria, fornecedor, recorrencia, parcela_num, parcela_total, parcela_grupo_id, valor_pago
             FROM contas_pagar
             WHERE status = 'Vencido'"
        );
        $vencidas = $stmt->fetchAll();
        $carried = 0;
        foreach ($vencidas as $c) {
            $saldo = (int)$c['valor'] - (int)($c['valor_pago'] ?? 0);
            if ($saldo <= 0) continue;
            $novaData = (new DateTime())->modify('first day of next month')->format('Y-m-d');
            $mesRef = substr($novaData, 0, 7);
            $pdo->prepare(
                'INSERT INTO contas_pagar (descricao, valor, categoria, fornecedor, recorrencia, parcela_num, parcela_total, parcela_grupo_id, mes_referencia, data_vencimento)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $c['descricao'] . ' (carry-over)',
                $saldo, $c['categoria'], $c['fornecedor'], $c['recorrencia'],
                $c['parcela_num'], $c['parcela_total'], $c['parcela_grupo_id'], $mesRef, $novaData
            ]);
            // marca a original como pago/transferido
            $pdo->prepare("UPDATE contas_pagar SET status='Pago', data_pagamento=CURDATE() WHERE id=?")->execute([$c['id']]);
            $carried++;
        }
        jsonResponse(['ok' => true, 'carried' => $carried]);
    }

    // ── CATEGORIAS DE CONTA ───────────────────────────────────────────────
    if ($action === 'list_categorias') {
        $stmt = $pdo->query("SELECT id, nome FROM categorias_conta ORDER BY nome ASC");
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create_categoria') {
        $nome = trim($_POST['nome'] ?? '');
        if (!$nome) jsonResponse(['ok' => false, 'message' => 'Nome obrigatório'], 422);
        $check = $pdo->prepare("SELECT id FROM categorias_conta WHERE nome = ?");
        $check->execute([$nome]);
        if ($check->fetch()) jsonResponse(['ok' => false, 'message' => 'Categoria já existe'], 422);
        $pdo->prepare('INSERT INTO categorias_conta (nome) VALUES (?)')->execute([$nome]);
        jsonResponse(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'delete_categoria') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $pdo->prepare('DELETE FROM categorias_conta WHERE id=?')->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    // ── ENTRADAS ───────────────────────────────────────────────────────────
    if ($action === 'list_entradas') {
        $stmt = $pdo->query(
            "SELECT id, descricao, valor, origem, data_entrada
             FROM entradas
             ORDER BY data_entrada DESC, id DESC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create_entrada') {
        $descricao   = requireField('descricao', 'Descrição obrigatória');
        $valor       = (int)($_POST['valor'] ?? 0);
        $origem      = $_POST['origem'] ?? 'manual';
        $dataEntrada = $_POST['data_entrada'] ?? date('Y-m-d');

        $pdo->prepare(
            'INSERT INTO entradas (descricao, valor, origem, data_entrada) VALUES (?, ?, ?, ?)'
        )->execute([$descricao, $valor, $origem, $dataEntrada]);
        $entradaId = (int)$pdo->lastInsertId();

        $credito = calcularCreditoCasa($valor);
        if ($credito > 0) {
            $pdo->prepare(
                'INSERT INTO credito_casa
                    (entrada_id, valor_original, percentual, valor_credito, descricao, data)
                 VALUES (?, ?, 10.00, ?, ?, ?)'
            )->execute([$entradaId, $valor, $credito, 'Crédito Casa — ' . $descricao, $dataEntrada]);
        }

        $mesStart = date('Y-m-01', strtotime($dataEntrada));
        $pdo->prepare(
            "INSERT INTO caixa_movimentos
                (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
             VALUES ('entrada', 'entrada', ?, ?, ?, ?, 'realizado', ?)
             ON DUPLICATE KEY UPDATE valor=VALUES(valor)"
        )->execute([$entradaId, $mesStart, $dataEntrada, $valor, $descricao]);

        jsonResponse(['ok' => true, 'id' => $entradaId]);
    }

    if ($action === 'update_entrada') {
        $id          = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $descricao   = requireField('descricao', 'Descrição obrigatória');
        $valor       = (int)($_POST['valor'] ?? 0);
        $origem      = $_POST['origem'] ?? 'manual';
        $dataEntrada = $_POST['data_entrada'] ?? date('Y-m-d');
        $pdo->prepare(
            'UPDATE entradas SET descricao=?, valor=?, origem=?, data_entrada=? WHERE id=?'
        )->execute([$descricao, $valor, $origem, $dataEntrada, $id]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete_entrada') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $pdo->prepare('DELETE FROM entradas WHERE id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM credito_casa WHERE entrada_id=?')->execute([$id]);
        $pdo->prepare(
            "DELETE FROM caixa_movimentos WHERE origem='entrada' AND referencia_id=?"
        )->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    // ── CRÉDITO CASA ───────────────────────────────────────────────────────
    if ($action === 'list_credito_casa') {
        $stmt = $pdo->query(
            "SELECT cc.id, cc.valor_original, cc.percentual, cc.valor_credito,
                    cc.descricao, cc.data,
                    e.descricao AS entrada_descricao
             FROM credito_casa cc
             LEFT JOIN entradas e ON e.id = cc.entrada_id
             ORDER BY cc.data DESC, cc.id DESC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // ── CAIXA (fluxo mensal) ───────────────────────────────────────────────
    if ($action === 'list_caixa') {
        $monthParam = $_GET['month'] ?? date('Y-m-01');
        $monthDate  = new DateTime($monthParam);
        $mesStart   = monthStart($monthDate);

        ensureMonthlyMensalidades($pdo, $mesStart);

        [$start, $end] = monthBounds($mesStart);

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE -valor END), 0)
             FROM caixa_movimentos
             WHERE status='realizado' AND data_movimento < ?"
        );
        $stmt->execute([$start]);
        $saldoInicial = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo='saida'   THEN valor ELSE 0 END), 0) AS saidas
             FROM caixa_movimentos
             WHERE status='realizado' AND data_movimento >= ? AND data_movimento < ?"
        );
        $stmt->execute([$start, $end]);
        $sums     = $stmt->fetch() ?: ['entradas' => 0, 'saidas' => 0];
        $entradas = (int)$sums['entradas'];
        $saidas   = (int)$sums['saidas'];

        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END), 0) AS entradas_previstas,
                COALESCE(SUM(CASE WHEN tipo='saida'   THEN valor ELSE 0 END), 0) AS saidas_previstas
             FROM caixa_movimentos
             WHERE status='previsto' AND data_movimento >= ? AND data_movimento < ?"
        );
        $stmt->execute([$start, $end]);
        $previsto = $stmt->fetch() ?: ['entradas_previstas' => 0, 'saidas_previstas' => 0];

        $stmt = $pdo->prepare(
            "SELECT id, tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao
             FROM caixa_movimentos
             WHERE data_movimento >= ? AND data_movimento < ?
             ORDER BY data_movimento ASC, id ASC"
        );
        $stmt->execute([$start, $end]);

        jsonResponse([
            'ok'      => true,
            'summary' => [
                'saldo_inicial'      => $saldoInicial,
                'entradas'           => $entradas,
                'saidas'             => $saidas,
                'saldo_final'        => $saldoInicial + $entradas - $saidas,
                'entradas_previstas' => (int)$previsto['entradas_previstas'],
                'saidas_previstas'   => (int)$previsto['saidas_previstas'],
            ],
            'data' => $stmt->fetchAll(),
        ]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
