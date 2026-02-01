<?php

declare(strict_types=1);

namespace App\Helpers;

class Session
{
    /**
     * Initializes the session if it's not already started.
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Sets a session value.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to store.
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Gets a session value.
     *
     * @param string $key The key to retrieve.
     * @param mixed $default The default value if the key does not exist.
     * @return mixed The session value or the default value.
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a session key exists.
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public static function exists(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Deletes a session key.
     *
     * @param string $key The key to delete.
     */
    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroys the entire session.
     */
    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Sets a flash message.
     *
     * @param string $key The key for the flash message (e.g., 'success', 'error').
     * @param string $message The message to store.
     */
    public static function flash(string $key, string $message): void
    {
        self::set('flash_' . $key, $message);
    }

    /**
     * Gets and deletes a flash message.
     *
     * @param string $key The key for the flash message.
     * @param mixed $default The default value if the flash message does not exist.
     * @return string|null The flash message or null if not found.
     */
    public static function getFlash(string $key, $default = null): ?string
    {
        $flashKey = 'flash_' . $key;
        if (self::exists($flashKey)) {
            $message = self::get($flashKey);
            self::delete($flashKey);
            return (string) $message;
        }
        if ($default === null) {
            return null;
        }
        return (string) $default;
    }

    /**
     * Generates a CSRF token and stores it in the session.
     *
     * @return string The generated CSRF token.
     */
    public static function generateCsrfToken(): string
    {
        if (!self::exists('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    /**
     * Validates a given CSRF token against the one stored in the session.
     *
     * @param string $token The token to validate.
     * @return bool True if the token is valid, false otherwise.
     */
    public static function validateCsrfToken(string $token): bool
    {
        $sessionToken = self::get('csrf_token');
        // Ensure the token is a string and perform a secure comparison
        if (is_string($sessionToken) && hash_equals($sessionToken, $token)) {
            return true;
        }
        return false;
    }
}
