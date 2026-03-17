-- ============================================================
-- CRM Terreiro — Schema Completo (gerado automaticamente)
-- Execute este script para criar todas as tabelas necessárias
-- ============================================================

-- Filhos da Casa
CREATE TABLE IF NOT EXISTS filhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    grade ENUM('Probatório','Iniciação','1º Grau','2º Grau','3º Grau','Mestre') NOT NULL DEFAULT 'Iniciação',
    grade_date DATE NULL,
    status ENUM('ativo','saiu') NOT NULL DEFAULT 'ativo',
    saiu_at DATE NULL,
    mensalidade_value INT NOT NULL DEFAULT 0,
    due_day INT NOT NULL DEFAULT 5,
    notes_evolucao TEXT NULL,
    anotacoes TEXT NULL,
    entidade_frente VARCHAR(255) NULL,
    orixa_pai VARCHAR(255) NULL,
    orixa_mae VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensalidades Pagas (controle mensal automático)
CREATE TABLE IF NOT EXISTS mensalidades_pagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filho_id INT NOT NULL,
    paid_month DATE NOT NULL,
    amount INT NOT NULL DEFAULT 0,
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    receipt_path VARCHAR(512) DEFAULT NULL,
    FOREIGN KEY (filho_id) REFERENCES filhos(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_filho_month (filho_id, paid_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensalidades Lançamentos (cobranças manuais extras)
CREATE TABLE IF NOT EXISTS mensalidades_lancamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filho_id INT NOT NULL,
    valor INT NOT NULL DEFAULT 0,
    data_vencimento DATE NOT NULL,
    pago TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATE NULL,
    descricao VARCHAR(512) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filho_id) REFERENCES filhos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quimbandeiro — Graus de Iniciação por Filho
CREATE TABLE IF NOT EXISTS quimbandeiro_graus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filho_id INT NOT NULL UNIQUE,
    probatorio DATE NULL,
    link_iniciacao VARCHAR(512) NULL,
    mao_buzios DATE NULL,
    mao_faca DATE NULL,
    grau1 DATE NULL,
    grau2 DATE NULL,
    grau3 DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (filho_id) REFERENCES filhos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo de Trabalhos (tipos de trabalho)
CREATE TABLE IF NOT EXISTS trabalhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Realizações de Trabalhos (agendamentos e status)
CREATE TABLE IF NOT EXISTS trabalho_realizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trabalho_id INT NOT NULL,
    cliente_nome VARCHAR(255) NULL,
    data_realizacao DATE NOT NULL,
    status ENUM('Pendente','Realizado','Adiado') NOT NULL DEFAULT 'Pendente',
    nova_data DATE NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trabalho_id) REFERENCES trabalhos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tipos de Gira (tipos para combobox)
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

-- ============================================================
-- NOTA: Todas as tabelas são criadas automaticamente pelo PHP
-- ao acessar as respectivas páginas pela primeira vez.
-- ============================================================
