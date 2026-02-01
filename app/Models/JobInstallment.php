<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class JobInstallment extends BaseModel
{
    public function __construct()
    {
        parent::__construct('job_installments');
    }

    public function getByJobId(int $jobId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE job_id = :job_id ORDER BY installment_number ASC");
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByJobId(int $jobId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE job_id = :job_id");
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (job_id, installment_number, amount, due_date, status) VALUES (:job_id, :installment_number, :amount, :due_date, :status)"
        );
        $stmt->bindParam(':job_id', $data['job_id'], PDO::PARAM_INT);
        $stmt->bindParam(':installment_number', $data['installment_number'], PDO::PARAM_INT);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function ensureForJob(int $jobId, int $installments, ?float $amount, ?string $startDate = null): void
    {
        if ($installments < 1) {
            return;
        }

        $existing = $this->getByJobId($jobId);
        $existingNumbers = array_map(static fn($row) => (int)$row['installment_number'], $existing);
        $existingCount = count($existingNumbers);

        if ($existingCount >= $installments) {
            return;
        }

        $baseDate = $startDate ? new \DateTime($startDate) : null;

        for ($i = 1; $i <= $installments; $i++) {
            if (in_array($i, $existingNumbers, true)) {
                continue;
            }

            $dueDate = null;
            if ($baseDate) {
                $due = clone $baseDate;
                $due->modify('+' . ($i - 1) . ' month');
                $dueDate = $due->format('Y-m-d');
            }

            $this->create([
                'job_id' => $jobId,
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }
    }

    public function markPaid(int $installmentId, int $userId, ?float $amount = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET amount = COALESCE(:amount, amount), status = 'paid', paid_at = CURRENT_TIMESTAMP, paid_by = :paid_by, updated_at = CURRENT_TIMESTAMP WHERE id = :id"
        );
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':paid_by', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':id', $installmentId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
