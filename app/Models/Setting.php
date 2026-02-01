<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

class Setting extends BaseModel
{
    public function __construct()
    {
        parent::__construct('settings');
    }

    public function get(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->db->exec("INSERT INTO {$this->table} (client_name, company_name, logo_path) VALUES ('', '', NULL)");
            $id = (int)$this->db->lastInsertId();
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $row ?: [
            'client_name' => '',
            'company_name' => '',
            'logo_path' => null,
        ];
    }

    public function updateSettings(array $data): bool
    {
        $current = $this->get();
        $id = (int)($current['id'] ?? 0);
        if ($id === 0) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET client_name = :client_name, company_name = :company_name, logo_path = :logo_path, updated_at = CURRENT_TIMESTAMP WHERE id = :id"
        );
        $stmt->bindParam(':client_name', $data['client_name']);
        $stmt->bindParam(':company_name', $data['company_name']);
        $stmt->bindParam(':logo_path', $data['logo_path']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
