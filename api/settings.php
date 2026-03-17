<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    $pdo = db();

    $exists = (int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($exists === 0) {
        $pdo->exec("INSERT INTO settings (company_name) VALUES ('CRM Terreiro')");
    }

    if ($action === 'get') {
        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        jsonResponse(['ok' => true, 'data' => $stmt->fetch()]);
    }

    if ($action === 'update') {
        $companyName = trim((string)($_POST['company_name'] ?? '')) ?: 'CRM Terreiro';
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

        if ($logoPath) {
            $stmt = $pdo->prepare('UPDATE settings SET company_name = ?, logo_path = ? WHERE id = 1');
            $stmt->execute([$companyName, $logoPath]);
        } else {
            $stmt = $pdo->prepare('UPDATE settings SET company_name = ? WHERE id = 1');
            $stmt->execute([$companyName]);
        }

        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        jsonResponse(['ok' => true, 'data' => $stmt->fetch()]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
