CREATE TABLE IF NOT EXISTS medium_configs (
    user_id INT NOT NULL PRIMARY KEY,
    pct_espaco DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    pct_treinamento DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    pct_material DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    pct_tata DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    pct_executor DECIMAL(5,2) NOT NULL DEFAULT 40.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medium_configs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medium_id INT NOT NULL,
    tata_id INT NULL,
    cliente_nome VARCHAR(255) NULL,
    cliente_telefone VARCHAR(50) NULL,
    descricao_servico VARCHAR(255) NULL,
    valor_total INT NOT NULL DEFAULT 0,
    taxa_gensen_paga INT NOT NULL DEFAULT 0,
    valor_liquido_medium INT NOT NULL DEFAULT 0,
    valor_liquido_tata INT NOT NULL DEFAULT 0,
    status_pagamento ENUM('pendente','processando','pago','cancelado') NOT NULL DEFAULT 'pendente',
    data_realizacao DATE NOT NULL,
    data_pagamento DATE NULL,
    receipt_path VARCHAR(512) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_financial_transactions_medium (medium_id),
    KEY idx_financial_transactions_tata (tata_id),
    KEY idx_financial_transactions_status_data (status_pagamento, data_realizacao),
    CONSTRAINT fk_financial_transactions_medium FOREIGN KEY (medium_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_financial_transactions_tata FOREIGN KEY (tata_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
