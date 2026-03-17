<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (session_status() === PHP_SESSION_NONE) {
    safeSessionStart();
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = (string)($_SESSION['user_role'] ?? '');

if ($currentUserId <= 0) {
    jsonResponse(['ok' => false, 'message' => 'Não autenticado'], 401);
}

try {
    $pdo = db();

    if ($action === 'list') {
        if ($currentUserRole !== 'admin') {
            $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
            $stmt->execute([$currentUserId]);
            jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
        }
        $stmt = $pdo->query('SELECT id, name, email, role, is_active FROM users ORDER BY id DESC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }
        $name = requireField('name', 'Nome obrigatório');
        $email = requireField('email', 'Email obrigatório');
        $role = $_POST['role'] ?? 'staff';
        $isActive = (int)($_POST['is_active'] ?? 1);
        $password = $_POST['password'] ?? '123456';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $role, $isActive]);
        jsonResponse(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        if ($currentUserRole !== 'admin' && $id !== $currentUserId) {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }
        $name = requireField('name', 'Nome obrigatório');
        $email = requireField('email', 'Email obrigatório');
        $role = $_POST['role'] ?? 'staff';
        $isActive = (int)($_POST['is_active'] ?? 1);
        $password = trim((string)($_POST['password'] ?? ''));

        if ($currentUserRole !== 'admin') {
            $role = 'staff';
            $isActive = 1;
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, is_active = ?, password = ? WHERE id = ?');
            $stmt->execute([$name, $email, $role, $isActive, $hash, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$name, $email, $role, $isActive, $id]);
        }

        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['ok' => false, 'message' => 'ID inválido'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['ok' => false, 'message' => 'Ação inválida'], 400);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}
