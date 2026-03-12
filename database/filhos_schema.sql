-- Schema separado para Filhos e Mensalidades (JPY como INT)

CREATE TABLE IF NOT EXISTS filhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    grade ENUM('Iniciação','1º Grau','2º Grau','3º Grau','Mestre') NOT NULL DEFAULT 'Iniciação',
    mensalidade_value INT NOT NULL DEFAULT 0,
    due_day INT NOT NULL DEFAULT 5,
    notes_evolucao TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
