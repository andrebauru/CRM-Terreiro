<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (session_status() === PHP_SESSION_NONE) {
    safeSessionStart();
}

// ── REGISTER (self-registration, no auth needed) ──
if ($action === 'register') {
    try {
        $pdo = db();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));
        if ($name === '' || $email === '' || $password === '') {
            jsonResponse(['ok' => false, 'message' => 'Nome, email e senha são obrigatórios'], 422);
        }
        if (strlen($password) < 6) {
            jsonResponse(['ok' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres'], 422);
        }
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            jsonResponse(['ok' => false, 'message' => 'Este email já está cadastrado'], 422);
        }
        $phone = trim((string)($_POST['phone'] ?? ''));
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $hash, 'user', 0]);
        jsonResponse(['ok' => true, 'message' => 'Cadastro realizado! Aguarde a ativação pelo administrador.']);
    } catch (Throwable $e) {
        safeJsonError($e);
    }
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
            $stmt = $pdo->prepare('SELECT id, name, email, phone, role, is_active, allowed_pages FROM users WHERE id = ?');
            $stmt->execute([$currentUserId]);
            jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
        }
        $stmt = $pdo->query('SELECT id, name, email, phone, role, is_active, allowed_pages FROM users ORDER BY id DESC');
        jsonResponse(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'create') {
        if ($currentUserRole !== 'admin') {
            jsonResponse(['ok' => false, 'message' => 'Acesso negado'], 403);
        }
        $name = requireField('name', 'Nome obrigatório');
        $email = requireField('email', 'Email obrigatório');
        $phone = trim((string)($_POST['phone'] ?? ''));
        $role = $_POST['role'] ?? 'staff';
        $isActive = (int)($_POST['is_active'] ?? 1);
        $allowedPages = trim((string)($_POST['allowed_pages'] ?? ''));
        $password = $_POST['password'] ?? '123456';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role, is_active, allowed_pages) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $hash, $role, $isActive, $allowedPages ?: null]);
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
        $phone = trim((string)($_POST['phone'] ?? ''));
        $role = $_POST['role'] ?? 'staff';
        $isActive = (int)($_POST['is_active'] ?? 1);
        $allowedPages = trim((string)($_POST['allowed_pages'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        if ($currentUserRole !== 'admin') {
            $role = 'staff';
            $isActive = 1;
            $allowedPages = '';
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ?, allowed_pages = ?, password = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone, $role, $isActive, $allowedPages ?: null, $hash, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ?, allowed_pages = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone, $role, $isActive, $allowedPages ?: null, $id]);
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
    safeJsonError($e);
}
