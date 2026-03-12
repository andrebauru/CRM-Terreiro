<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $pdo = db();

    // ── 1. INDEPENDENT TABLES (no foreign key dependencies) ───────────────

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            email      VARCHAR(255) NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            role       ENUM('admin','staff') NOT NULL DEFAULT 'staff',
            is_active  TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS settings (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            client_name     VARCHAR(255),
            company_name    VARCHAR(255),
            logo_path       VARCHAR(512),
            currency_code   VARCHAR(3) NOT NULL DEFAULT 'JPY',
            currency_symbol VARCHAR(8) NOT NULL DEFAULT '¥',
            timezone        VARCHAR(64) NOT NULL DEFAULT 'Asia/Tokyo',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS clients (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            email      VARCHAR(255) DEFAULT NULL,
            phone      VARCHAR(50)  DEFAULT NULL,
            address    TEXT         DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS services (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(255) NOT NULL,
            description TEXT         DEFAULT NULL,
            price       INT NOT NULL DEFAULT 0,
            is_active   TINYINT(1) NOT NULL DEFAULT 1,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS filhos (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            name             VARCHAR(255) NOT NULL,
            email            VARCHAR(255) DEFAULT NULL,
            phone            VARCHAR(50)  DEFAULT NULL,
            grade            VARCHAR(50)  NOT NULL DEFAULT 'Iniciação',
            grade_date       DATE         DEFAULT NULL,
            status           VARCHAR(20)  NOT NULL DEFAULT 'ativo',
            saiu_at          DATE         DEFAULT NULL,
            mensalidade_value INT NOT NULL DEFAULT 0,
            due_day          INT NOT NULL DEFAULT 5,
            notes_evolucao   TEXT         DEFAULT NULL,
            anotacoes        TEXT         DEFAULT NULL,
            entidade_frente  VARCHAR(100) DEFAULT NULL,
            orixa_pai        VARCHAR(100) DEFAULT NULL,
            orixa_mae        VARCHAR(100) DEFAULT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS trabalhos (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(255) NOT NULL,
            description TEXT NULL,
            price       INT NOT NULL DEFAULT 0,
            is_active   TINYINT(1) NOT NULL DEFAULT 1,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contas_pagar (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            descricao       VARCHAR(255) NOT NULL,
            valor           INT NOT NULL DEFAULT 0,
            categoria       VARCHAR(100) NULL,
            data_vencimento DATE NOT NULL,
            status          ENUM('Pendente','Pago') NOT NULL DEFAULT 'Pendente',
            data_pagamento  DATE NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS entradas (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            descricao     VARCHAR(255) NOT NULL,
            valor         INT NOT NULL DEFAULT 0,
            origem        ENUM('mensalidade','trabalho','doacao','manual') NOT NULL DEFAULT 'manual',
            referencia_id INT NULL,
            data_entrada  DATE NOT NULL,
            created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS caixa_movimentos (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            tipo           ENUM('entrada','saida') NOT NULL,
            origem         ENUM('mensalidade','trabalho','conta_pagar','entrada','manual') NOT NULL DEFAULT 'manual',
            referencia_id  INT NULL,
            mes            DATE NOT NULL,
            data_movimento DATE NOT NULL,
            valor          INT NOT NULL DEFAULT 0,
            status         ENUM('previsto','realizado') NOT NULL DEFAULT 'realizado',
            descricao      VARCHAR(512) NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_origem_ref_mes (origem, referencia_id, mes),
            KEY idx_caixa_mes (mes),
            KEY idx_caixa_data (data_movimento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    // ── 2. TABLES WITH FOREIGN KEY DEPENDENCIES ───────────────────────────

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS mensalidades_pagas (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            filho_id   INT NOT NULL,
            paid_month DATE NOT NULL,
            amount     INT NOT NULL DEFAULT 0,
            paid_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            receipt_path VARCHAR(512) DEFAULT NULL,
            FOREIGN KEY (filho_id) REFERENCES filhos(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_filho_month (filho_id, paid_month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS mensalidades_lancamentos (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            filho_id        INT NOT NULL,
            valor           INT NOT NULL DEFAULT 0,
            data_vencimento DATE NOT NULL,
            pago            TINYINT(1) NOT NULL DEFAULT 0,
            data_pagamento  DATE NULL,
            descricao       VARCHAR(512) NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (filho_id) REFERENCES filhos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS quimbandeiro (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            filho_id       INT NOT NULL UNIQUE,
            probatorio     DATE DEFAULT NULL,
            link_iniciacao VARCHAR(512) DEFAULT NULL,
            mao_buzios     DATE DEFAULT NULL,
            mao_faca       DATE DEFAULT NULL,
            grau1          DATE DEFAULT NULL,
            grau2          DATE DEFAULT NULL,
            grau3          DATE DEFAULT NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (filho_id) REFERENCES filhos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS trabalho_realizacoes (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            trabalho_id     INT NOT NULL,
            cliente_nome    VARCHAR(255) NULL,
            data_realizacao DATE NOT NULL,
            status          ENUM('Pendente','Realizado','Adiado') NOT NULL DEFAULT 'Pendente',
            nova_data       DATE NULL,
            observacoes     TEXT NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (trabalho_id) REFERENCES trabalhos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS attendances (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            client_id    INT NOT NULL,
            notes        TEXT,
            total_amount INT NOT NULL DEFAULT 0,
            payment_type ENUM('cash','installments') NOT NULL DEFAULT 'cash',
            is_delinquent BOOLEAN NOT NULL DEFAULT FALSE,
            is_reversed  BOOLEAN NOT NULL DEFAULT FALSE,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS attendance_services (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            attendance_id INT NOT NULL,
            service_id    INT NOT NULL,
            price         INT NOT NULL DEFAULT 0,
            FOREIGN KEY (attendance_id) REFERENCES attendances(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id)    REFERENCES services(id)    ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS attendance_installments (
            id                 INT AUTO_INCREMENT PRIMARY KEY,
            attendance_id      INT NOT NULL,
            installment_number INT NOT NULL,
            amount             INT NOT NULL DEFAULT 0,
            due_date           DATE NOT NULL,
            status             ENUM('pending','paid') NOT NULL DEFAULT 'pending',
            paid_at            DATETIME DEFAULT NULL,
            receipt_path       VARCHAR(512) DEFAULT NULL,
            created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_att_installment (attendance_id, installment_number),
            FOREIGN KEY (attendance_id) REFERENCES attendances(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS credito_casa (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            entrada_id     INT NULL,
            valor_original INT NOT NULL,
            percentual     DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            valor_credito  INT NOT NULL,
            descricao      VARCHAR(255) NULL,
            data           DATE NOT NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    // ── 3. COLUMN MIGRATIONS (ensureColumn) ───────────────────────────────

    // filhos — columns added across versions
    ensureColumn($pdo, 'filhos', 'email',             'VARCHAR(255) DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'phone',             'VARCHAR(50) DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'grade',             "VARCHAR(50) NOT NULL DEFAULT 'Iniciação'");
    ensureColumn($pdo, 'filhos', 'grade_date',        'DATE DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'status',            "VARCHAR(20) NOT NULL DEFAULT 'ativo'");
    ensureColumn($pdo, 'filhos', 'saiu_at',           'DATE DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'mensalidade_value', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'filhos', 'due_day',           'INT NOT NULL DEFAULT 5');
    ensureColumn($pdo, 'filhos', 'notes_evolucao',    'TEXT DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'anotacoes',         'TEXT DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'entidade_frente',   'VARCHAR(100) DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'orixa_pai',         'VARCHAR(100) DEFAULT NULL');
    ensureColumn($pdo, 'filhos', 'orixa_mae',         'VARCHAR(100) DEFAULT NULL');

    // mensalidades_pagas
    ensureColumn($pdo, 'mensalidades_pagas', 'receipt_path', 'VARCHAR(512) DEFAULT NULL');

    // contas_pagar
    ensureColumn($pdo, 'contas_pagar', 'categoria',      'VARCHAR(100) NULL');
    ensureColumn($pdo, 'contas_pagar', 'data_pagamento', 'DATE NULL');

    // caixa_movimentos
    ensureColumn($pdo, 'caixa_movimentos', 'origem',        "ENUM('mensalidade','trabalho','conta_pagar','entrada','manual') NOT NULL DEFAULT 'manual'");
    ensureColumn($pdo, 'caixa_movimentos', 'referencia_id', 'INT NULL');
    ensureColumn($pdo, 'caixa_movimentos', 'mes',           'DATE NOT NULL');
    ensureColumn($pdo, 'caixa_movimentos', 'status',        "ENUM('previsto','realizado') NOT NULL DEFAULT 'realizado'");
    ensureColumn($pdo, 'caixa_movimentos', 'descricao',     'VARCHAR(512) NULL');

    // ── 4. ENUM MIGRATIONS (ensureEnumHasValue) ───────────────────────────

    ensureEnumHasValue(
        $pdo, 'caixa_movimentos', 'origem',
        "ENUM('mensalidade','trabalho','conta_pagar','entrada','manual') NOT NULL DEFAULT 'manual'",
        'entrada'
    );
    ensureEnumHasValue(
        $pdo, 'caixa_movimentos', 'status',
        "ENUM('previsto','realizado') NOT NULL DEFAULT 'realizado'",
        'previsto'
    );

    // ── 5. COLUMN TYPE MIGRATIONS ─────────────────────────────────────────

    $checks = [
        ['attendances',            'total_amount', 'INT NOT NULL DEFAULT 0'],
        ['attendance_services',    'price',        'INT NOT NULL DEFAULT 0'],
        ['attendance_installments', 'amount',      'INT NOT NULL DEFAULT 0'],
        ['services',               'price',        'INT NOT NULL DEFAULT 0'],
    ];
    foreach ($checks as [$tbl, $col, $def]) {
        $type = $pdo->query(
            "SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tbl' AND COLUMN_NAME = '$col'"
        )->fetchColumn();
        if ($type && $type !== 'int') {
            $pdo->exec("ALTER TABLE `$tbl` MODIFY `$col` $def");
        }
    }

    // ── 6. SEEDS ──────────────────────────────────────────────────────────

    // Default admin user (senha: 123456)
    $adminHash = '$2y$12$a0GPOE8R6inJUzrnz1MXQuLtCvd3yzAiagv0/ltDLP8H8tZHyrYH.';
    $pdo->prepare(
        'INSERT IGNORE INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)'
    )->execute(['Administrador', 'admin@terreiro.com', $adminHash, 'admin', 1]);

    // Default settings row
    if ((int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO settings (company_name) VALUES ('CRM Terreiro')");
    }

    echo "OK — migrations concluídas com sucesso.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erro: " . $e->getMessage();
}
