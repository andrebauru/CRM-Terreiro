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

class Service extends BaseModel
{
    public function __construct()
    {
        parent::__construct('services');
    }

    /**
     * Get all services.
     *
     * @return array An array of service data.
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a service by ID.
     *
     * @param int $id The service ID.
     * @return array|false The service data, or false if not found.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new service.
     *
     * @param array $data Service data (name, description, price, is_active).
     * @return int The ID of the newly created service.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (name, description, price, is_active) VALUES (:name, :description, :price, :is_active)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_BOOL);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing service.
     *
     * @param int $id The service ID.
     * @param array $data Service data (name, description, price, is_active).
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = :name, description = :description, price = :price, is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a service.
     *
     * @param int $id The service ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}