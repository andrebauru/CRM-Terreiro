<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'list') {
        $stmt = $pdo->query('SELECT id, name, description, price, is_active FROM services ORDER BY id DESC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create') {
        $name = requireField('name', 'Nome obrigatório');
        $description = trim((string)($_POST['description'] ?? '')) ?: null;
        $price = (float)($_POST['price'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);

        $stmt = $pdo->prepare('INSERT INTO services (name, description, price, is_active) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $description, $price, $isActive]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $name = requireField('name', 'Nome obrigatório');
        $description = trim((string)($_POST['description'] ?? '')) ?: null;
        $price = (float)($_POST['price'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 1);

        $stmt = $pdo->prepare('UPDATE services SET name = ?, description = ?, price = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$name, $description, $price, $isActive, $id]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
