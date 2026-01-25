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

class Job extends BaseModel
{
    public function __construct()
    {
        parent::__construct('jobs');
    }

    /**
     * Get all jobs with related client and service information.
     *
     * @return array An array of job data.
     */
    public function all(): array
    {
        $stmt = $this->db->query("
            SELECT
                j.*,
                c.name as client_name,
                s.name as service_name,
                u_created.name as created_by_name,
                u_assigned.name as assigned_to_name
            FROM {$this->table} j
            JOIN clients c ON j.client_id = c.id
            JOIN services s ON j.service_id = s.id
            JOIN users u_created ON j.created_by = u_created.id
            LEFT JOIN users u_assigned ON j.assigned_to = u_assigned.id
            ORDER BY j.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a job by ID with related client and service information.
     *
     * @param int $id The job ID.
     * @return array|false The job data, or false if not found.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                j.*,
                c.name as client_name,
                s.name as service_name,
                u_created.name as created_by_name,
                u_assigned.name as assigned_to_name
            FROM {$this->table} j
            JOIN clients c ON j.client_id = c.id
            JOIN services s ON j.service_id = s.id
            JOIN users u_created ON j.created_by = u_created.id
            LEFT JOIN users u_assigned ON j.assigned_to = u_assigned.id
            WHERE j.id = :id
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Counts the number of jobs with a specific status.
     *
     * @param string $status The status to count (pending, in_progress, completed, cancelled).
     * @return int The number of jobs with the specified status.
     */
    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = :status");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a new job.
     *
     * @param array $data Job data (client_id, service_id, title, description, status, priority, channel, start_date, due_date, created_by, assigned_to).
     * @return int The ID of the newly created job.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (client_id, service_id, title, description, status, priority, channel, start_date, due_date, created_by, assigned_to)
            VALUES (:client_id, :service_id, :title, :description, :status, :priority, :channel, :start_date, :due_date, :created_by, :assigned_to)
        ");
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':service_id', $data['service_id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':channel', $data['channel']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':created_by', $data['created_by'], PDO::PARAM_INT);
        $stmt->bindParam(':assigned_to', $data['assigned_to'], PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing job.
     *
     * @param int $id The job ID.
     * @param array $data Job data.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                client_id = :client_id,
                service_id = :service_id,
                title = :title,
                description = :description,
                status = :status,
                priority = :priority,
                channel = :channel,
                start_date = :start_date,
                due_date = :due_date,
                assigned_to = :assigned_to,
                completed_at = :completed_at,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':service_id', $data['service_id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':channel', $data['channel']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':assigned_to', $data['assigned_to'], PDO::PARAM_INT);
        $stmt->bindParam(':completed_at', $data['completed_at']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a job.
     *
     * @param int $id The job ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}