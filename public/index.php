<?php

declare(strict_types=1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload Composer dependencies
require BASE_PATH . '/vendor/autoload.php';

// Include configuration
require BASE_PATH . '/app/config.php';

// Ensure log directory exists and capture PHP errors
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0777, true);
}
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');

// Report errors based on environment
$displayErrors = (defined('APP_ENV') && APP_ENV === 'development');
error_reporting(E_ALL);
ini_set('display_errors', $displayErrors ? '1' : '0');

// Force HTTPS in production
if (defined('APP_ENV') && APP_ENV === 'production'
    && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')
    && (string)($_SERVER['SERVER_PORT'] ?? '') !== '443'
) {
    $redirectUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

// Session hardening
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$cookieParams = session_get_cookie_params();
session_name(SESSION_COOKIE_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the router
require BASE_PATH . '/app/router.php';
