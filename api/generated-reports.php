<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../financeiro_app/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = $_SESSION['user_role'] ?? 'staff';

try {
    $pdo = db();

    // ========== ACTION: get (Buscar relatório gerado) ==========
    if ($action === 'get') {
        $month = $_GET['month'] ?? date('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            jsonResponse(['ok' => false, 'message' => 'Formato de mês inválido'], 400);
        }

        // Buscar relatório gerado
        $stmt = $pdo->prepare(
            "SELECT 
                id,
                mes_referencia,
                total_entradas,
                total_saidas,
                saldo_anterior,
                subtotal_mes,
                saldo_final,
                dados_detalhados,
                gerado_por,
                gerado_em
             FROM relatorios_gerados
             WHERE mes_referencia = ?"
        );
        $stmt->execute([$month]);
        $relatorio = $stmt->fetch();

        if (!$relatorio) {
            jsonResponse(['ok' => false, 'message' => 'Nenhum relatório gerado para este período', 'empty' => true], 404);
        }

        $dados = json_decode($relatorio['dados_detalhados'], true) ?? [];

        jsonResponse([
            'ok' => true,
            'relatorio' => [
                'mes' => $relatorio['mes_referencia'],
                'entradas' => ['total' => (float)$relatorio['total_entradas']],
                'saidas' => $dados['saidas'] ?? [],
                'acumulado' => ['saldo_anterior' => (float)$relatorio['saldo_anterior']],
                'subtotal_mes' => [
                    'valor' => (float)$relatorio['subtotal_mes'],
                    'cor' => (float)$relatorio['subtotal_mes'] < 0 ? 'text-red-600' : 'text-green-600',
                    'fundo' => (float)$relatorio['subtotal_mes'] < 0 ? 'bg-red-50' : 'bg-green-50',
                ],
                'saldo_final' => [
                    'valor' => (float)$relatorio['saldo_final'],
                    'cor' => (float)$relatorio['saldo_final'] < 0 ? 'text-red-600' : 'text-green-600',
                    'fundo' => (float)$relatorio['saldo_final'] < 0 ? 'bg-red-50' : 'bg-green-50',
                    'status' => (float)$relatorio['saldo_final'] >= 0 ? 'positivo' : 'negativo',
                ],
            ],
            'gerado_em' => $relatorio['gerado_em'],
        ]);
    }

    // ========== ACTION: generate (Admin gera novo relatório) ==========
    if ($action === 'generate') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }

        $month = requireField('month', 'Mês obrigatório');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            jsonResponse(['ok' => false, 'message' => 'Formato de mês inválido'], 400);
        }

        // Conectar ao SQLite do financeiro
        $dbPath = __DIR__ . '/../financeiro_app/financeiro.db';
        if (!file_exists($dbPath)) {
            jsonResponse(['ok' => false, 'message' => 'Banco de dados de financeiro não encontrado'], 404);
        }

        $sqliteDb = new PDO('sqlite:' . $dbPath);
        $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sqliteDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        [$year, $monthNum] = explode('-', $month);
        $monthStart = "$year-$monthNum-01";
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Entradas
        $stmtEntradas = $sqliteDb->prepare(
            "SELECT SUM(CAST(valor AS REAL)) as total FROM entradas WHERE strftime('%Y-%m', data_entrada) = ?"
        );
        $stmtEntradas->execute([$month]);
        $totalEntradas = (float)($stmtEntradas->fetch()['total'] ?? 0);

        // Saídas
        $stmtSaidas = $sqliteDb->prepare(
            "SELECT id, descricao, CAST(valor AS REAL) as valor, categoria, status
             FROM contas WHERE strftime('%Y-%m', data_vencimento) = ?
             ORDER BY data_vencimento DESC"
        );
        $stmtSaidas->execute([$month]);
        $saidas = $stmtSaidas->fetchAll();

        $totalSaidas = 0;
        $saidasDetalhado = [];

        foreach ($saidas as $saida) {
            $valor = (float)$saida['valor'];
            $totalSaidas += $valor;
            $saidasDetalhado[] = [
                'descricao' => $saida['descricao'],
                'valor' => $valor,
                'categoria' => $saida['categoria'],
            ];
        }

        // Saldo anterior
        $stmtAcumulado = $sqliteDb->prepare(
            "SELECT CAST(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END) AS REAL) as saldo
             FROM (
                SELECT 'entrada' as tipo, CAST(valor AS REAL) as valor FROM entradas WHERE strftime('%Y-%m', data_entrada) < ?
                UNION ALL
                SELECT 'saida', CAST(valor AS REAL) FROM contas WHERE strftime('%Y-%m', data_vencimento) < ? AND status = 'Pendente'
             ) combined"
        );
        $stmtAcumulado->execute([$month, $month]);
        $saldoAnterior = (float)($stmtAcumulado->fetch()['saldo'] ?? 0);

        // Cálculos finais
        $subtotalMes = $totalEntradas - $totalSaidas;
        $saldoFinal = $saldoAnterior + $subtotalMes;

        $pdo->beginTransaction();

        try {
            // Salvar ou atualizar relatório
            $stmtCheck = $pdo->prepare("SELECT id FROM relatorios_gerados WHERE mes_referencia = ?");
            $stmtCheck->execute([$month]);
            $existing = $stmtCheck->fetch();

            $dadosDetalhados = json_encode([
                'saidas' => [
                    'total' => $totalSaidas,
                    'detalhado' => $saidasDetalhado,
                ],
                'periodo_inicio' => $monthStart,
                'periodo_fim' => $monthEnd,
            ]);

            if ($existing) {
                // Atualizar
                $stmt = $pdo->prepare(
                    "UPDATE relatorios_gerados 
                     SET total_entradas = ?, total_saidas = ?, saldo_anterior = ?, 
                         subtotal_mes = ?, saldo_final = ?, dados_detalhados = ?, gerado_por = ?
                     WHERE mes_referencia = ?"
                );
                $stmt->execute([
                    $totalEntradas, $totalSaidas, $saldoAnterior,
                    $subtotalMes, $saldoFinal, $dadosDetalhados, $currentUserId, $month
                ]);
            } else {
                // Inserir novo
                $stmt = $pdo->prepare(
                    "INSERT INTO relatorios_gerados 
                     (mes_referencia, total_entradas, total_saidas, saldo_anterior, subtotal_mes, saldo_final, dados_detalhados, gerado_por)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $month, $totalEntradas, $totalSaidas, $saldoAnterior,
                    $subtotalMes, $saldoFinal, $dadosDetalhados, $currentUserId
                ]);
            }

            $pdo->commit();

            jsonResponse([
                'ok' => true,
                'message' => 'Relatório gerado com sucesso',
                'relatorio' => [
                    'mes' => $month,
                    'total_entradas' => $totalEntradas,
                    'total_saidas' => $totalSaidas,
                    'saldo_anterior' => $saldoAnterior,
                    'subtotal_mes' => $subtotalMes,
                    'saldo_final' => $saldoFinal,
                ],
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (\Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['ok' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
}
