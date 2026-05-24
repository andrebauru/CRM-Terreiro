<?php

declare(strict_types=1);

// Auto-migration runner for profile_pending_changes table
define('BASE_PATH', __DIR__);
require BASE_PATH . '/app/database.php';
require BASE_PATH . '/app/config.php';

try {
    $pdo = getPDOConnection();
    
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'profile_pending_changes'");
    if ($stmt->fetch()) {
        echo "✓ Tabela 'profile_pending_changes' já existe.\n";
        exit(0);
    }
    
    // Read and execute migration
    $migrationSql = file_get_contents(__DIR__ . '/database/migrate_2026_05_25_profile_pending_changes.sql');
    
    // Split into individual statements (remove comments)
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Migração executada com sucesso! Tabela 'profile_pending_changes' criada.\n";
    exit(0);
    
} catch (Throwable $e) {
    echo "✗ Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
}
