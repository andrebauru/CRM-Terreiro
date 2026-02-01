-- Migration: add settings currency/timezone and job installments (no data loss)

-- Settings: add currency/timezone columns if they do not exist (MySQL 5.7+ compatible)
SET @db = DATABASE();

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE settings ADD COLUMN currency_code VARCHAR(3) NOT NULL DEFAULT ''JPY''',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'currency_code'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE settings ADD COLUMN currency_symbol VARCHAR(8) NOT NULL DEFAULT ''¥''',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'currency_symbol'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE settings ADD COLUMN timezone VARCHAR(64) NOT NULL DEFAULT ''Asia/Tokyo''',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'timezone'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Jobs: add installments columns if they do not exist
SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE jobs ADD COLUMN installments INT NOT NULL DEFAULT 1',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'installments'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE jobs ADD COLUMN installment_value DECIMAL(10, 2) DEFAULT NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'jobs' AND COLUMN_NAME = 'installment_value'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure existing settings row has defaults
UPDATE settings
SET currency_code = COALESCE(currency_code, 'JPY'),
    currency_symbol = COALESCE(currency_symbol, '¥'),
    timezone = COALESCE(timezone, 'Asia/Tokyo')
WHERE id IS NOT NULL;
