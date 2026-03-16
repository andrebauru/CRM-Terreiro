<?php
/**
 * Database Dump Generator
 * Acesse via browser no servidor: http://<seu-servidor>/dump_db.php
 * Apague este arquivo após usar!
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo    = db();
$dbName = $_ENV['DB_NAME'] ?? 'crm_quimbanda';
$date   = date('Y-m-d_His');
$file   = __DIR__ . "/database/dump_{$date}.sql";

if (!is_dir(__DIR__ . '/database')) {
    mkdir(__DIR__ . '/database', 0755, true);
}

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$sql  = "-- CRM Terreiro - Database Dump\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Database: {$dbName}\n\n";
$sql .= "SET NAMES utf8mb4;\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $ddl    = $create['Create Table'] ?? array_values($create)[1];

    $sql .= "-- ----------------------------\n-- Table: {$table}\n-- ----------------------------\n";
    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $sql .= $ddl . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
        $chunks = array_chunk($rows, 300);
        foreach ($chunks as $chunk) {
            $vals = [];
            foreach ($chunk as $row) {
                $esc = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row);
                $vals[] = '(' . implode(', ', $esc) . ')';
            }
            $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $vals) . ";\n";
        }
        $sql .= "\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($file, $sql);
$size = number_format(filesize($file));
$name = basename($file);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Dump</title></head><body>";
echo "<h2>✅ Dump gerado com sucesso!</h2>";
echo "<p><strong>Arquivo:</strong> database/{$name}</p>";
echo "<p><strong>Tamanho:</strong> {$size} bytes</p>";
echo "<p><a href='database/{$name}' download>⬇️ Baixar SQL</a></p>";
echo "<p style='color:red'><strong>⚠️ Apague este arquivo (dump_db.php) após baixar o dump!</strong></p>";
echo "</body></html>";
