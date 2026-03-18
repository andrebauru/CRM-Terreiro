-- ============================================================
-- Migration: Add isento_mensalidade to filhos
-- ============================================================

SET @dbname = DATABASE();
SET @tablename = 'filhos';
SET @columnname = 'isento_mensalidade';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) = 0,
  'ALTER TABLE filhos ADD COLUMN isento_mensalidade TINYINT(1) NOT NULL DEFAULT 0 AFTER due_day',
  'SELECT ''Coluna isento_mensalidade já existe'''
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
