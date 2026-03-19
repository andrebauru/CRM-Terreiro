<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/_auth_guard.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    // Auto-create tables if not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tipos_gira (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS giras (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_gira_id INT NOT NULL,
            plataforma VARCHAR(255) NOT NULL DEFAULT 'Instagram',
            foto_path VARCHAR(512) NULL,
            data_postagem DATE NULL,
            data_realizacao DATE NOT NULL,
            descricao TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tipo_gira_id) REFERENCES tipos_gira(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Migrate ENUM → VARCHAR if needed
    try {
        $colInfo = $pdo->query("SHOW COLUMNS FROM giras WHERE Field = 'plataforma'")->fetch();
        if ($colInfo && stripos($colInfo['Type'], 'enum') !== false) {
            $pdo->exec("ALTER TABLE giras MODIFY COLUMN plataforma VARCHAR(255) NOT NULL DEFAULT 'Instagram'");
        }
    } catch (Throwable $e) { /* ignore */ }

    // ── LIST GIRAS ──
    if ($action === 'list') {
        $stmt = $pdo->query('
            SELECT g.*, t.nome AS tipo_gira_nome
            FROM giras g
            JOIN tipos_gira t ON t.id = g.tipo_gira_id
            ORDER BY g.data_realizacao DESC, g.created_at DESC
        ');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // ── LIST TIPOS ──
    if ($action === 'list_tipos') {
        $stmt = $pdo->query('SELECT * FROM tipos_gira ORDER BY nome ASC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    // ── CREATE TIPO ──
    if ($action === 'create_tipo') {
        $nome = trim((string)($_POST['nome'] ?? ''));
        if (!$nome) {
            jsonResponse(['ok' => false, 'message' => 'Nome obrigatório'], 400);
        }
        $stmt = $pdo->prepare('INSERT INTO tipos_gira (nome) VALUES (?)');
        $stmt->execute([$nome]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    // ── DELETE TIPO ──
    if ($action === 'delete_tipo') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(['ok' => false, 'message' => 'ID obrigatório'], 400);
        }
        $stmt = $pdo->prepare('DELETE FROM tipos_gira WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    // ── CREATE GIRA ──
    if ($action === 'create') {
        $tipoId = (int)($_POST['tipo_gira_id'] ?? 0);
        $plataforma = trim((string)($_POST['plataforma'] ?? 'Instagram'));
        // Accept comma-separated platforms from multi-select
        if (empty($plataforma)) $plataforma = 'Instagram';
        $dataPostagem = trim((string)($_POST['data_postagem'] ?? '')) ?: null;
        $dataRealizacao = trim((string)($_POST['data_realizacao'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? '')) ?: null;

        if (!$tipoId || !$dataRealizacao) {
            jsonResponse(['ok' => false, 'message' => 'Tipo de gira e data de realização são obrigatórios'], 400);
        }

        $fotoPath = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/giras';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'gira_' . time() . '_' . mt_rand(1000, 9999) . ($ext ? '.' . $ext : '');
            $targetPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
                $fotoPath = '../uploads/giras/' . $filename;
            }
        }

        $stmt = $pdo->prepare('
            INSERT INTO giras (tipo_gira_id, plataforma, foto_path, data_postagem, data_realizacao, descricao)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$tipoId, $plataforma, $fotoPath, $dataPostagem, $dataRealizacao, $descricao]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    // ── UPDATE GIRA ──
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $tipoId = (int)($_POST['tipo_gira_id'] ?? 0);
        $plataforma = trim((string)($_POST['plataforma'] ?? 'Instagram'));
        if (empty($plataforma)) $plataforma = 'Instagram';
        $dataPostagem = trim((string)($_POST['data_postagem'] ?? '')) ?: null;
        $dataRealizacao = trim((string)($_POST['data_realizacao'] ?? ''));
        $descricao = trim((string)($_POST['descricao'] ?? '')) ?: null;

        if (!$id || !$tipoId || !$dataRealizacao) {
            jsonResponse(['ok' => false, 'message' => 'Dados obrigatórios faltando'], 400);
        }

        $fotoPath = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/giras';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $filename = 'gira_' . time() . '_' . mt_rand(1000, 9999) . ($ext ? '.' . $ext : '');
            $targetPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
                $fotoPath = '../uploads/giras/' . $filename;
            }
        }

        if ($fotoPath) {
            $stmt = $pdo->prepare('
                UPDATE giras SET tipo_gira_id = ?, plataforma = ?, foto_path = ?, data_postagem = ?, data_realizacao = ?, descricao = ?
                WHERE id = ?
            ');
            $stmt->execute([$tipoId, $plataforma, $fotoPath, $dataPostagem, $dataRealizacao, $descricao, $id]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE giras SET tipo_gira_id = ?, plataforma = ?, data_postagem = ?, data_realizacao = ?, descricao = ?
                WHERE id = ?
            ');
            $stmt->execute([$tipoId, $plataforma, $dataPostagem, $dataRealizacao, $descricao, $id]);
        }

        jsonResponse(['ok' => true]);
    }

    // ── DELETE GIRA ──
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            jsonResponse(['ok' => false, 'message' => 'ID obrigatório'], 400);
        }
        $stmt = $pdo->prepare('DELETE FROM giras WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
