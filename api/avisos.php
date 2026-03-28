<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';
require_once __DIR__ . '/../app/Helpers/SendGridNotifier.php';

function buildAvisosCtaLink(?string $linkPostagem = null): ?string
{
    $linkPostagem = trim((string)$linkPostagem);
    if ($linkPostagem !== '' && preg_match('~^https?://~i', $linkPostagem)) {
        return $linkPostagem;
    }

    $baseUrl = rtrim((string)($_ENV['BASE_URL'] ?? ''), '/');
    if ($baseUrl === '') {
        return null;
    }
    return $baseUrl . '/avisos.php';
}

function normalizeAvisoImageUpload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/avisos';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Não foi possível criar pasta de upload de avisos.');
    }

    $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        throw new RuntimeException('Formato de imagem inválido. Use jpg, png, gif ou webp.');
    }

    $filename = 'aviso_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Falha ao salvar imagem do aviso.');
    }

    return '../uploads/avisos/' . $filename;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();
    ensureColumn($pdo, 'avisos', 'imagem_path', 'VARCHAR(512) NULL');
    ensureColumn($pdo, 'avisos', 'link_postagem', 'VARCHAR(512) NULL');

    if ($action === 'list') {
        if ($_apiUserRole === 'admin') {
            $stmt = $pdo->query(
                "SELECT id, titulo, mensagem, imagem_path, link_postagem, is_active, created_by, created_at, updated_at
                 FROM avisos
                 ORDER BY is_active DESC, created_at DESC, id DESC"
            );
        } else {
            $stmt = $pdo->query(
                "SELECT id, titulo, mensagem, imagem_path, link_postagem, is_active, created_by, created_at, updated_at
                 FROM avisos
                 WHERE is_active = 1
                 ORDER BY created_at DESC, id DESC"
            );
        }

        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($_apiUserRole !== 'admin') {
        jsonResponse(['ok' => false, 'message' => 'Acesso restrito a administradores'], 403);
    }

    if ($action === 'create') {
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $mensagem = trim((string)($_POST['mensagem'] ?? ''));
        $linkPostagem = trim((string)($_POST['link_postagem'] ?? ''));
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($titulo === '' || $mensagem === '') {
            jsonResponse(['ok' => false, 'message' => 'Título e mensagem são obrigatórios'], 422);
        }

        if ($linkPostagem !== '' && !preg_match('~^https?://~i', $linkPostagem)) {
            jsonResponse(['ok' => false, 'message' => 'Link da postagem inválido. Use URL iniciando com http:// ou https://'], 422);
        }

        $imagemPath = null;
        if (isset($_FILES['imagem'])) {
            try {
                $imagemPath = normalizeAvisoImageUpload($_FILES['imagem']);
            } catch (Throwable $e) {
                jsonResponse(['ok' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $pdo->prepare(
            'INSERT INTO avisos (titulo, mensagem, imagem_path, link_postagem, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$titulo, $mensagem, $imagemPath, $linkPostagem !== '' ? $linkPostagem : null, $isActive ? 1 : 0, $_apiUserId]);

        try {
            $sendResult = sendGridNotifyBoard(
                $pdo,
                'Avisos',
                'create',
                $titulo,
                $mensagem,
                buildAvisosCtaLink($linkPostagem),
                $imagemPath
            );
            ensureSendGridLogsTable($pdo);
            persistSendGridLog(
                $pdo,
                $sendResult,
                (string)($sendResult['to_email'] ?? ''),
                (string)($sendResult['from_email'] ?? ($_ENV['EMAIL_FROM'] ?? '')),
                'Quadro de Avisos'
            );
        } catch (Throwable $e) {
            error_log('[Avisos] Falha ao disparar e-mail create: ' . $e->getMessage());
        }

        jsonResponse(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $mensagem = trim((string)($_POST['mensagem'] ?? ''));
        $linkPostagem = trim((string)($_POST['link_postagem'] ?? ''));
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($id <= 0 || $titulo === '' || $mensagem === '') {
            jsonResponse(['ok' => false, 'message' => 'Dados inválidos'], 422);
        }

        if ($linkPostagem !== '' && !preg_match('~^https?://~i', $linkPostagem)) {
            jsonResponse(['ok' => false, 'message' => 'Link da postagem inválido. Use URL iniciando com http:// ou https://'], 422);
        }

        $currentStmt = $pdo->prepare('SELECT imagem_path FROM avisos WHERE id = ? LIMIT 1');
        $currentStmt->execute([$id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $imagemPath = (string)($current['imagem_path'] ?? '');

        if (isset($_FILES['imagem']) && (int)($_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $imagemPath = (string)(normalizeAvisoImageUpload($_FILES['imagem']) ?? '');
            } catch (Throwable $e) {
                jsonResponse(['ok' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $pdo->prepare(
            'UPDATE avisos SET titulo = ?, mensagem = ?, imagem_path = ?, link_postagem = ?, is_active = ? WHERE id = ?'
        )->execute([$titulo, $mensagem, $imagemPath !== '' ? $imagemPath : null, $linkPostagem !== '' ? $linkPostagem : null, $isActive ? 1 : 0, $id]);

        try {
            $sendResult = sendGridNotifyBoard(
                $pdo,
                'Avisos',
                'update',
                $titulo,
                $mensagem,
                buildAvisosCtaLink($linkPostagem),
                $imagemPath !== '' ? $imagemPath : null
            );
            ensureSendGridLogsTable($pdo);
            persistSendGridLog(
                $pdo,
                $sendResult,
                (string)($sendResult['to_email'] ?? ''),
                (string)($sendResult['from_email'] ?? ($_ENV['EMAIL_FROM'] ?? '')),
                'Quadro de Avisos'
            );
        } catch (Throwable $e) {
            error_log('[Avisos] Falha ao disparar e-mail update: ' . $e->getMessage());
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }

        $aviso = $pdo->prepare('SELECT titulo, mensagem, imagem_path, link_postagem FROM avisos WHERE id = ? LIMIT 1');
        $aviso->execute([$id]);
        $avisoData = $aviso->fetch() ?: ['titulo' => 'Aviso removido', 'mensagem' => '', 'imagem_path' => null, 'link_postagem' => null];

        $pdo->prepare('DELETE FROM avisos WHERE id = ?')->execute([$id]);

        try {
            $sendResult = sendGridNotifyBoard(
                $pdo,
                'Avisos',
                'delete',
                (string)($avisoData['titulo'] ?? 'Aviso removido'),
                (string)($avisoData['mensagem'] ?? ''),
                buildAvisosCtaLink((string)($avisoData['link_postagem'] ?? '')),
                (string)($avisoData['imagem_path'] ?? '') !== '' ? (string)$avisoData['imagem_path'] : null
            );
            ensureSendGridLogsTable($pdo);
            persistSendGridLog(
                $pdo,
                $sendResult,
                (string)($sendResult['to_email'] ?? ''),
                (string)($sendResult['from_email'] ?? ($_ENV['EMAIL_FROM'] ?? '')),
                'Quadro de Avisos'
            );
        } catch (Throwable $e) {
            error_log('[Avisos] Falha ao disparar e-mail delete: ' . $e->getMessage());
        }

        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
