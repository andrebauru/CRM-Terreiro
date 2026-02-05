<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use App\Models\BaseModel;

if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class Client extends BaseModel
{
    public function __construct()
    {
        parent::__construct('clients');
    }

    /**
     * Get all clients.
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all active clients.
     */
    public function allActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a client by ID.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new client.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                (name, email, phone, phone_secondary, whatsapp, address, city, state, zip_code, document, birth_date, source, notes, status, created_by)
                VALUES
                (:name, :email, :phone, :phone_secondary, :whatsapp, :address, :city, :state, :zip_code, :document, :birth_date, :source, :notes, :status, :created_by)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email'] ?? null);
        $stmt->bindParam(':phone', $data['phone'] ?? null);
        $stmt->bindParam(':phone_secondary', $data['phone_secondary'] ?? null);
        $stmt->bindParam(':whatsapp', $data['whatsapp'] ?? null);
        $stmt->bindParam(':address', $data['address'] ?? null);
        $stmt->bindParam(':city', $data['city'] ?? null);
        $stmt->bindParam(':state', $data['state'] ?? null);
        $stmt->bindParam(':zip_code', $data['zip_code'] ?? null);
        $stmt->bindParam(':document', $data['document'] ?? null);
        $stmt->bindParam(':birth_date', $data['birth_date'] ?? null);
        $stmt->bindParam(':source', $data['source'] ?? null);
        $stmt->bindParam(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindParam(':created_by', $data['created_by'] ?? null);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing client.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET
                name = :name,
                email = :email,
                phone = :phone,
                phone_secondary = :phone_secondary,
                whatsapp = :whatsapp,
                address = :address,
                city = :city,
                state = :state,
                zip_code = :zip_code,
                document = :document,
                birth_date = :birth_date,
                source = :source,
                notes = :notes,
                status = :status,
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email'] ?? null);
        $stmt->bindParam(':phone', $data['phone'] ?? null);
        $stmt->bindParam(':phone_secondary', $data['phone_secondary'] ?? null);
        $stmt->bindParam(':whatsapp', $data['whatsapp'] ?? null);
        $stmt->bindParam(':address', $data['address'] ?? null);
        $stmt->bindParam(':city', $data['city'] ?? null);
        $stmt->bindParam(':state', $data['state'] ?? null);
        $stmt->bindParam(':zip_code', $data['zip_code'] ?? null);
        $stmt->bindParam(':document', $data['document'] ?? null);
        $stmt->bindParam(':birth_date', $data['birth_date'] ?? null);
        $stmt->bindParam(':source', $data['source'] ?? null);
        $stmt->bindParam(':notes', $data['notes'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? 'active');
        $stmt->bindParam(':updated_by', $data['updated_by'] ?? null);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Delete a client.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get jobs for a specific client.
     */
    public function getJobs(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT j.*, s.name as service_name
            FROM jobs j
            LEFT JOIN services s ON j.service_id = s.id
            WHERE j.client_id = :client_id
            ORDER BY j.created_at DESC
        ");
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get client history.
     */
    public function getHistory(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT ch.*, u.name as user_name
            FROM client_history ch
            LEFT JOIN users u ON ch.user_id = u.id
            WHERE ch.client_id = :client_id
            ORDER BY ch.created_at DESC
            LIMIT 50
        ");
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search clients.
     */
    public function search(string $term): array
    {
        $term = '%' . $term . '%';
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE name LIKE :term
               OR email LIKE :term
               OR phone LIKE :term
               OR whatsapp LIKE :term
               OR document LIKE :term
            ORDER BY name ASC
            LIMIT 50
        ");
        $stmt->bindParam(':term', $term);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
