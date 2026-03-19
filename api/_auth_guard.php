<?php
/**
 * API Authentication Guard
 * Include this file at the top of every API endpoint that requires authentication.
 * It starts the session and checks for a valid user_id.
 * If not authenticated, returns a 401 JSON response and exits.
 */

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    safeSessionStart();
}

$_apiUserId = (int)($_SESSION['user_id'] ?? 0);
$_apiUserRole = (string)($_SESSION['user_role'] ?? '');

if ($_apiUserId <= 0) {
    jsonResponse(['ok' => false, 'message' => 'Não autenticado'], 401);
}
