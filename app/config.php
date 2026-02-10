<?php

declare(strict_types=1);

// Basic configuration for the application
// This file will load environment variables and define global constants.

// Load environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(BASE_PATH);
        $dotenv->safeLoad();
    } else {
        // Fallback: simple .env file parsing
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$key, $value] = $parts;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'crm_terreiro');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Application settings
define('APP_NAME', $_ENV['APP_NAME'] ?? 'CRM Terreiro');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

// Dynamic BASE_URL - detecta corretamente o caminho base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Calcula o caminho base removendo /public/index.php ou /index.php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = '';
$publicPrefix = '';

if (strpos($scriptName, '/public/index.php') !== false) {
    $basePath = str_replace('/public/index.php', '', $scriptName);
    $publicPrefix = '/public';
} elseif (strpos($scriptName, '/index.php') !== false) {
    $basePath = dirname(dirname($scriptName));
    if ($basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }

    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $isPublicRoot = $docRoot !== '' && basename($docRoot) === 'public';
    $publicPrefix = $isPublicRoot ? '' : '/public';
}

// Remove barra final se existir
$basePath = rtrim($basePath, '/');

// BASE_URL aponta para a pasta public (para CSS, JS, imagens)
define('BASE_URL', $protocol . $host . $basePath . $publicPrefix);

// ROUTE_BASE é o caminho base para links/rotas (sem /public pois .htaccess redireciona)
define('ROUTE_BASE', $basePath);

define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo');

date_default_timezone_set(APP_TIMEZONE);

// Security settings
define('CSRF_TOKEN_SECRET', $_ENV['CSRF_TOKEN_SECRET'] ?? 'your_very_strong_random_secret_key_here'); // CHANGE THIS IN .env!
define('SESSION_COOKIE_NAME', $_ENV['SESSION_COOKIE_NAME'] ?? 'CRM_Terreiro_Session');

// Paths
define('VIEW_PATH', BASE_PATH . '/app/views');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('LOG_PATH', STORAGE_PATH . '/logs');
