<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

// Autenticação obrigatória para backup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $pdo = db();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $sql = "-- CRM Terreiro Backup\n";
    $sql .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createStmt['Create Table'] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $columns = array_map(fn($col) => "`$col`", array_keys($row));
            $values = array_map(function ($value) use ($pdo) {
                if ($value === null) {
                    return 'NULL';
                }
                return $pdo->quote((string)$value);
            }, array_values($row));
            $sql .= "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
        }
        $sql .= "\n";
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup_crm_terreiro.sql"');
    header('Cache-Control: no-store');
    echo $sql;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro ao gerar backup: ' . $e->getMessage();
}
