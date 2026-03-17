-- ============================================================
-- Migration: Registro de Giras + Language setting
-- ============================================================

-- Add language column to settings
SET @dbname = DATABASE();
SET @tablename = 'settings';
SET @columnname = 'language';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) = 0,
  'ALTER TABLE settings ADD COLUMN language VARCHAR(5) NOT NULL DEFAULT ''pt''',
  'SELECT ''Coluna language já existe'''
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Tipos de Gira (combobox values)
CREATE TABLE IF NOT EXISTS tipos_gira (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de Giras (campanhas em redes sociais)
CREATE TABLE IF NOT EXISTS giras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_gira_id INT NOT NULL,
    plataforma ENUM('Facebook','Instagram','TikTok') NOT NULL DEFAULT 'Instagram',
    foto_path VARCHAR(512) NULL,
    data_postagem DATE NULL,
    data_realizacao DATE NOT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tipo_gira_id) REFERENCES tipos_gira(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default gira types
INSERT IGNORE INTO tipos_gira (nome) VALUES
  ('Gira de Exu'),
  ('Gira de Preto Velho'),
  ('Gira de Caboclo'),
  ('Gira de Erê'),
  ('Gira de Boiadeiro'),
  ('Gira de Marinheiro'),
  ('Gira de Cigana'),
  ('Gira de Baiano'),
  ('Gira Geral');
