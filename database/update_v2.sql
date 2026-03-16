-- =====================================================
-- CRM TERREIRO - SCRIPT DE ATUALIZAÇÃO v2.0
-- =====================================================
-- Compatível com MySQL 5.7+ e MariaDB
-- Pode ser executado múltiplas vezes com segurança
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- ATUALIZAÇÃO DA TABELA: USERS
-- =====================================================
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login_at') = 0,
    'ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER is_active',
    'SELECT "Coluna last_login_at já existe em users"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ATUALIZAÇÃO DA TABELA: SETTINGS
-- =====================================================
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'client_name') = 0,
    'ALTER TABLE settings ADD COLUMN client_name VARCHAR(255) NULL',
    'SELECT "Coluna client_name já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'company_name') = 0,
    'ALTER TABLE settings ADD COLUMN company_name VARCHAR(255) NULL',
    'SELECT "Coluna company_name já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'logo_path') = 0,
    'ALTER TABLE settings ADD COLUMN logo_path VARCHAR(512) NULL',
    'SELECT "Coluna logo_path já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'currency_code') = 0,
    'ALTER TABLE settings ADD COLUMN currency_code VARCHAR(3) DEFAULT "JPY"',
    'SELECT "Coluna currency_code já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'currency_symbol') = 0,
    'ALTER TABLE settings ADD COLUMN currency_symbol VARCHAR(8) DEFAULT "¥"',
    'SELECT "Coluna currency_symbol já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'timezone') = 0,
    'ALTER TABLE settings ADD COLUMN timezone VARCHAR(64) DEFAULT "Asia/Tokyo"',
    'SELECT "Coluna timezone já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ATUALIZAÇÃO DA TABELA: CLIENTS
-- =====================================================

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'phone_secondary') = 0,
    'ALTER TABLE clients ADD COLUMN phone_secondary VARCHAR(50) NULL COMMENT "Telefone secundário" AFTER phone',
    'SELECT "Coluna phone_secondary já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'whatsapp') = 0,
    'ALTER TABLE clients ADD COLUMN whatsapp VARCHAR(50) NULL COMMENT "Número do WhatsApp" AFTER phone_secondary',
    'SELECT "Coluna whatsapp já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'whatsapp_link') = 0,
    'ALTER TABLE clients ADD COLUMN whatsapp_link VARCHAR(255) NULL COMMENT "Link do WhatsApp" AFTER whatsapp',
    'SELECT "Coluna whatsapp_link já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'city') = 0,
    'ALTER TABLE clients ADD COLUMN city VARCHAR(100) NULL AFTER address',
    'SELECT "Coluna city já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'state') = 0,
    'ALTER TABLE clients ADD COLUMN state VARCHAR(50) NULL AFTER city',
    'SELECT "Coluna state já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'zip_code') = 0,
    'ALTER TABLE clients ADD COLUMN zip_code VARCHAR(20) NULL AFTER state',
    'SELECT "Coluna zip_code já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'document') = 0,
    'ALTER TABLE clients ADD COLUMN document VARCHAR(20) NULL COMMENT "CPF ou CNPJ" AFTER zip_code',
    'SELECT "Coluna document já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'birth_date') = 0,
    'ALTER TABLE clients ADD COLUMN birth_date DATE NULL AFTER document',
    'SELECT "Coluna birth_date já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'source') = 0,
    'ALTER TABLE clients ADD COLUMN source VARCHAR(100) NULL COMMENT "Origem do cliente" AFTER birth_date',
    'SELECT "Coluna source já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'notes') = 0,
    'ALTER TABLE clients ADD COLUMN notes TEXT NULL COMMENT "Observações" AFTER source',
    'SELECT "Coluna notes já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'status') = 0,
    'ALTER TABLE clients ADD COLUMN status ENUM("active", "inactive", "blocked") NOT NULL DEFAULT "active" AFTER notes',
    'SELECT "Coluna status já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'created_by') = 0,
    'ALTER TABLE clients ADD COLUMN created_by INT NULL',
    'SELECT "Coluna created_by já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'updated_by') = 0,
    'ALTER TABLE clients ADD COLUMN updated_by INT NULL',
    'SELECT "Coluna updated_by já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ATUALIZAÇÃO DA TABELA: SERVICES
-- =====================================================
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND COLUMN_NAME = 'duration_minutes') = 0,
    'ALTER TABLE services ADD COLUMN duration_minutes INT NULL COMMENT "Duração em minutos" AFTER price',
    'SELECT "Coluna duration_minutes já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ATUALIZAÇÃO DA TABELA: JOBS
-- =====================================================
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'channel') = 0,
    'ALTER TABLE jobs ADD COLUMN channel VARCHAR(100) NULL COMMENT "Canal de contato" AFTER priority',
    'SELECT "Coluna channel já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'total_value') = 0,
    'ALTER TABLE jobs ADD COLUMN total_value DECIMAL(10, 2) NULL COMMENT "Valor total" AFTER completed_at',
    'SELECT "Coluna total_value já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'installments') = 0,
    'ALTER TABLE jobs ADD COLUMN installments INT NOT NULL DEFAULT 1 AFTER total_value',
    'SELECT "Coluna installments já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'installment_value') = 0,
    'ALTER TABLE jobs ADD COLUMN installment_value DECIMAL(10, 2) NULL AFTER installments',
    'SELECT "Coluna installment_value já existe"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- CRIAR TABELA: CLIENT_HISTORY
-- =====================================================
CREATE TABLE IF NOT EXISTS client_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    user_id INT NULL COMMENT 'Usuário que fez a alteração',
    action ENUM('created', 'updated', 'deleted', 'status_changed') NOT NULL,
    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    data_snapshot TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_history_client_id (client_id),
    INDEX idx_client_history_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CRIAR TABELA: JOB_HISTORY
-- =====================================================
CREATE TABLE IF NOT EXISTS job_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NULL,
    action ENUM('created', 'updated', 'deleted', 'status_changed', 'assigned', 'note_added', 'attachment_added') NOT NULL,
    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_history_job_id (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CRIAR TABELA: JOB_NOTES
-- =====================================================
CREATE TABLE IF NOT EXISTS job_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_notes_job_id (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CRIAR TABELA: JOB_ATTACHMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS job_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(512) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_attachments_job_id (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CRIAR TABELA: JOB_INSTALLMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS job_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    installment_number INT NOT NULL,
    amount DECIMAL(10, 2) NULL,
    due_date DATE NULL,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    paid_by INT NULL,
    payment_method VARCHAR(50) NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_job_installment (job_id, installment_number),
    INDEX idx_job_installments_job_id (job_id),
    INDEX idx_job_installments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FUNÇÃO PARA LIMPAR NÚMERO (compatível com MySQL 5.7)
-- =====================================================
DROP FUNCTION IF EXISTS fn_clean_phone_number;

DELIMITER //

CREATE FUNCTION fn_clean_phone_number(phone VARCHAR(50))
RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    DECLARE clean_number VARCHAR(50);
    SET clean_number = phone;
    -- Remove caracteres comuns de formatação
    SET clean_number = REPLACE(clean_number, ' ', '');
    SET clean_number = REPLACE(clean_number, '-', '');
    SET clean_number = REPLACE(clean_number, '(', '');
    SET clean_number = REPLACE(clean_number, ')', '');
    SET clean_number = REPLACE(clean_number, '.', '');
    SET clean_number = REPLACE(clean_number, '+', '');
    RETURN clean_number;
END//

DELIMITER ;

-- =====================================================
-- CRIAR TRIGGERS
-- =====================================================
DROP TRIGGER IF EXISTS before_client_insert;
DROP TRIGGER IF EXISTS before_client_update;
DROP TRIGGER IF EXISTS after_client_insert;
DROP TRIGGER IF EXISTS after_client_update;
DROP TRIGGER IF EXISTS after_job_insert;
DROP TRIGGER IF EXISTS after_job_update;

DELIMITER //

-- Trigger: Gerar link do WhatsApp (INSERT)
CREATE TRIGGER before_client_insert
BEFORE INSERT ON clients
FOR EACH ROW
BEGIN
    IF NEW.whatsapp IS NOT NULL AND NEW.whatsapp != '' THEN
        SET NEW.whatsapp_link = CONCAT('https://wa.me/', fn_clean_phone_number(NEW.whatsapp));
    END IF;
END//

-- Trigger: Gerar link do WhatsApp (UPDATE)
CREATE TRIGGER before_client_update
BEFORE UPDATE ON clients
FOR EACH ROW
BEGIN
    IF NEW.whatsapp IS NOT NULL AND NEW.whatsapp != '' THEN
        SET NEW.whatsapp_link = CONCAT('https://wa.me/', fn_clean_phone_number(NEW.whatsapp));
    ELSE
        SET NEW.whatsapp_link = NULL;
    END IF;
END//

-- Trigger: Registrar criação de cliente
CREATE TRIGGER after_client_insert
AFTER INSERT ON clients
FOR EACH ROW
BEGIN
    INSERT INTO client_history (client_id, user_id, action, notes)
    VALUES (NEW.id, NEW.created_by, 'created', 'Cliente criado');
END//

-- Trigger: Registrar alterações de cliente
CREATE TRIGGER after_client_update
AFTER UPDATE ON clients
FOR EACH ROW
BEGIN
    IF OLD.name != NEW.name THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'name', OLD.name, NEW.name);
    END IF;

    IF IFNULL(OLD.email, '') != IFNULL(NEW.email, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'email', OLD.email, NEW.email);
    END IF;

    IF IFNULL(OLD.phone, '') != IFNULL(NEW.phone, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'phone', OLD.phone, NEW.phone);
    END IF;

    IF IFNULL(OLD.whatsapp, '') != IFNULL(NEW.whatsapp, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'whatsapp', OLD.whatsapp, NEW.whatsapp);
    END IF;

    IF OLD.status != NEW.status THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'status_changed', 'status', OLD.status, NEW.status);
    END IF;

    IF IFNULL(OLD.notes, '') != IFNULL(NEW.notes, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'notes', LEFT(OLD.notes, 500), LEFT(NEW.notes, 500));
    END IF;
END//

-- Trigger: Registrar criação de job
CREATE TRIGGER after_job_insert
AFTER INSERT ON jobs
FOR EACH ROW
BEGIN
    INSERT INTO job_history (job_id, user_id, action, notes)
    VALUES (NEW.id, NEW.created_by, 'created', CONCAT('Trabalho criado: ', NEW.title));
END//

-- Trigger: Registrar alterações de job
CREATE TRIGGER after_job_update
AFTER UPDATE ON jobs
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO job_history (job_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.assigned_to, 'status_changed', 'status', OLD.status, NEW.status);
    END IF;

    IF IFNULL(OLD.assigned_to, 0) != IFNULL(NEW.assigned_to, 0) THEN
        INSERT INTO job_history (job_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.assigned_to, 'assigned', 'assigned_to',
                CAST(OLD.assigned_to AS CHAR), CAST(NEW.assigned_to AS CHAR));
    END IF;

    IF OLD.priority != NEW.priority THEN
        INSERT INTO job_history (job_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.assigned_to, 'updated', 'priority', OLD.priority, NEW.priority);
    END IF;
END//

DELIMITER ;

-- =====================================================
-- ATUALIZAR LINKS DO WHATSAPP EXISTENTES
-- =====================================================
UPDATE clients
SET whatsapp_link = CONCAT('https://wa.me/', fn_clean_phone_number(whatsapp))
WHERE whatsapp IS NOT NULL AND whatsapp != '' AND (whatsapp_link IS NULL OR whatsapp_link = '');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RESUMO
-- =====================================================
SELECT '========================================' AS '';
SELECT 'ATUALIZAÇÃO v2.0 CONCLUÍDA!' AS Status;
SELECT '========================================' AS '';
