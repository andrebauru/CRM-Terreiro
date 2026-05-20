-- =====================================================
-- DUMP SQL - Sistema de Aprovação de Perfil
-- Data: 21/05/2026
-- Descrição: Migração para implementar aprovação de alterações de perfil para usuários comuns
-- =====================================================

-- Criar tabela para armazenar alterações de perfil pendentes de aprovação
CREATE TABLE IF NOT EXISTS `profile_pending_changes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `foto_perfil` VARCHAR(512) NULL,
    `password_hash` VARCHAR(255) NULL,
    `other_data` JSON NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL,
    `reviewed_by` INT NULL,
    `review_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_requested_at` (`requested_at`),
    INDEX `idx_reviewed_by` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Fim da migração
-- =====================================================
