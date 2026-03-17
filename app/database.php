<?php

declare(strict_types=1);

use App\Helpers\Logger;
use App\Helpers\MySQLiPDO;

// Ensure config.php is loaded for database credentials
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// Load the MySQLi wrapper
require_once __DIR__ . '/Helpers/Database.php';

/**
 * Establishes and returns a database connection (PDO or MySQLi wrapper).
 *
 * @return object
 */
function getPDOConnection(): object
{
    // Check if PDO and pdo_mysql are available
    if (extension_loaded('pdo_mysql')) {
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            Logger::error("PDO connection failed: " . $e->getMessage());
            // Fallback to MySQLi if PDO fails with "could not find driver"
            if (strpos($e->getMessage(), 'could not find driver') === false) {
                throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
            }
        }
    }

    // Fallback to MySQLi wrapper for portability
    try {
        return new MySQLiPDO(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (\Exception $e) {
        Logger::error("MySQLi connection failed: " . $e->getMessage());
        throw new \Exception("Database connection failed: " . $e->getMessage());
    }
}
