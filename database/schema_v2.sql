-- =====================================================
-- CRM TERREIRO - SCHEMA v2.0
-- Execute este script no phpMyAdmin para criar o banco
-- =====================================================

-- Configurações iniciais
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================
-- REMOVER TABELAS EXISTENTES (cuidado em produção!)
-- =====================================================
DROP TABLE IF EXISTS client_history;
DROP TABLE IF EXISTS job_history;
DROP TABLE IF EXISTS job_attachments;
DROP TABLE IF EXISTS job_installments;
DROP TABLE IF EXISTS job_notes;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;

-- =====================================================
-- TABELA: USUÁRIOS DO SISTEMA
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: CONFIGURAÇÕES DO SISTEMA
-- =====================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255),
    company_name VARCHAR(255),
    logo_path VARCHAR(512),
    currency_code VARCHAR(3) NOT NULL DEFAULT 'BRL',
    currency_symbol VARCHAR(8) NOT NULL DEFAULT 'R$',
    timezone VARCHAR(64) NOT NULL DEFAULT 'America/Sao_Paulo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: CLIENTES
-- =====================================================
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Informações básicas
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,

    -- Telefones
    phone VARCHAR(50) NULL COMMENT 'Telefone principal',
    phone_secondary VARCHAR(50) NULL COMMENT 'Telefone secundário',
    whatsapp VARCHAR(50) NULL COMMENT 'Número do WhatsApp (com código do país)',
    whatsapp_link VARCHAR(255) NULL COMMENT 'Link direto para WhatsApp (gerado automaticamente)',

    -- Endereço
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(50) NULL,
    zip_code VARCHAR(20) NULL,

    -- Informações adicionais
    document VARCHAR(20) NULL COMMENT 'CPF ou CNPJ',
    birth_date DATE NULL,
    source VARCHAR(100) NULL COMMENT 'Origem do cliente (indicação, site, redes sociais, etc.)',

    -- Observações e notas
    notes TEXT NULL COMMENT 'Observações gerais sobre o cliente',

    -- Status e controle
    status ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: HISTÓRICO DE ALTERAÇÕES DE CLIENTES
-- =====================================================
CREATE TABLE client_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    user_id INT NULL COMMENT 'Usuário que fez a alteração',
    action ENUM('created', 'updated', 'deleted', 'status_changed') NOT NULL,

    -- Dados da alteração
    field_changed VARCHAR(100) NULL COMMENT 'Campo que foi alterado',
    old_value TEXT NULL COMMENT 'Valor anterior',
    new_value TEXT NULL COMMENT 'Novo valor',

    -- Snapshot completo (opcional, para restauração)
    data_snapshot JSON NULL COMMENT 'Snapshot completo dos dados no momento da alteração',

    -- Informações adicionais
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    notes VARCHAR(500) NULL COMMENT 'Observações sobre a alteração',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: SERVIÇOS
-- =====================================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    duration_minutes INT NULL COMMENT 'Duração estimada em minutos',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: TRABALHOS/JOBS
-- =====================================================
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_id INT NOT NULL,

    -- Informações do trabalho
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,

    -- Status e prioridade
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'on_hold') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',

    -- Canal de origem
    channel VARCHAR(100) NULL COMMENT 'Canal de contato: email, phone, whatsapp, in_person, website',

    -- Datas
    start_date DATE NULL,
    due_date DATE NULL,
    completed_at TIMESTAMP NULL,

    -- Financeiro
    total_value DECIMAL(10, 2) NULL COMMENT 'Valor total do trabalho',
    installments INT NOT NULL DEFAULT 1,
    installment_value DECIMAL(10, 2) NULL,

    -- Responsáveis
    created_by INT NOT NULL,
    assigned_to INT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: HISTÓRICO DE ALTERAÇÕES DE TRABALHOS
-- =====================================================
CREATE TABLE job_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NULL,
    action ENUM('created', 'updated', 'deleted', 'status_changed', 'assigned', 'note_added', 'attachment_added') NOT NULL,

    field_changed VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,

    notes VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: NOTAS DOS TRABALHOS
-- =====================================================
CREATE TABLE job_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE COMMENT 'Se true, não será visível para o cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: ANEXOS DOS TRABALHOS
-- =====================================================
CREATE TABLE job_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(512) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT NULL COMMENT 'Tamanho em bytes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: PARCELAS DOS TRABALHOS
-- =====================================================
CREATE TABLE job_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    installment_number INT NOT NULL,
    amount DECIMAL(10, 2) NULL,
    due_date DATE NULL,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    paid_by INT NULL,
    payment_method VARCHAR(50) NULL COMMENT 'dinheiro, pix, cartao_credito, cartao_debito, boleto',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_job_installment (job_id, installment_number),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICES PARA PERFORMANCE
-- =====================================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_clients_email ON clients(email);
CREATE INDEX idx_clients_phone ON clients(phone);
CREATE INDEX idx_clients_whatsapp ON clients(whatsapp);
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_clients_created_at ON clients(created_at);
CREATE INDEX idx_client_history_client_id ON client_history(client_id);
CREATE INDEX idx_client_history_created_at ON client_history(created_at);
CREATE INDEX idx_jobs_client_id ON jobs(client_id);
CREATE INDEX idx_jobs_service_id ON jobs(service_id);
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_jobs_created_by ON jobs(created_by);
CREATE INDEX idx_jobs_assigned_to ON jobs(assigned_to);
CREATE INDEX idx_jobs_due_date ON jobs(due_date);
CREATE INDEX idx_job_history_job_id ON job_history(job_id);
CREATE INDEX idx_job_notes_job_id ON job_notes(job_id);
CREATE INDEX idx_job_attachments_job_id ON job_attachments(job_id);
CREATE INDEX idx_job_installments_job_id ON job_installments(job_id);
CREATE INDEX idx_job_installments_status ON job_installments(status);

-- =====================================================
-- TRIGGER: GERAR LINK DO WHATSAPP AUTOMATICAMENTE
-- =====================================================
DELIMITER //

CREATE TRIGGER before_client_insert
BEFORE INSERT ON clients
FOR EACH ROW
BEGIN
    IF NEW.whatsapp IS NOT NULL AND NEW.whatsapp != '' THEN
        -- Remove caracteres não numéricos
        SET @clean_number = REGEXP_REPLACE(NEW.whatsapp, '[^0-9]', '');
        -- Gera o link do WhatsApp
        SET NEW.whatsapp_link = CONCAT('https://wa.me/', @clean_number);
    END IF;
END//

CREATE TRIGGER before_client_update
BEFORE UPDATE ON clients
FOR EACH ROW
BEGIN
    IF NEW.whatsapp IS NOT NULL AND NEW.whatsapp != '' THEN
        SET @clean_number = REGEXP_REPLACE(NEW.whatsapp, '[^0-9]', '');
        SET NEW.whatsapp_link = CONCAT('https://wa.me/', @clean_number);
    ELSE
        SET NEW.whatsapp_link = NULL;
    END IF;
END//

-- =====================================================
-- TRIGGER: REGISTRAR HISTÓRICO DE ALTERAÇÕES (CLIENTES)
-- =====================================================
CREATE TRIGGER after_client_insert
AFTER INSERT ON clients
FOR EACH ROW
BEGIN
    INSERT INTO client_history (client_id, user_id, action, notes, data_snapshot)
    VALUES (NEW.id, NEW.created_by, 'created', 'Cliente criado',
            JSON_OBJECT(
                'name', NEW.name,
                'email', NEW.email,
                'phone', NEW.phone,
                'whatsapp', NEW.whatsapp
            ));
END//

CREATE TRIGGER after_client_update
AFTER UPDATE ON clients
FOR EACH ROW
BEGIN
    -- Registra mudança de nome
    IF OLD.name != NEW.name THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'name', OLD.name, NEW.name);
    END IF;

    -- Registra mudança de email
    IF IFNULL(OLD.email, '') != IFNULL(NEW.email, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'email', OLD.email, NEW.email);
    END IF;

    -- Registra mudança de telefone
    IF IFNULL(OLD.phone, '') != IFNULL(NEW.phone, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'phone', OLD.phone, NEW.phone);
    END IF;

    -- Registra mudança de whatsapp
    IF IFNULL(OLD.whatsapp, '') != IFNULL(NEW.whatsapp, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'whatsapp', OLD.whatsapp, NEW.whatsapp);
    END IF;

    -- Registra mudança de status
    IF OLD.status != NEW.status THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'status_changed', 'status', OLD.status, NEW.status);
    END IF;

    -- Registra mudança de notas
    IF IFNULL(OLD.notes, '') != IFNULL(NEW.notes, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'notes',
                LEFT(OLD.notes, 500), LEFT(NEW.notes, 500));
    END IF;

    -- Registra mudança de endereço
    IF IFNULL(OLD.address, '') != IFNULL(NEW.address, '') THEN
        INSERT INTO client_history (client_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.updated_by, 'updated', 'address', OLD.address, NEW.address);
    END IF;
END//

-- =====================================================
-- TRIGGER: REGISTRAR HISTÓRICO DE ALTERAÇÕES (JOBS)
-- =====================================================
CREATE TRIGGER after_job_insert
AFTER INSERT ON jobs
FOR EACH ROW
BEGIN
    INSERT INTO job_history (job_id, user_id, action, notes)
    VALUES (NEW.id, NEW.created_by, 'created', CONCAT('Trabalho criado: ', NEW.title));
END//

CREATE TRIGGER after_job_update
AFTER UPDATE ON jobs
FOR EACH ROW
BEGIN
    -- Registra mudança de status
    IF OLD.status != NEW.status THEN
        INSERT INTO job_history (job_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.assigned_to, 'status_changed', 'status', OLD.status, NEW.status);
    END IF;

    -- Registra mudança de atribuição
    IF IFNULL(OLD.assigned_to, 0) != IFNULL(NEW.assigned_to, 0) THEN
        INSERT INTO job_history (job_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.assigned_to, 'assigned', 'assigned_to',
                CAST(OLD.assigned_to AS CHAR), CAST(NEW.assigned_to AS CHAR));
    END IF;

    -- Registra mudança de prioridade
    IF OLD.priority != NEW.priority THEN
        INSERT INTO job_history (job_id, user_id, action, field_changed, old_value, new_value)
        VALUES (NEW.id, NEW.assigned_to, 'updated', 'priority', OLD.priority, NEW.priority);
    END IF;
END//

DELIMITER ;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Inserir configuração inicial
INSERT INTO settings (company_name, client_name, currency_code, currency_symbol, timezone)
VALUES ('CRM Terreiro', 'Cliente', 'BRL', 'R$', 'America/Sao_Paulo');

-- Inserir usuário admin padrão (senha: password123)
INSERT INTO users (name, email, password, role, is_active)
VALUES ('Administrador', 'admin@crm-terreiro.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);

-- Inserir usuário staff padrão (senha: password123)
INSERT INTO users (name, email, password, role, is_active)
VALUES ('Colaborador', 'staff@crm-terreiro.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', TRUE);

-- Inserir alguns serviços de exemplo
INSERT INTO services (name, description, price, duration_minutes, is_active) VALUES
('Consulta Inicial', 'Primeira consulta com o cliente', 150.00, 60, TRUE),
('Atendimento Padrão', 'Atendimento regular', 100.00, 45, TRUE),
('Atendimento Especial', 'Atendimento com ritual especial', 250.00, 90, TRUE),
('Limpeza Espiritual', 'Sessão de limpeza energética', 200.00, 60, TRUE);

-- Reativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
--
-- CREDENCIAIS PADRÃO:
-- Admin: admin@crm-terreiro.local / password123
-- Staff: staff@crm-terreiro.local / password123
--
-- NOVOS CAMPOS DO CLIENTE:
-- - phone: Telefone principal
-- - phone_secondary: Telefone secundário
-- - whatsapp: Número do WhatsApp (ex: 5511999999999)
-- - whatsapp_link: Gerado automaticamente (https://wa.me/...)
-- - city, state, zip_code: Endereço completo
-- - document: CPF ou CNPJ
-- - birth_date: Data de nascimento
-- - source: Origem do cliente
-- - notes: Observações
-- - status: active, inactive, blocked
--
-- HISTÓRICO DE ALTERAÇÕES:
-- - client_history: Registra todas as alterações de clientes
-- - job_history: Registra todas as alterações de trabalhos
--
-- =====================================================
