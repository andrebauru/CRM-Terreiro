<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

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
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
