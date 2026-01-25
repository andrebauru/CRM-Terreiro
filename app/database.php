<?php

declare(strict_types=1);

// Ensure config.php is loaded for database credentials
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Establishes and returns a PDO database connection.
 *
 * @return PDO
 * @throws PDOException If the connection fails.
 */
function getPDOConnection(): PDO
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log error (to be implemented later)
        error_log("Database connection failed: " . $e->getMessage());
        // For development, re-throw or show a user-friendly error page
        throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
    }
}
