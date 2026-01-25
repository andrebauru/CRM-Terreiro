<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class JobAttachment extends BaseModel
{
    public function __construct()
    {
        parent::__construct('job_attachments');
    }

    /**
     * Get attachments for a specific job.
     *
     * @param int $jobId The ID of the job.
     * @return array An array of attachment data.
     */
    public function getByJobId(int $jobId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE job_id = :job_id ORDER BY created_at DESC");
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new job attachment.
     *
     * @param array $data Attachment data (job_id, user_id, filename, filepath, file_type, file_size).
     * @return int The ID of the newly created attachment.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (job_id, user_id, filename, filepath, file_type, file_size) VALUES (:job_id, :user_id, :filename, :filepath, :file_type, :file_size)");
        $stmt->bindParam(':job_id', $data['job_id'], PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':filename', $data['filename']);
        $stmt->bindParam(':filepath', $data['filepath']);
        $stmt->bindParam(':file_type', $data['file_type']);
        $stmt->bindParam(':file_size', $data['file_size'], PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Find an attachment by ID.
     *
     * @param int $id The attachment ID.
     * @return array|false The attachment data, or false if not found.
     */
    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete an attachment.
     *
     * @param int $id The attachment ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
