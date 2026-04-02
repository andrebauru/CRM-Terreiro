<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function quimbandaSelectField(PDO $pdo, string $column, string $fallback = 'NULL'): string
{
    return hasColumn($pdo, 'quimbandeiro', $column) ? 'q.' . $column : $fallback . ' AS ' . $column;
}

function salvarQuimbandeiroCompat(PDO $pdo, int $filhoId, ?string $probatorio, ?string $linkIniciacao, ?string $maoBuzios, ?string $maoFaca, string $statusQuimbanda, ?string $nomeIniciador, ?string $entidadeReconheceu): void
{
    $columns = ['filho_id', 'probatorio', 'link_iniciacao', 'mao_buzios', 'mao_faca'];
    $values = [$filhoId, $probatorio, $linkIniciacao, $maoBuzios, $maoFaca];
    $updates = ['probatorio = VALUES(probatorio)', 'link_iniciacao = VALUES(link_iniciacao)', 'mao_buzios = VALUES(mao_buzios)', 'mao_faca = VALUES(mao_faca)'];

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

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO quimbandeiro (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    $pdo->prepare($sql)->execute($values);
}

try {
    $pdo = db();

    if ($action === 'list') {
        $stmt = $pdo->query(
            "SELECT f.id, f.name,
                    q.probatorio, q.link_iniciacao, q.mao_buzios, q.mao_faca,
                    " . quimbandaSelectField($pdo, 'status_quimbanda', "'Probatorio'") . ",
                    " . quimbandaSelectField($pdo, 'nome_iniciador') . ",
                    " . quimbandaSelectField($pdo, 'entidade_reconheceu') . "
             FROM filhos f
             LEFT JOIN quimbandeiro q ON q.filho_id = f.id
             WHERE (f.status IS NULL OR f.status = 'ativo')
             ORDER BY f.name ASC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // Filhos que ainda NÃO possuem registro no quimbandeiro
    if ($action === 'unregistered') {
        $stmt = $pdo->query(
            "SELECT f.id, f.name
             FROM filhos f
             WHERE (f.status IS NULL OR f.status = 'ativo')
               AND NOT EXISTS (SELECT 1 FROM quimbandeiro q WHERE q.filho_id = f.id)
             ORDER BY f.name ASC"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'save') {
        $filhoId       = (int)($_POST['filho_id'] ?? 0);
        if ($filhoId <= 0) jsonResponse(['ok' => false, 'message' => 'Filho inválido'], 422);

        $probatorio    = trim((string)($_POST['probatorio'] ?? '')) ?: null;
        $linkIniciacao = trim((string)($_POST['link_iniciacao'] ?? '')) ?: null;
        $maoBuzios     = trim((string)($_POST['mao_buzios'] ?? '')) ?: null;
        $maoFaca       = trim((string)($_POST['mao_faca'] ?? '')) ?: null;
        $statusQuimbanda = trim((string)($_POST['status_quimbanda'] ?? 'Probatorio')) ?: 'Probatorio';
        $nomeIniciador = trim((string)($_POST['nome_iniciador'] ?? '')) ?: null;
        $entidadeReconheceu = trim((string)($_POST['entidade_reconheceu'] ?? '')) ?: null;

        if (!in_array($statusQuimbanda, ['Probatorio', 'Iniciado', 'Tata'], true)) {
            $statusQuimbanda = 'Probatorio';
        }

        salvarQuimbandeiroCompat($pdo, $filhoId, $probatorio, $linkIniciacao, $maoBuzios, $maoFaca, $statusQuimbanda, $nomeIniciador, $entidadeReconheceu);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}