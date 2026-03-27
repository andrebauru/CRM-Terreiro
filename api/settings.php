<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';
require_once __DIR__ . '/../app/Helpers/SendGridNotifier.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    $pdo = db();

    $exists = (int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($exists === 0) {
        $pdo->exec("INSERT INTO settings (company_name) VALUES ('CRM Terreiro')");
    }
    ensureColumn($pdo, 'settings', 'notification_email', 'VARCHAR(255) NULL');
    ensureColumn($pdo, 'settings', 'sendgrid_api_key', 'TEXT NULL');
    ensureColumn($pdo, 'settings', 'sendgrid_from_email', 'VARCHAR(255) NULL');
    ensureColumn($pdo, 'settings', 'sendgrid_from_name', 'VARCHAR(255) NULL');

    // Endpoint para registrar prints/cópias
    if ($action === 'log_event') {
        $event = trim((string)($_POST['event'] ?? ''));
        $page = trim((string)($_POST['page'] ?? ''));
        $userAgent = trim((string)($_POST['user_agent'] ?? ''));
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo->exec("CREATE TABLE IF NOT EXISTS logs_eventos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            event VARCHAR(50) NOT NULL,
            page VARCHAR(50) NULL,
            user_agent TEXT NULL,
            ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $stmt = $pdo->prepare("INSERT INTO logs_eventos (user_id, event, page, user_agent, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $event, $page, $userAgent, $ip]);
        jsonResponse(['ok' => true]);
    }

    // Endpoint para retornar logs de prints/cópias
    if ($action === 'get_logs_eventos') {
        if ($_apiUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso restrito a administradores'], 403);
        }
        $stmt = $pdo->query("SELECT l.*, u.name AS user_name FROM logs_eventos l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 100");
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'get_sendgrid_logs') {
        if ($_apiUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso restrito a administradores'], 403);
        }

        ensureSendGridLogsTable($pdo);
        $stmt = $pdo->query(
            "SELECT l.*, u.name AS user_name
             FROM sendgrid_logs l
             LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.id DESC
             LIMIT 100"
        );
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'test_sendgrid') {
        if ($_apiUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso restrito a administradores'], 403);
        }

        $result = sendGridNotifyBoard(
            $pdo,
            'SendGrid',
            'test',
            'Teste de integração',
            'Disparo de teste executado em ' . date('Y-m-d H:i:s')
        );

        jsonResponse([
            'ok' => !empty($result['ok']),
            'message' => (string)($result['message'] ?? ''),
            'data' => $result,
        ], !empty($result['ok']) ? 200 : 422);
    }

    if ($action === 'get') {
        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        $data = $stmt->fetch() ?: [];
        if (array_key_exists('sendgrid_api_key', $data)) {
            $data['has_sendgrid_api_key'] = trim((string)$data['sendgrid_api_key']) !== '';
            $data['sendgrid_api_key'] = '';
        }
        jsonResponse(['ok' => true, 'data' => $data]);
    }

    if ($action === 'update') {
        $companyName = trim((string)($_POST['company_name'] ?? '')) ?: 'CRM Terreiro';
        $currencyCode = trim((string)($_POST['currency_code'] ?? ''));
        $currencySymbol = '';
        if ($currencyCode === 'BRL') {
            $currencySymbol = 'R$';
        } elseif ($currencyCode === 'JPY') {
            $currencySymbol = '¥';
        }
        $language = trim((string)($_POST['language'] ?? ''));
        if (!in_array($language, ['pt', 'ja'], true)) {
            $language = 'pt';
        }
        $notificationEmail = trim((string)($_POST['notification_email'] ?? ''));
        $sendgridApiKey = trim((string)($_POST['sendgrid_api_key'] ?? ''));
        $sendgridFromEmail = trim((string)($_POST['sendgrid_from_email'] ?? ''));
        $sendgridFromName = trim((string)($_POST['sendgrid_from_name'] ?? ''));
        $logoPath = null;

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/logo';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . ($ext ? '.' . $ext : '');
            $targetPath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                jsonResponse(['ok' => false, 'message' => 'Falha ao enviar logo'], 500);
            }

            $logoPath = '../uploads/logo/' . $filename;
        }

        $sql = 'UPDATE settings SET company_name = ?';
        $params = [$companyName];

        if ($currencyCode) {
            $sql .= ', currency_code = ?, currency_symbol = ?';
            $params[] = $currencyCode;
            $params[] = $currencySymbol;
        }

        $sql .= ', language = ?';
        $params[] = $language;

        $sql .= ', notification_email = ?';
        $params[] = $notificationEmail !== '' ? $notificationEmail : null;

        $sql .= ', sendgrid_from_email = ?';
        $params[] = $sendgridFromEmail !== '' ? $sendgridFromEmail : null;

        $sql .= ', sendgrid_from_name = ?';
        $params[] = $sendgridFromName !== '' ? $sendgridFromName : null;

        if ($sendgridApiKey !== '') {
            $sql .= ', sendgrid_api_key = ?';
            $params[] = $sendgridApiKey;
        }

        if ($logoPath) {
            $sql .= ', logo_path = ?';
            $params[] = $logoPath;
        }

        $sql .= ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        $data = $stmt->fetch() ?: [];
        if (array_key_exists('sendgrid_api_key', $data)) {
            $data['has_sendgrid_api_key'] = trim((string)$data['sendgrid_api_key']) !== '';
            $data['sendgrid_api_key'] = '';
        }
        jsonResponse(['ok' => true, 'data' => $data]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
