<?php

declare(strict_types=1);

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        [$key, $value] = $parts;
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"");
        $_ENV[$key] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

// ── SECURITY: Suppress error display in production ──
// Legacy pages (*.php) bypass index.php, so error settings must be applied here too.
$_appEnv = $_ENV['APP_ENV'] ?? 'production';
ini_set('display_errors', $_appEnv === 'development' ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
if (!ini_get('error_log')) {
    $logDir = __DIR__ . '/storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    ini_set('error_log', $logDir . '/php_errors.log');
}

/**
 * Inicia sessão com configuração segura de cookies.
 * secure=false garante que cookies funcionam em HTTP (dev local).
 * Em produção, o redirect HTTPS no index.php garante a segurança.
 */
function safeSessionStart(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $name = $_ENV['SESSION_COOKIE_NAME'] ?? 'CRM_Terreiro_Session';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $host    = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port    = $_ENV['DB_PORT'] ?? '3306';
    $db      = $_ENV['DB_NAME'] ?? 'crm_terreiro';
    $user    = $_ENV['DB_USER'] ?? 'root';
    $pass    = $_ENV['DB_PASS'] ?? '';
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireField(string $key, string $message = 'Campo obrigatório'): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    if ($value === '') {
        jsonResponse(['ok' => false, 'message' => $message], 422);
    }
    return $value;
}

/**
 * Returns a safe JSON error response.
 * In production, hides the real error message to avoid leaking internal details.
 * Always logs the real error.
 */
function safeJsonError(Throwable $e, int $status = 500): void
{
    error_log('[API Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $appEnv = $_ENV['APP_ENV'] ?? 'production';
    $message = ($appEnv === 'development') ? $e->getMessage() : 'Erro interno do servidor';
    jsonResponse(['ok' => false, 'message' => $message], $status);
}

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    if (!(int)$stmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function ensureEnumHasValue(PDO $pdo, string $table, string $column, string $newDefinition, string $markerValue): void
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    $currentType = $stmt->fetchColumn();
    if ($currentType !== false && strpos($currentType, $markerValue) === false) {
        $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$newDefinition}");
    }
}
