<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function monthStart(DateTime $date): string
{
    return $date->format('Y-m-01');
}

function ensureMonthlyCaixaMensalidades(PDO $pdo, string $monthStart): void
{
    $monthDate = new DateTime($monthStart);
    $stmt = $pdo->query(
        "SELECT id, name, mensalidade_value, due_day
         FROM filhos
         WHERE COALESCE(status, 'ativo') = 'ativo'
         ORDER BY id ASC"
    );
    $filhos = $stmt->fetchAll();

    foreach ($filhos as $filho) {
        $dueDate = new DateTime($monthDate->format('Y-m-') . str_pad((string)$filho['due_day'], 2, '0', STR_PAD_LEFT));
        $insert = $pdo->prepare(
            "INSERT INTO caixa_movimentos (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
             VALUES ('entrada', 'mensalidade', ?, ?, ?, ?, 'previsto', ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), data_movimento = VALUES(data_movimento)"
        );
        $insert->execute([
            (int)$filho['id'],
            $monthStart,
            $dueDate->format('Y-m-d'),
            (int)$filho['mensalidade_value'],
            'Mensalidade - ' . $filho['name'],
        ]);
    }

    $paidStmt = $pdo->prepare("SELECT filho_id FROM mensalidades_pagas WHERE paid_month = ?");
    $paidStmt->execute([$monthStart]);
    $paidIds = array_map('intval', array_column($paidStmt->fetchAll(), 'filho_id'));
    if ($paidIds) {
        $in = implode(',', array_fill(0, count($paidIds), '?'));
        $upd = $pdo->prepare(
            "UPDATE caixa_movimentos
             SET status = 'realizado'
             WHERE origem = 'mensalidade' AND mes = ? AND referencia_id IN ($in)"
        );
        $upd->execute(array_merge([$monthStart], $paidIds));
    }
}

try {
    $pdo = db();

    $today = new DateTime('today');
    $currentMonth = monthStart($today);

    ensureMonthlyCaixaMensalidades($pdo, $currentMonth);

    if ($action === 'list') {
        // Monthly auto from filhos
        $stmt = $pdo->prepare(
            "SELECT f.id, f.name, f.grade, f.phone, f.mensalidade_value, f.due_day,
                    p.id AS payment_id, p.paid_month, p.paid_at
             FROM filhos f
             LEFT JOIN mensalidades_pagas p
               ON p.filho_id = f.id AND p.paid_month = ?
             WHERE COALESCE(f.status, 'ativo') = 'ativo'
             ORDER BY f.name ASC"
        );
        $stmt->execute([$currentMonth]);
        $rows = $stmt->fetchAll();

        $data = [];
        foreach ($rows as $row) {
            $dueDate = new DateTime($today->format('Y-m') . '-' . str_pad((string)$row['due_day'], 2, '0', STR_PAD_LEFT));
            $paid = !empty($row['payment_id']);
            $overdue = !$paid && $today > $dueDate;

            $data[] = [
                'type'              => 'mensal',
                'id'                => $row['id'],
                'name'              => $row['name'],
                'grade'             => $row['grade'],
                'phone'             => $row['phone'],
                'mensalidade_value' => (int)$row['mensalidade_value'],
                'due_day'           => (int)$row['due_day'],
                'paid'              => $paid,
                'overdue'           => $overdue,
            ];
        }

        // Extra lancamentos
        $lStmt = $pdo->query(
            "SELECT l.id, l.filho_id, f.name, l.valor, l.data_vencimento, l.pago, l.data_pagamento, l.descricao
             FROM mensalidades_lancamentos l
             JOIN filhos f ON f.id = l.filho_id
             ORDER BY l.data_vencimento DESC"
        );
        $lancamentos = $lStmt->fetchAll();
        foreach ($lancamentos as $l) {
            $venc = new DateTime($l['data_vencimento']);
            $overdue = !$l['pago'] && $today > $venc;
            $data[] = [
                'type'              => 'lancamento',
                'id'                => $l['id'],
                'filho_id'          => $l['filho_id'],
                'name'              => $l['name'],
                'grade'             => '',
                'phone'             => '',
                'mensalidade_value' => (int)$l['valor'],
                'due_day'           => null,
                'data_vencimento'   => $l['data_vencimento'],
                'paid'              => (bool)$l['pago'],
                'overdue'           => $overdue,
                'data_pagamento'    => $l['data_pagamento'],
                'descricao'         => $l['descricao'],
            ];
        }

        jsonResponse(['ok' => true, 'data' => $data]);
    }

    if ($action === 'pay') {
        $filhoId = (int)($_POST['filho_id'] ?? 0);
        if ($filhoId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Filho inválido'], 422);
        }

        $stmt = $pdo->prepare('SELECT mensalidade_value FROM filhos WHERE id = ?');
        $stmt->execute([$filhoId]);
        $value = (int)$stmt->fetchColumn();

        $insert = $pdo->prepare(
            'INSERT INTO mensalidades_pagas (filho_id, paid_month, amount) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount), paid_at = CURRENT_TIMESTAMP'
        );
        $insert->execute([$filhoId, $currentMonth, $value]);

        $today = new DateTime('today');
        $insertMov = $pdo->prepare(
            "INSERT INTO caixa_movimentos (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
             VALUES ('entrada', 'mensalidade', ?, ?, ?, ?, 'realizado', ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), data_movimento = VALUES(data_movimento), status = 'realizado'"
        );
        $insertMov->execute([
            $filhoId,
            $currentMonth,
            $today->format('Y-m-d'),
            $value,
            'Mensalidade - pagamento',
        ]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'pay_lancamento') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $stmt = $pdo->prepare('SELECT valor, descricao, data_vencimento FROM mensalidades_lancamentos WHERE id = ?');
        $stmt->execute([$id]);
        $lanc = $stmt->fetch();
        if (!$lanc) {
            jsonResponse(['ok' => false, 'message' => 'Lançamento não encontrado'], 404);
        }
        $pdo->prepare('UPDATE mensalidades_lancamentos SET pago = 1, data_pagamento = CURDATE() WHERE id = ?')
            ->execute([$id]);

        $today = new DateTime('today');
        $month = monthStart($today);
        $insertMov = $pdo->prepare(
            "INSERT INTO caixa_movimentos (tipo, origem, referencia_id, mes, data_movimento, valor, status, descricao)
             VALUES ('entrada', 'mensalidade', NULL, ?, ?, ?, 'realizado', ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), data_movimento = VALUES(data_movimento), status = 'realizado'"
        );
        $insertMov->execute([
            $month,
            $today->format('Y-m-d'),
            (int)$lanc['valor'],
            'Mensalidade extra - ' . ($lanc['descricao'] ?? 'Lançamento'),
        ]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'create_lancamento') {
        $filhoId = (int)($_POST['filho_id'] ?? 0);
        if ($filhoId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Filho inválido'], 422);
        }
        $valor = (int)($_POST['valor'] ?? 0);
        $dataVencimento = $_POST['data_vencimento'] ?? date('Y-m-d');
        $descricao = trim((string)($_POST['descricao'] ?? '')) ?: null;

        $stmt = $pdo->prepare('INSERT INTO mensalidades_lancamentos (filho_id, valor, data_vencimento, descricao) VALUES (?, ?, ?, ?)');
        $stmt->execute([$filhoId, $valor, $dataVencimento, $descricao]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($action === 'list_filhos') {
        $stmt = $pdo->query('SELECT id, name, grade FROM filhos ORDER BY name ASC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
