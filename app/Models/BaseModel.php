<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

if (!function_exists('getPDOConnection')) {
    require_once BASE_PATH . '/app/database.php';
}

abstract class BaseModel
{
    protected object $db;
    protected string $table;

    public function __construct(string $table)
    {
        $this->db = getPDOConnection();
        $this->table = $table;
    }

    /**
     * Counts the total number of records in the model's table.
     *
     * @return int The number of records.
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int)$stmt->fetchColumn();
    }
}