<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? 'dashboard';

function buildCalendarEvents(PDO $pdo, string $monthStart, string $monthEnd): array
{
    $events = [];

    $filhosStmt = $pdo->prepare(
        "SELECT id, name, due_day FROM filhos WHERE COALESCE(status, 'ativo') = 'ativo' AND isento_mensalidade = 0"
    );
    $filhosStmt->execute();
    foreach ($filhosStmt->fetchAll() as $filho) {
        $dueDate = new DateTime((new DateTime($monthStart))->format('Y-m-') . str_pad((string)$filho['due_day'], 2, '0', STR_PAD_LEFT));
        $events[] = [
            'title' => 'Mensalidade - ' . $filho['name'],
            'date' => $dueDate->format('Y-m-d'),
            'type' => 'mensalidade',
        ];
    }

    $lancStmt = $pdo->prepare(
        "SELECT f.name, l.data_vencimento, l.descricao
         FROM mensalidades_lancamentos l
         JOIN filhos f ON f.id = l.filho_id
         WHERE l.data_vencimento BETWEEN ? AND ?"
    );
    $lancStmt->execute([$monthStart, $monthEnd]);
    foreach ($lancStmt->fetchAll() as $lanc) {
        $events[] = [
            'title' => 'Mensalidade extra - ' . $lanc['name'],
            'date' => $lanc['data_vencimento'],
            'type' => 'mensalidade',
            'description' => $lanc['descricao'],
        ];
    }

    $trabStmt = $pdo->prepare(
        "SELECT r.data_realizacao, r.status, r.cliente_nome, t.name
         FROM trabalho_realizacoes r
         JOIN trabalhos t ON t.id = r.trabalho_id
         WHERE r.data_realizacao BETWEEN ? AND ?"
    );
    $trabStmt->execute([$monthStart, $monthEnd]);
    foreach ($trabStmt->fetchAll() as $trab) {
        $events[] = [
            'title' => 'Trabalho - ' . $trab['name'] . ($trab['cliente_nome'] ? ' (' . $trab['cliente_nome'] . ')' : ''),
            'date' => $trab['data_realizacao'],
            'type' => 'trabalho',
            'status' => $trab['status'],
        ];
    }

    // Contas a pagar no calendário
    $contasStmt = $pdo->prepare(
        "SELECT id, descricao, valor, data_vencimento, status, fornecedor, categoria
         FROM contas_pagar
         WHERE data_vencimento BETWEEN ? AND ?"
    );
    $contasStmt->execute([$monthStart, $monthEnd]);
    foreach ($contasStmt->fetchAll() as $conta) {
        $titulo = 'Conta - ' . $conta['descricao'];
        if ($conta['fornecedor']) $titulo .= ' (' . $conta['fornecedor'] . ')';
        $events[] = [
            'title' => $titulo,
            'date' => $conta['data_vencimento'],
            'type' => 'conta_pagar',
            'status' => $conta['status'],
            'categoria' => $conta['categoria'],
        ];
    }

    return $events;
}

try {
    $pdo = db();

    $counts = [
        'clients' => (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
        'services' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'attendances' => (int) $pdo->query('SELECT COUNT(*) FROM attendances')->fetchColumn(),
        'receivable_month' => 0,
        'cash_month' => 0,
        'payable_month' => 0,
    ];

    $monthStart = (new DateTime('first day of this month'))->format('Y-m-d');
    $monthEnd = (new DateTime('last day of this month'))->format('Y-m-d');

    if ($action === 'calendar') {
        $monthParam = $_GET['month'] ?? $monthStart;
        $monthDate = new DateTime($monthParam);
        $calendarStart = $monthDate->format('Y-m-01');
        $calendarEnd = (new DateTime($calendarStart))->modify('last day of this month')->format('Y-m-d');
        jsonResponse([
            'ok' => true,
            'calendar_events' => buildCalendarEvents($pdo, $calendarStart, $calendarEnd),
        ]);
    }

    $receivableStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM attendance_installments WHERE due_date BETWEEN ? AND ?');
    $receivableStmt->execute([$monthStart, $monthEnd]);
        $counts['receivable_month'] = (int)$receivableStmt->fetchColumn();

        $monthStart = (new DateTime('first day of this month'))->format('Y-m-01');
        $mensalidadesStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(f.mensalidade_value),0)
                 FROM filhos f
                 WHERE f.isento_mensalidade = 0
                   AND COALESCE(f.status, 'ativo') = 'ativo'
                   AND NOT EXISTS (
                     SELECT 1 FROM mensalidades_pagas mp
                     WHERE mp.filho_id = f.id AND mp.paid_month = ?
                 )"
        );
        $mensalidadesStmt->execute([$monthStart]);
        $counts['receivable_month'] += (int)$mensalidadesStmt->fetchColumn();

    $payablesStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(valor),0) FROM contas_pagar WHERE status = 'Pendente' AND data_vencimento BETWEEN ? AND ?"
    );
    $payablesStmt->execute([$monthStart, $monthEnd]);
    $counts['payable_month'] = (int)$payablesStmt->fetchColumn();

    $cashBeforeStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0)
         FROM caixa_movimentos
         WHERE status = 'realizado' AND data_movimento < ?"
    );
    $cashBeforeStmt->execute([$monthStart]);
    $saldoInicial = (int)$cashBeforeStmt->fetchColumn();

    $cashCurrentStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0)
         FROM caixa_movimentos
         WHERE status = 'realizado' AND data_movimento BETWEEN ? AND ?"
    );
    $cashCurrentStmt->execute([$monthStart, $monthEnd]);
    $saldoMes = (int)$cashCurrentStmt->fetchColumn();
    $counts['cash_month'] = $saldoInicial + $saldoMes;

    $stmt = $pdo->query(
        "SELECT a.id, a.client_id, a.total_amount, a.payment_type, a.notes, a.is_delinquent, a.is_reversed,
                c.name AS client_name, c.phone AS client_phone,
                GROUP_CONCAT(s.name SEPARATOR ', ') AS services
         FROM attendances a
         JOIN clients c ON c.id = a.client_id
         LEFT JOIN attendance_services ats ON ats.attendance_id = a.id
         LEFT JOIN services s ON s.id = ats.service_id
         GROUP BY a.id
         ORDER BY a.id DESC
         LIMIT 5"
    );

    $calendarEvents = buildCalendarEvents($pdo, $monthStart, $monthEnd);

    jsonResponse([
        'ok' => true,
        'counts' => $counts,
        'latest_attendances' => $stmt->fetchAll(),
        'calendar_events' => $calendarEvents,
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
