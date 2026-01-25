<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

// Ensure database connection function is available
if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getPDOConnection();
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address of the user.
     * @return array|false The user data as an associative array, or false if not found.
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a user by their ID.
     *
     * @param int $id The ID of the user.
     * @return array|false The user data as an associative array, or false if not found.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifies a given password against the hashed password.
     *
     * @param string $password The plain text password.
     * @param string $hashedPassword The hashed password from the database.
     * @return bool True if the password matches, false otherwise.
     */
    public function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Get all active users (excluding password).
     *
     * @return array An array of user data.
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, role FROM users WHERE is_active = TRUE ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
