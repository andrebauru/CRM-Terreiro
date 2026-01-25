<?php

declare(strict_types=1);

namespace App\Helpers;

class Logger
{
    private static string $logFile = LOG_PATH . '/app.log';

    /**
     * Writes a log message to the log file.
     *
     * @param string $message The message to log.
     * @param string $level The log level (e.g., INFO, WARNING, ERROR).
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        if (!is_dir(LOG_PATH)) {
            mkdir(LOG_PATH, 0777, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Logs an informational message.
     *
     * @param string $message
     */
    public static function info(string $message): void
    {
        self::log($message, 'INFO');
    }

    /**
     * Logs a warning message.
     *
     * @param string $message
     */
    public static function warning(string $message): void
    {
        self::log($message, 'WARNING');
    }

    /**
     * Logs an error message.
     *
     * @param string $message
     */
    public static function error(string $message): void
    {
        self::log($message, 'ERROR');
    }
}
