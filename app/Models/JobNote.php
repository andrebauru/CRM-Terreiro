<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class JobNote extends BaseModel
{
    public function __construct()
    {
        parent::__construct('job_notes');
    }

    /**
     * Get all notes for a specific job.
     *
     * @param int $jobId The ID of the job.
     * @return array An array of note data, including user name.
     */
    public function getByJobId(int $jobId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                jn.*,
                u.name as user_name
            FROM {$this->table} jn
            JOIN users u ON jn.user_id = u.id
            WHERE jn.job_id = :job_id
            ORDER BY jn.created_at DESC
        ");
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new job note.
     *
     * @param array $data Note data (job_id, user_id, note).
     * @return int The ID of the newly created note.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (job_id, user_id, note) VALUES (:job_id, :user_id, :note)");
        $stmt->bindParam(':job_id', $data['job_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':note', $data['note']);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Find a note by ID.
     *
     * @param int $id The note ID.
     * @return array|false The note data, or false if not found.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update an existing note.
     *
     * @param int $id The note ID.
     * @param string $note The new note content.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, string $note): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET note = :note, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->bindParam(':note', $note);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a note.
     *
     * @param int $id The note ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
