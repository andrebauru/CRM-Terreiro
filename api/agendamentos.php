<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'bootstrap') {
        $services = $pdo->query("SELECT id, name, price FROM services WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $jobs = $pdo->query("SELECT id, name, price FROM trabalhos WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        jsonResponse([
            'ok' => true,
            'services' => $services,
            'jobs' => $jobs,
        ]);
    }

    if ($action === 'create') {
        $nome = trim((string)($_POST['nome'] ?? ''));
        $dataAgendamento = trim((string)($_POST['data_agendamento'] ?? '')) ?: date('Y-m-d');
        $horaAgendamento = trim((string)($_POST['hora_agendamento'] ?? '')) ?: '09:00';
        $tipoAtendimento = strtolower(trim((string)($_POST['tipo_atendimento'] ?? 'servico')));
        $referenciaId = (int)($_POST['referencia_id'] ?? 0);
        $observacoes = trim((string)($_POST['observacoes'] ?? '')) ?: null;

        if ($nome === '') {
            jsonResponse(['ok' => false, 'message' => 'Nome é obrigatório'], 422);
        }
        if (!in_array($tipoAtendimento, ['servico', 'trabalho'], true)) {
            jsonResponse(['ok' => false, 'message' => 'Tipo de atendimento inválido'], 422);
        }
        if ($referenciaId <= 0) {
            jsonResponse(['ok' => false, 'message' => 'Selecione um serviço/trabalho'], 422);
        }

        if ($tipoAtendimento === 'servico') {
            $stmtRef = $pdo->prepare("SELECT id, name, price FROM services WHERE id = ? LIMIT 1");
        } else {
            $stmtRef = $pdo->prepare("SELECT id, name, price FROM trabalhos WHERE id = ? LIMIT 1");
        }
        $stmtRef->execute([$referenciaId]);
        $ref = $stmtRef->fetch();

        if (!$ref) {
            jsonResponse(['ok' => false, 'message' => 'Referência de atendimento não encontrada'], 404);
        }

        $pdo->prepare(
            "INSERT INTO atendimento_agendamentos
                (nome, data_agendamento, hora_agendamento, tipo_atendimento, referencia_id, referencia_nome, valor_previsto, status, observacoes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?)"
        )->execute([
            $nome,
            $dataAgendamento,
            $horaAgendamento,
            $tipoAtendimento,
            $referenciaId,
            (string)$ref['name'],
            (int)$ref['price'],
            $observacoes,
            (int)($_apiUserId ?? 0) ?: null,
        ]);

        jsonResponse([
            'ok' => true,
            'id' => (int)$pdo->lastInsertId(),
        ]);
    }

    if ($action === 'list') {
        $stmt = $pdo->query(
            "SELECT id, nome, data_agendamento, hora_agendamento, tipo_atendimento, referencia_id, referencia_nome,
                    valor_previsto, status, observacoes, converted_attendance_id, created_at
             FROM atendimento_agendamentos
             ORDER BY data_agendamento DESC, hora_agendamento DESC, id DESC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'convert_to_attendance') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }

        $stmt = $pdo->prepare(
            "SELECT id, nome, data_agendamento, hora_agendamento, tipo_atendimento, referencia_id,
                    referencia_nome, valor_previsto, status, observacoes, converted_attendance_id
             FROM atendimento_agendamentos
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $ag = $stmt->fetch();

        if (!$ag) {
            jsonResponse(['ok' => false, 'message' => 'Agendamento não encontrado'], 404);
        }
        if ((string)$ag['status'] !== 'agendado') {
            jsonResponse(['ok' => false, 'message' => 'Somente agendamentos com status agendado podem ser convertidos'], 422);
        }
        if (!empty($ag['converted_attendance_id'])) {
            jsonResponse([
                'ok' => true,
                'attendance_id' => (int)$ag['converted_attendance_id'],
                'message' => 'Agendamento já convertido anteriormente',
            ]);
        }

        $clientStmt = $pdo->prepare("SELECT id FROM clients WHERE name = ? LIMIT 1");
        $clientStmt->execute([(string)$ag['nome']]);
        $clientId = (int)($clientStmt->fetchColumn() ?: 0);

        if ($clientId <= 0) {
            $pdo->prepare("INSERT INTO clients (name) VALUES (?)")->execute([(string)$ag['nome']]);
            $clientId = (int)$pdo->lastInsertId();
        }

        $dataAtendimento = (string)$ag['data_agendamento'];
        $notes = trim((string)($ag['observacoes'] ?? ''));
        $prefixo = 'Gerado por agendamento #' . (int)$ag['id'] . ' - ' . strtoupper((string)$ag['tipo_atendimento']) . ': ' . (string)($ag['referencia_nome'] ?? '');
        $notes = $notes !== '' ? ($prefixo . "\n" . $notes) : $prefixo;

        $pdo->beginTransaction();

        $pdo->prepare(
            "INSERT INTO attendances (client_id, data_atendimento, notes, total_amount, payment_type, is_delinquent, is_reversed)
             VALUES (?, ?, ?, ?, 'cash', 0, 0)"
        )->execute([
            $clientId,
            $dataAtendimento,
            $notes,
            (int)$ag['valor_previsto'],
        ]);

        $attendanceId = (int)$pdo->lastInsertId();

        if ((string)$ag['tipo_atendimento'] === 'servico') {
            $srvStmt = $pdo->prepare("SELECT id, price FROM services WHERE id = ? LIMIT 1");
            $srvStmt->execute([(int)$ag['referencia_id']]);
            $srv = $srvStmt->fetch();
            if ($srv) {
                $pdo->prepare(
                    "INSERT INTO attendance_services (attendance_id, service_id, price)
                     VALUES (?, ?, ?)"
                )->execute([
                    $attendanceId,
                    (int)$srv['id'],
                    (int)$srv['price'],
                ]);
            }
        }

        $pdo->prepare(
            "INSERT INTO attendance_installments (attendance_id, installment_number, amount, due_date, status)
             VALUES (?, 1, ?, ?, 'pending')"
        )->execute([
            $attendanceId,
            (int)$ag['valor_previsto'],
            $dataAtendimento,
        ]);

        $pdo->prepare(
            "UPDATE atendimento_agendamentos
             SET status = 'realizado', converted_attendance_id = ?
             WHERE id = ?"
        )->execute([$attendanceId, $id]);

        $pdo->commit();

        jsonResponse([
            'ok' => true,
            'attendance_id' => $attendanceId,
            'message' => 'Agendamento convertido em atendimento com sucesso',
        ]);
    }

    if ($action === 'calendar') {
        $monthParam = trim((string)($_GET['month'] ?? date('Y-m-01')));
        $monthDate = new DateTime($monthParam);
        $start = $monthDate->format('Y-m-01');
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');

        $stmt = $pdo->prepare(
            "SELECT id, nome, data_agendamento, hora_agendamento, tipo_atendimento, referencia_nome, status
             FROM atendimento_agendamentos
             WHERE data_agendamento BETWEEN ? AND ?"
        );
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll();

        $events = array_map(static function (array $row): array {
            $start = $row['data_agendamento'] . 'T' . substr((string)$row['hora_agendamento'], 0, 5) . ':00';
            return [
                'id' => (int)$row['id'],
                'title' => 'Atendimento - ' . $row['nome'] . (!empty($row['referencia_nome']) ? ' (' . $row['referencia_nome'] . ')' : ''),
                'start' => $start,
                'type' => 'agendamento_atendimento',
                'status' => $row['status'],
                'color' => '#ec4899',
            ];
        }, $rows);

        jsonResponse(['ok' => true, 'events' => $events]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }

        $pdo->prepare("DELETE FROM atendimento_agendamentos WHERE id = ?")->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
