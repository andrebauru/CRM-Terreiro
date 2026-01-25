<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

// Ensure database connection function is available
if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class Client
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getPDOConnection();
    }

    /**
     * Get all clients.
     *
     * @return array An array of client data.
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM clients ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a client by ID.
     *
     * @param int $id The client ID.
     * @return array|false The client data, or false if not found.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new client.
     *
     * @param array $data Client data (name, email, phone, address).
     * @return int The ID of the newly created client.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO clients (name, email, phone, address) VALUES (:name, :email, :phone, :address)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing client.
     *
     * @param int $id The client ID.
     * @param array $data Client data (name, email, phone, address).
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE clients SET name = :name, email = :email, phone = :phone, address = :address, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a client.
     *
     * @param int $id The client ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
