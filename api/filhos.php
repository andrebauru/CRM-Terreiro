<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function quimbandaColumnExpr(PDO $pdo, string $column, string $fallback): string
{
    return hasColumn($pdo, 'quimbandeiro', $column) ? 'q.' . $column : $fallback . ' AS ' . $column;
}

function normalizeQuimbandaStatus(?string $rawStatus, ?string $legacyGrade = null): string
{
    $status = trim((string)$rawStatus);
    if ($status !== '') {
        $normalized = mb_strtolower($status);
        if (in_array($normalized, ['probatorio', 'probatório'], true)) {
            return 'Probatorio';
        }
        if (in_array($normalized, ['iniciado', 'iniciação', 'iniciacao'], true)) {
            return 'Iniciado';
        }
        if (in_array($normalized, ['tata', 'mestre'], true)) {
            return 'Tata';
        }
    }

    $legacy = mb_strtolower(trim((string)$legacyGrade));
    if (in_array($legacy, ['3º grau', '3o grau', 'mestre', 'tata'], true)) {
        return 'Tata';
    }
    if (in_array($legacy, ['iniciação', 'iniciacao', '1º grau', '1o grau', '2º grau', '2o grau', 'iniciado'], true)) {
        return 'Iniciado';
    }

    return 'Probatorio';
}

function legacyGradeFromStatus(string $status): string
{
    return match ($status) {
        'Iniciado' => 'Iniciação',
        'Tata' => 'Mestre',
        default => 'Probatório',
    };
}

function upsertQuimbandeiro(PDO $pdo, int $filhoId, ?string $probatorio, string $statusQuimbanda, ?string $nomeIniciador, ?string $entidadeReconheceu): void
{
    $columns = ['filho_id', 'probatorio'];
    $values = [$filhoId, $probatorio];
    $updates = ['probatorio = VALUES(probatorio)'];

    if (hasColumn($pdo, 'quimbandeiro', 'status_quimbanda')) {
        $columns[] = 'status_quimbanda';
        $values[] = $statusQuimbanda;
        $updates[] = 'status_quimbanda = VALUES(status_quimbanda)';
    }
    if (hasColumn($pdo, 'quimbandeiro', 'nome_iniciador')) {
        $columns[] = 'nome_iniciador';
        $values[] = $nomeIniciador;
        $updates[] = 'nome_iniciador = VALUES(nome_iniciador)';
    }
    if (hasColumn($pdo, 'quimbandeiro', 'entidade_reconheceu')) {
        $columns[] = 'entidade_reconheceu';
        $values[] = $entidadeReconheceu;
        $updates[] = 'entidade_reconheceu = VALUES(entidade_reconheceu)';
    }

    $sql = 'INSERT INTO quimbandeiro (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    $pdo->prepare($sql)->execute($values);
}

try {
    $pdo = db();

    if ($action === 'list') {
        $stmt = $pdo->query(
            'SELECT f.id, f.name, f.email, f.phone, f.grade, f.grade_date, f.status, f.saiu_at,
                    mensalidade_value, due_day, isento_mensalidade, notes_evolucao, anotacoes,
                    entidade_frente, orixa_pai, orixa_mae,
                    ' . quimbandaColumnExpr($pdo, 'status_quimbanda', 'NULL') . ', ' . quimbandaColumnExpr($pdo, 'nome_iniciador', 'NULL') . ', ' . quimbandaColumnExpr($pdo, 'entidade_reconheceu', 'NULL') . ', q.probatorio
             FROM filhos f
             LEFT JOIN quimbandeiro q ON q.filho_id = f.id
             ORDER BY f.name ASC'
        );
        $rows = array_map(static function (array $row): array {
            $statusQuimbanda = normalizeQuimbandaStatus($row['status_quimbanda'] ?? null, $row['grade'] ?? null);
            $row['status_quimbanda'] = $statusQuimbanda;
            $row['nome_iniciador'] = $row['nome_iniciador'] ?? null;
            $row['entidade_reconheceu'] = $row['entidade_reconheceu'] ?? null;
            $row['probatorio'] = $row['probatorio'] ?? $row['grade_date'] ?? null;
            $row['grade'] = $statusQuimbanda;
            return $row;
        }, $stmt->fetchAll());
        jsonResponse(['ok' => true, 'data' => $rows]);
    }

    if ($action === 'create') {
        $name = requireField('name', 'Nome obrigatório');
        $email       = trim((string)($_POST['email'] ?? '')) ?: null;
        $phone       = trim((string)($_POST['phone'] ?? '')) ?: null;
        $legacyGrade = trim((string)($_POST['grade'] ?? '')) ?: null;
        $probatorio  = trim((string)($_POST['probatorio'] ?? '')) ?: null;
        $statusQuimbanda = normalizeQuimbandaStatus($_POST['status_quimbanda'] ?? null, $legacyGrade);
        $nomeIniciador = trim((string)($_POST['nome_iniciador'] ?? '')) ?: null;
        $entidadeReconheceu = trim((string)($_POST['entidade_reconheceu'] ?? '')) ?: null;
        $grade       = legacyGradeFromStatus($statusQuimbanda);
        $gradeDate   = $probatorio;
        $menVal      = (int)($_POST['mensalidade_value'] ?? 0);
        $dueDay      = (int)($_POST['due_day'] ?? 5);
        $isento      = (int)($_POST['isento_mensalidade'] ?? 0);
        $notesEv     = trim((string)($_POST['notes_evolucao'] ?? '')) ?: null;
        $anotacoes   = trim((string)($_POST['anotacoes'] ?? '')) ?: null;
        $entidade    = trim((string)($_POST['entidade_frente'] ?? '')) ?: null;
        $orixaPai    = trim((string)($_POST['orixa_pai'] ?? '')) ?: null;
        $orixaMae    = trim((string)($_POST['orixa_mae'] ?? '')) ?: null;

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO filhos (name, email, phone, grade, grade_date, mensalidade_value, due_day,
                                 isento_mensalidade, notes_evolucao, anotacoes, entidade_frente, orixa_pai, orixa_mae)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $phone, $grade, $gradeDate, $menVal, $dueDay,
                        $isento, $notesEv, $anotacoes, $entidade, $orixaPai, $orixaMae]);
        $filhoId = (int)$pdo->lastInsertId();
        upsertQuimbandeiro($pdo, $filhoId, $probatorio, $statusQuimbanda, $nomeIniciador, $entidadeReconheceu);
        $pdo->commit();
        jsonResponse(['ok' => true, 'id' => $filhoId]);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);

        $name       = requireField('name', 'Nome obrigatório');
        $email      = trim((string)($_POST['email'] ?? '')) ?: null;
        $phone      = trim((string)($_POST['phone'] ?? '')) ?: null;
        $legacyGrade = trim((string)($_POST['grade'] ?? '')) ?: null;
        $probatorio = trim((string)($_POST['probatorio'] ?? '')) ?: null;
        $statusQuimbanda = normalizeQuimbandaStatus($_POST['status_quimbanda'] ?? null, $legacyGrade);
        $nomeIniciador = trim((string)($_POST['nome_iniciador'] ?? '')) ?: null;
        $entidadeReconheceu = trim((string)($_POST['entidade_reconheceu'] ?? '')) ?: null;
        $grade      = legacyGradeFromStatus($statusQuimbanda);
        $gradeDate  = $probatorio;
        $status     = trim((string)($_POST['status'] ?? 'ativo'));
        $saiuAt     = trim((string)($_POST['saiu_at'] ?? '')) ?: null;
        $menVal     = (int)($_POST['mensalidade_value'] ?? 0);
        $dueDay     = (int)($_POST['due_day'] ?? 5);
        $isento     = (int)($_POST['isento_mensalidade'] ?? 0);
        $notesEv    = trim((string)($_POST['notes_evolucao'] ?? '')) ?: null;
        $anotacoes  = trim((string)($_POST['anotacoes'] ?? '')) ?: null;
        $entidade   = trim((string)($_POST['entidade_frente'] ?? '')) ?: null;
        $orixaPai   = trim((string)($_POST['orixa_pai'] ?? '')) ?: null;
        $orixaMae   = trim((string)($_POST['orixa_mae'] ?? '')) ?: null;

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'UPDATE filhos SET name=?, email=?, phone=?, grade=?, grade_date=?, status=?, saiu_at=?,
                    mensalidade_value=?, due_day=?, isento_mensalidade=?, notes_evolucao=?, anotacoes=?,
                    entidade_frente=?, orixa_pai=?, orixa_mae=?
             WHERE id=?'
        );
        $stmt->execute([$name, $email, $phone, $grade, $gradeDate, $status, $saiuAt,
                        $menVal, $dueDay, $isento, $notesEv, $anotacoes, $entidade, $orixaPai, $orixaMae, $id]);
        upsertQuimbandeiro($pdo, $id, $probatorio, $statusQuimbanda, $nomeIniciador, $entidadeReconheceu);
        $pdo->commit();
        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        $pdo->prepare('DELETE FROM filhos WHERE id = ?')->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    safeJsonError($e);
}