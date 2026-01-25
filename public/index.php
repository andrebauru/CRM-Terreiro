<?php

declare(strict_types=1);

// Report all errors for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload Composer dependencies
require BASE_PATH . '/vendor/autoload.php';

// Include configuration
require BASE_PATH . '/app/config.php';

// Include the router
require BASE_PATH . '/app/router.php';
