<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'list') {
        $stmt = $pdo->query(
            'SELECT id, name, email, phone, grade, grade_date, status, saiu_at,
                    mensalidade_value, due_day, isento_mensalidade, notes_evolucao, anotacoes,
                    entidade_frente, orixa_pai, orixa_mae
             FROM filhos ORDER BY name ASC'
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create') {
        $name = requireField('name', 'Nome obrigatório');
        $email       = trim((string)($_POST['email'] ?? '')) ?: null;
        $phone       = trim((string)($_POST['phone'] ?? '')) ?: null;
        $grade       = trim((string)($_POST['grade'] ?? 'Iniciação')) ?: 'Iniciação';
        $gradeDate   = trim((string)($_POST['grade_date'] ?? '')) ?: null;
        $menVal      = (int)($_POST['mensalidade_value'] ?? 0);
        $dueDay      = (int)($_POST['due_day'] ?? 5);
        $isento      = (int)($_POST['isento_mensalidade'] ?? 0);
        $notesEv     = trim((string)($_POST['notes_evolucao'] ?? '')) ?: null;
        $anotacoes   = trim((string)($_POST['anotacoes'] ?? '')) ?: null;
        $entidade    = trim((string)($_POST['entidade_frente'] ?? '')) ?: null;
        $orixaPai    = trim((string)($_POST['orixa_pai'] ?? '')) ?: null;
        $orixaMae    = trim((string)($_POST['orixa_mae'] ?? '')) ?: null;

        $stmt = $pdo->prepare(
            'INSERT INTO filhos (name, email, phone, grade, grade_date, mensalidade_value, due_day,
                                 isento_mensalidade, notes_evolucao, anotacoes, entidade_frente, orixa_pai, orixa_mae)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $phone, $grade, $gradeDate, $menVal, $dueDay,
                        $isento, $notesEv, $anotacoes, $entidade, $orixaPai, $orixaMae]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);

        $name       = requireField('name', 'Nome obrigatório');
        $email      = trim((string)($_POST['email'] ?? '')) ?: null;
        $phone      = trim((string)($_POST['phone'] ?? '')) ?: null;
        $grade      = trim((string)($_POST['grade'] ?? 'Iniciação')) ?: 'Iniciação';
        $gradeDate  = trim((string)($_POST['grade_date'] ?? '')) ?: null;
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

        $stmt = $pdo->prepare(
            'UPDATE filhos SET name=?, email=?, phone=?, grade=?, grade_date=?, status=?, saiu_at=?,
                    mensalidade_value=?, due_day=?, isento_mensalidade=?, notes_evolucao=?, anotacoes=?,
                    entidade_frente=?, orixa_pai=?, orixa_mae=?
             WHERE id=?'
        );
        $stmt->execute([$name, $email, $phone, $grade, $gradeDate, $status, $saiuAt,
                        $menVal, $dueDay, $isento, $notesEv, $anotacoes, $entidade, $orixaPai, $orixaMae, $id]);
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
    safeJsonError($e);
}