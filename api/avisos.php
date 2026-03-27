<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';
require_once __DIR__ . '/../app/Helpers/SendGridNotifier.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'list') {
        if ($_apiUserRole === 'admin') {
            $stmt = $pdo->query(
                "SELECT id, titulo, mensagem, is_active, created_by, created_at, updated_at
                 FROM avisos
                 ORDER BY is_active DESC, created_at DESC, id DESC"
            );
        } else {
            $stmt = $pdo->query(
                "SELECT id, titulo, mensagem, is_active, created_by, created_at, updated_at
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
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($titulo === '' || $mensagem === '') {
            jsonResponse(['ok' => false, 'message' => 'Título e mensagem são obrigatórios'], 422);
        }

        $pdo->prepare(
            'INSERT INTO avisos (titulo, mensagem, is_active, created_by) VALUES (?, ?, ?, ?)'
        )->execute([$titulo, $mensagem, $isActive ? 1 : 0, $_apiUserId]);

        try {
            $sendResult = sendGridNotifyBoard($pdo, 'Avisos', 'create', $titulo, $mensagem);
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
        $isActive = (int)($_POST['is_active'] ?? 1);

        if ($id <= 0 || $titulo === '' || $mensagem === '') {
            jsonResponse(['ok' => false, 'message' => 'Dados inválidos'], 422);
        }

        $pdo->prepare(
            'UPDATE avisos SET titulo = ?, mensagem = ?, is_active = ? WHERE id = ?'
        )->execute([$titulo, $mensagem, $isActive ? 1 : 0, $id]);

        try {
            $sendResult = sendGridNotifyBoard($pdo, 'Avisos', 'update', $titulo, $mensagem);
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

        $aviso = $pdo->prepare('SELECT titulo, mensagem FROM avisos WHERE id = ? LIMIT 1');
        $aviso->execute([$id]);
        $avisoData = $aviso->fetch() ?: ['titulo' => 'Aviso removido', 'mensagem' => ''];

        $pdo->prepare('DELETE FROM avisos WHERE id = ?')->execute([$id]);

        try {
            $sendResult = sendGridNotifyBoard(
                $pdo,
                'Avisos',
                'delete',
                (string)($avisoData['titulo'] ?? 'Aviso removido'),
                (string)($avisoData['mensagem'] ?? '')
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
