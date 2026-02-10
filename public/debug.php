<?php
// Simple diagnostics page for CRM-Terreiro
// WARNING: Remove or protect this file after use.

declare(strict_types=1);

function ok($value): string {
    return $value ? 'OK' : 'FALHA';
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$basePath = dirname(__DIR__);
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath);
}
$envPath = $basePath . '/.env';
$logPath = $basePath . '/storage/logs/app.log';

// Load app config if possible (may trigger errors)
$constantsLoaded = false;
try {
    require_once BASE_PATH . '/app/config.php';
    $constantsLoaded = true;
} catch (Throwable $e) {
    $constantsLoaded = false;
    $configError = $e->getMessage();
}

$checks = [
    'PHP Version' => PHP_VERSION,
    'SAPI' => PHP_SAPI,
    'Loaded php.ini' => php_ini_loaded_file() ?: 'N/D',
    'PDO loaded' => extension_loaded('pdo'),
    'pdo_mysql loaded' => extension_loaded('pdo_mysql'),
    'mysqli loaded' => extension_loaded('mysqli'),
    'openssl loaded' => extension_loaded('openssl'),
    'mbstring loaded' => extension_loaded('mbstring'),
    'BASE_PATH exists' => is_dir($basePath),
    '.env exists' => file_exists($envPath),
    '.env readable' => is_readable($envPath),
    'storage/logs exists' => is_dir($basePath . '/storage/logs'),
    'storage/logs writable' => is_writable($basePath . '/storage/logs'),
    'app.log exists' => file_exists($logPath),
    'app.log writable' => is_writable($logPath),
];

$envPreview = [];
if (file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $envPreview[$key] = '***'; // hide values
        }
    }
}

$dbCheck = 'N/D';
$dbError = null;
if ($constantsLoaded && defined('DB_HOST')) {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $dbCheck = 'OK';
    } catch (Throwable $e) {
        $dbCheck = 'FALHA';
        $dbError = $e->getMessage();
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <title>CRM-Terreiro Debug</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; }
        h1 { margin-bottom: 8px; }
        .card { background: #111827; padding: 16px; border-radius: 8px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #1f2937; }
        .ok { color: #22c55e; font-weight: bold; }
        .fail { color: #ef4444; font-weight: bold; }
        code { color: #93c5fd; }
    </style>
</head>
<body>
    <h1>CRM-Terreiro Debug</h1>
    <div class="card">
        <h2>Diagnóstico Geral</h2>
        <table>
            <tbody>
            <?php foreach ($checks as $label => $value): ?>
                <tr>
                    <th><?= h($label) ?></th>
                    <td>
                        <?php if (is_bool($value)): ?>
                            <span class="<?= $value ? 'ok' : 'fail' ?>"><?= h(ok($value)) ?></span>
                        <?php else: ?>
                            <?= h($value) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Configuração</h2>
        <?php if (!$constantsLoaded): ?>
            <p class="fail">Falha ao carregar app/config.php: <?= h($configError ?? 'Erro desconhecido') ?></p>
        <?php else: ?>
            <p><strong>APP_ENV:</strong> <?= h(defined('APP_ENV') ? APP_ENV : 'N/D') ?></p>
            <p><strong>APP_NAME:</strong> <?= h(defined('APP_NAME') ? APP_NAME : 'N/D') ?></p>
            <p><strong>BASE_URL:</strong> <?= h(defined('BASE_URL') ? BASE_URL : 'N/D') ?></p>
            <p><strong>ROUTE_BASE:</strong> <?= h(defined('ROUTE_BASE') ? ROUTE_BASE : 'N/D') ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>.env (chaves detectadas)</h2>
        <?php if (empty($envPreview)): ?>
            <p class="fail">Nenhuma chave encontrada ou .env ilegível.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($envPreview as $key => $masked): ?>
                    <li><code><?= h($key) ?></code>: <?= h($masked) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Teste de Conexão MySQL</h2>
        <?php if ($dbCheck === 'N/D'): ?>
            <p class="fail">Configuração não carregada (DB_* não definido).</p>
        <?php elseif ($dbCheck === 'OK'): ?>
            <p class="ok">Conexão OK.</p>
        <?php else: ?>
            <p class="fail">Falha: <?= h($dbError ?? 'Erro desconhecido') ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Instruções</h2>
        <ol>
            <li>Abra <code>/debug.php</code> no navegador.</li>
            <li>Depois de resolver, remova este arquivo.</li>
        </ol>
    </div>
</body>
</html>
