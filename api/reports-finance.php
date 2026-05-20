<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../financeiro_app/config.php';
require_once __DIR__ . '/../financeiro_app/database.py';

$month = $_GET['month'] ?? date('Y-m');
$currentUserRole = $_SESSION['user_role'] ?? 'staff';

try {
    // Validar formato de mês
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        jsonResponse(['ok' => false, 'message' => 'Formato de mês inválido (use YYYY-MM)'], 400);
    }

    // Conectar ao banco SQLite do financeiro
    $dbPath = __DIR__ . '/../financeiro_app/financeiro.db';
    if (!file_exists($dbPath)) {
        jsonResponse(['ok' => false, 'message' => 'Banco de dados de financeiro não encontrado'], 404);
    }

    $sqliteDb = new PDO('sqlite:' . $dbPath);
    $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqliteDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Extrair ano e mês
    [$year, $monthNum] = explode('-', $month);
    $monthStart = "$year-$monthNum-01";
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    // ========== ENTRADAS DO MÊS ==========
    $stmtEntradas = $sqliteDb->prepare(
        "SELECT 
            SUM(CAST(valor AS REAL)) as total
         FROM entradas
         WHERE strftime('%Y-%m', data_entrada) = ?"
    );
    $stmtEntradas->execute([$month]);
    $entradaMes = $stmtEntradas->fetch()['total'] ?? 0;
    $totalEntradas = (float)$entradaMes;

    // ========== SAÍDAS DO MÊS ==========
    $stmtSaidas = $sqliteDb->prepare(
        "SELECT 
            id,
            descricao,
            CAST(valor AS REAL) as valor,
            categoria,
            status
         FROM contas
         WHERE strftime('%Y-%m', data_vencimento) = ?
         ORDER BY data_vencimento DESC"
    );
    $stmtSaidas->execute([$month]);
    $saidas = $stmtSaidas->fetchAll();

    $totalSaidas = 0;
    $saidasDetalhado = [];
    
    foreach ($saidas as $saida) {
        $valor = (float)$saida['valor'];
        $totalSaidas += $valor;
        
        if ($currentUserRole === 'admin') {
            // Administrador vê categoria também
            $saidasDetalhado[] = [
                'id' => (int)$saida['id'],
                'descricao' => $saida['descricao'],
                'valor' => $valor,
                'categoria' => $saida['categoria'] ?? 'Sem categoria',
                'status' => $saida['status'] ?? 'Pendente',
            ];
        } else {
            // Usuário comum vê apenas descricao e valor
            $saidasDetalhado[] = [
                'descricao' => $saida['descricao'],
                'valor' => $valor,
            ];
        }
    }

    // ========== SALDO ACUMULADO DE MESES ANTERIORES ==========
    $stmtAcumulado = $sqliteDb->prepare(
        "SELECT
            CAST(SUM(CASE WHEN tipo = 'entrada' OR tabela = 'entradas' THEN valor ELSE -valor END) AS REAL) as saldo
         FROM (
            SELECT 'entrada' as tipo, CAST(valor AS REAL) as valor
            FROM entradas
            WHERE strftime('%Y-%m', data_entrada) < ?
            
            UNION ALL
            
            SELECT 'saida', CAST(valor AS REAL)
            FROM contas
            WHERE strftime('%Y-%m', data_vencimento) < ? AND status = 'Pendente'
         ) combined"
    );
    $stmtAcumulado->execute([$month, $month]);
    $saldoAnterior = (float)($stmtAcumulado->fetch()['saldo'] ?? 0);

    // ========== CÁLCULOS FINAIS ==========
    $subtotalMes = $totalEntradas - $totalSaidas;
    $saldoFinal = $saldoAnterior + $subtotalMes;

    // Determinar cor (vermelho se negativo, verde se positivo)
    $corSubtotal = $saldoFinal < 0 ? 'text-red-600' : 'text-green-600';
    $fundoSubtotal = $saldoFinal < 0 ? 'bg-red-50' : 'bg-green-50';

    // ========== RESPOSTA ==========
    $response = [
        'ok' => true,
        'mes' => $month,
        'periodo' => [
            'inicio' => $monthStart,
            'fim' => $monthEnd,
        ],
        'entradas' => [
            'total' => round($totalEntradas, 2),
            'visivel' => $currentUserRole === 'admin' ? 'total_com_origem' : 'apenas_total',
        ],
        'saidas' => [
            'total' => round($totalSaidas, 2),
            'detalhado' => $saidasDetalhado,
        ],
        'acumulado' => [
            'saldo_anterior' => round($saldoAnterior, 2),
            'observacao' => 'Saldo acumulado de meses anteriores',
        ],
        'subtotal_mes' => [
            'valor' => round($subtotalMes, 2),
            'cor' => $corSubtotal,
            'fundo' => $fundoSubtotal,
        ],
        'saldo_final' => [
            'valor' => round($saldoFinal, 2),
            'cor' => $corSubtotal,
            'fundo' => $fundoSubtotal,
            'status' => $saldoFinal >= 0 ? 'positivo' : 'negativo',
        ],
    ];

    jsonResponse($response);

} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => 'Erro ao buscar relatório: ' . $e->getMessage()], 500);
}
