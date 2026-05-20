-- Migração para tabela de relatórios financeiros gerados
-- Data: 21/05/2026

CREATE TABLE IF NOT EXISTS `relatorios_gerados` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mes_referencia` VARCHAR(7) NOT NULL COMMENT 'Formato YYYY-MM',
    `total_entradas` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `total_saidas` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `saldo_anterior` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `subtotal_mes` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `saldo_final` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `dados_detalhados` JSON NOT NULL COMMENT 'Snapshot completo do relatório',
    `gerado_por` INT NOT NULL,
    `gerado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`gerado_por`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    UNIQUE KEY `unique_mes` (`mes_referencia`),
    INDEX `idx_mes_referencia` (`mes_referencia`),
    INDEX `idx_gerado_por` (`gerado_por`),
    INDEX `idx_gerado_em` (`gerado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
