-- Migration: create job_installments table (no data loss)

SET @db = DATABASE();

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'CREATE TABLE job_installments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            installment_number INT NOT NULL,
            amount DECIMAL(10, 2) DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            status ENUM(''pending'', ''paid'') NOT NULL DEFAULT ''pending'',
            paid_at DATETIME DEFAULT NULL,
            paid_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_job_installment (job_id, installment_number),
            KEY idx_job_installments_job_id (job_id),
            CONSTRAINT fk_job_installments_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            CONSTRAINT fk_job_installments_paid_by FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'SELECT 1'
    )
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'job_installments'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
