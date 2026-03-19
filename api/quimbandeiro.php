<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'list') {
        $stmt = $pdo->query(
            "SELECT f.id, f.name,
                    q.probatorio, q.link_iniciacao, q.mao_buzios, q.mao_faca,
                    q.grau1, q.grau2, q.grau3
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
        $grau1         = trim((string)($_POST['grau1'] ?? '')) ?: null;
        $grau2         = trim((string)($_POST['grau2'] ?? '')) ?: null;
        $grau3         = trim((string)($_POST['grau3'] ?? '')) ?: null;

        $stmt = $pdo->prepare(
            "INSERT INTO quimbandeiro (filho_id, probatorio, link_iniciacao, mao_buzios, mao_faca, grau1, grau2, grau3)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                probatorio = VALUES(probatorio),
                link_iniciacao = VALUES(link_iniciacao),
                mao_buzios = VALUES(mao_buzios),
                mao_faca = VALUES(mao_faca),
                grau1 = VALUES(grau1),
                grau2 = VALUES(grau2),
                grau3 = VALUES(grau3)"
        );
        $stmt->execute([$filhoId, $probatorio, $linkIniciacao, $maoBuzios, $maoFaca, $grau1, $grau2, $grau3]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}