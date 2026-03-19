<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    $pdo = db();

    $exists = (int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($exists === 0) {
        $pdo->exec("INSERT INTO settings (company_name) VALUES ('CRM Terreiro')");
    }

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
        $stmt = $pdo->query("SELECT l.*, u.name AS user_name FROM logs_eventos l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 100");
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'get') {
        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        jsonResponse(['ok' => true, 'data' => $stmt->fetch()]);
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

        if ($logoPath) {
            $sql .= ', logo_path = ?';
            $params[] = $logoPath;
        }

        $sql .= ' WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        jsonResponse(['ok' => true, 'data' => $stmt->fetch()]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    safeJsonError($e);
}
