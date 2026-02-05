<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use App\Models\BaseModel;

// Ensure database connection function is available
if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class User extends BaseModel
{
    public function __construct()
    {
        parent::__construct('users');
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address of the user.
     * @return array|false The user data as an associative array, or false if not found.
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
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
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all users (excluding password).
     *
     * @return array An array of user data.
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, role, is_active, created_at FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Create a new user.
     *
     * @param array $data User data (name, email, password, role). Password will be hashed.
     * @return int The ID of the newly created user.
     */
    public function create(array $data): int
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $data['role']);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing user.
     *
     * @param int $id The user ID.
     * @param array $data User data (name, email, role, password (optional)).
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET name = :name, email = :email, role = :role, updated_at = CURRENT_TIMESTAMP";
        if (isset($data['password']) && !empty($data['password'])) {
            $sql .= ", password = :password";
        }
        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':role', $data['role']);
        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $hashedPassword);
        }
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a user.
     *
     * @param int $id The user ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}