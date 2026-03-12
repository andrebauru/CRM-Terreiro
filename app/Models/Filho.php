<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Filho extends BaseModel
{
    public function __construct()
    {
        parent::__construct('filhos');
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE status = 'ativo' ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO filhos (name, email, phone, grade, grade_date, mensalidade_value, due_day,
             notes_evolucao, anotacoes, entidade_frente, orixa_pai, orixa_mae)
             VALUES (:name, :email, :phone, :grade, :grade_date, :mensalidade_value, :due_day,
             :notes_evolucao, :anotacoes, :entidade_frente, :orixa_pai, :orixa_mae)'
        );
        $stmt->execute([
            ':name'              => $data['name'],
            ':email'             => $data['email'] ?? null,
            ':phone'             => $data['phone'] ?? null,
            ':grade'             => $data['grade'] ?? 'Iniciação',
            ':grade_date'        => $data['grade_date'] ?? date('Y-m-d'),
            ':mensalidade_value' => (int)($data['mensalidade_value'] ?? 0),
            ':due_day'           => (int)($data['due_day'] ?? 5),
            ':notes_evolucao'    => $data['notes_evolucao'] ?? null,
            ':anotacoes'         => $data['anotacoes'] ?? null,
            ':entidade_frente'   => $data['entidade_frente'] ?? null,
            ':orixa_pai'         => $data['orixa_pai'] ?? null,
            ':orixa_mae'         => $data['orixa_mae'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $gradeDate = !empty($data['grade_date']) ? ', grade_date = :grade_date' : '';
        $sql = "UPDATE {$this->table} SET
            name = :name, email = :email, phone = :phone, grade = :grade {$gradeDate},
            status = :status, saiu_at = :saiu_at, mensalidade_value = :mensalidade_value,
            due_day = :due_day, notes_evolucao = :notes_evolucao, anotacoes = :anotacoes,
            entidade_frente = :entidade_frente, orixa_pai = :orixa_pai, orixa_mae = :orixa_mae
            WHERE id = :id";

        $params = [
            ':id'                => $id,
            ':name'              => $data['name'],
            ':email'             => $data['email'] ?? null,
            ':phone'             => $data['phone'] ?? null,
            ':grade'             => $data['grade'] ?? 'Iniciação',
            ':status'            => $data['status'] ?? 'ativo',
            ':saiu_at'           => $data['saiu_at'] ?? null,
            ':mensalidade_value' => (int)($data['mensalidade_value'] ?? 0),
            ':due_day'           => (int)($data['due_day'] ?? 5),
            ':notes_evolucao'    => $data['notes_evolucao'] ?? null,
            ':anotacoes'         => $data['anotacoes'] ?? null,
            ':entidade_frente'   => $data['entidade_frente'] ?? null,
            ':orixa_pai'         => $data['orixa_pai'] ?? null,
            ':orixa_mae'         => $data['orixa_mae'] ?? null,
        ];
        if (!empty($data['grade_date'])) {
            $params[':grade_date'] = $data['grade_date'];
        }
        return $this->db->prepare($sql)->execute($params);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE name LIKE :term OR phone LIKE :term OR entidade_frente LIKE :term
             ORDER BY name ASC LIMIT 50"
        );
        $stmt->execute([':term' => $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
