<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $pdo = db();

    if ($action === 'list') {
        $stmt = $pdo->query('SELECT id, name, email, phone, address FROM clients ORDER BY id DESC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create') {
        $name = requireField('name', 'Nome obrigatório');
        $email = trim((string)($_POST['email'] ?? '')) ?: null;
        $phone = trim((string)($_POST['phone'] ?? '')) ?: null;
        $address = trim((string)($_POST['address'] ?? '')) ?: null;

        $stmt = $pdo->prepare('INSERT INTO clients (name, email, phone, address) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $address]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $name = requireField('name', 'Nome obrigatório');
        $email = trim((string)($_POST['email'] ?? '')) ?: null;
        $phone = trim((string)($_POST['phone'] ?? '')) ?: null;
        $address = trim((string)($_POST['address'] ?? '')) ?: null;

        $stmt = $pdo->prepare('UPDATE clients SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, $address, $id]);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM clients WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
