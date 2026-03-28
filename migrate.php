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
            foto_perfil VARCHAR(512) NULL,
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
            notification_email VARCHAR(255) NULL,
            sendgrid_api_key TEXT NULL,
            sendgrid_from_email VARCHAR(255) NULL,
            sendgrid_from_name VARCHAR(255) NULL,
            sendgrid_port INT NULL,
            currency_code   VARCHAR(3) NOT NULL DEFAULT 'JPY',
            currency_symbol VARCHAR(8) NOT NULL DEFAULT '¥',
            timezone        VARCHAR(64) NOT NULL DEFAULT 'Asia/Tokyo',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS avisos (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            titulo     VARCHAR(255) NOT NULL,
            mensagem   TEXT NOT NULL,
            imagem_path VARCHAR(512) NULL,
            link_postagem VARCHAR(512) NULL,
            is_active  TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_avisos_active_date (is_active, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS sendgrid_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            section VARCHAR(80) NULL,
            action_name VARCHAR(80) NULL,
            title VARCHAR(255) NULL,
            to_email VARCHAR(255) NULL,
            from_email VARCHAR(255) NULL,
            subject VARCHAR(255) NULL,
            status_code INT NOT NULL DEFAULT 0,
            success TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT NULL,
            provider_response MEDIUMTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_sendgrid_logs_created (created_at),
            KEY idx_sendgrid_logs_success (success, created_at)
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
        "CREATE TABLE IF NOT EXISTS atendimento_agendamentos (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            nome             VARCHAR(255) NOT NULL,
            data_agendamento DATE NOT NULL,
            hora_agendamento TIME NOT NULL,
            tipo_atendimento ENUM('servico','trabalho') NOT NULL DEFAULT 'servico',
            referencia_id    INT NULL,
            referencia_nome  VARCHAR(255) NULL,
            valor_previsto   INT NOT NULL DEFAULT 0,
            status           ENUM('agendado','realizado','cancelado') NOT NULL DEFAULT 'agendado',
            observacoes      TEXT NULL,
            converted_attendance_id INT NULL,
            created_by       INT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ag_data_hora (data_agendamento, hora_agendamento),
            KEY idx_ag_status (status)
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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS medium_configs (
            user_id          INT NOT NULL PRIMARY KEY,
            pct_espaco       DECIMAL(5,2) NOT NULL DEFAULT 20.00,
            pct_treinamento  DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            pct_material     DECIMAL(5,2) NOT NULL DEFAULT 20.00,
            pct_tata         DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            pct_executor     DECIMAL(5,2) NOT NULL DEFAULT 40.00,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS financial_transactions (
            id                   INT AUTO_INCREMENT PRIMARY KEY,
            medium_id            INT NOT NULL,
            tata_id              INT NULL,
            cliente_nome         VARCHAR(255) NULL,
            cliente_telefone     VARCHAR(50) NULL,
            descricao_servico    VARCHAR(255) NULL,
            valor_total          INT NOT NULL DEFAULT 0,
            taxa_gensen_paga     INT NOT NULL DEFAULT 0,
            valor_liquido_medium INT NOT NULL DEFAULT 0,
            valor_liquido_tata   INT NOT NULL DEFAULT 0,
            status_pagamento     ENUM('pendente','processando','pago','cancelado') NOT NULL DEFAULT 'pendente',
            data_realizacao      DATE NOT NULL,
            data_pagamento       DATE NULL,
            receipt_path         VARCHAR(512) NULL,
            created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_financial_transactions_medium (medium_id),
            KEY idx_financial_transactions_tata (tata_id),
            KEY idx_financial_transactions_status_data (status_pagamento, data_realizacao),
            FOREIGN KEY (medium_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (tata_id) REFERENCES users(id) ON DELETE SET NULL
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

    // medium_configs
    ensureColumn($pdo, 'medium_configs', 'pct_espaco', 'DECIMAL(5,2) NOT NULL DEFAULT 20.00');
    ensureColumn($pdo, 'medium_configs', 'pct_treinamento', 'DECIMAL(5,2) NOT NULL DEFAULT 10.00');
    ensureColumn($pdo, 'medium_configs', 'pct_material', 'DECIMAL(5,2) NOT NULL DEFAULT 20.00');
    ensureColumn($pdo, 'medium_configs', 'pct_tata', 'DECIMAL(5,2) NOT NULL DEFAULT 10.00');
    ensureColumn($pdo, 'medium_configs', 'pct_executor', 'DECIMAL(5,2) NOT NULL DEFAULT 40.00');

    // financial_transactions
    ensureColumn($pdo, 'financial_transactions', 'medium_id', 'INT NOT NULL');
    ensureColumn($pdo, 'financial_transactions', 'tata_id', 'INT NULL');
    ensureColumn($pdo, 'financial_transactions', 'cliente_nome', 'VARCHAR(255) NULL');
    ensureColumn($pdo, 'financial_transactions', 'cliente_telefone', 'VARCHAR(50) NULL');
    ensureColumn($pdo, 'financial_transactions', 'descricao_servico', 'VARCHAR(255) NULL');
    ensureColumn($pdo, 'financial_transactions', 'valor_total', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'financial_transactions', 'taxa_gensen_paga', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'financial_transactions', 'valor_liquido_medium', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'financial_transactions', 'valor_liquido_tata', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'financial_transactions', 'status_pagamento', "ENUM('pendente','processando','pago','cancelado') NOT NULL DEFAULT 'pendente'");
    ensureColumn($pdo, 'financial_transactions', 'data_realizacao', 'DATE NOT NULL');
    ensureColumn($pdo, 'financial_transactions', 'data_pagamento', 'DATE NULL');
    ensureColumn($pdo, 'financial_transactions', 'receipt_path', 'VARCHAR(512) NULL');

    // avisos
    ensureColumn($pdo, 'avisos', 'titulo', 'VARCHAR(255) NOT NULL');
    ensureColumn($pdo, 'avisos', 'mensagem', 'TEXT NOT NULL');
    ensureColumn($pdo, 'avisos', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
    ensureColumn($pdo, 'avisos', 'created_by', 'INT NULL');

    // atendimento_agendamentos
    ensureColumn($pdo, 'atendimento_agendamentos', 'nome', 'VARCHAR(255) NOT NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'data_agendamento', 'DATE NOT NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'hora_agendamento', 'TIME NOT NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'tipo_atendimento', "ENUM('servico','trabalho') NOT NULL DEFAULT 'servico'");
    ensureColumn($pdo, 'atendimento_agendamentos', 'referencia_id', 'INT NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'referencia_nome', 'VARCHAR(255) NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'valor_previsto', 'INT NOT NULL DEFAULT 0');
    ensureColumn($pdo, 'atendimento_agendamentos', 'status', "ENUM('agendado','realizado','cancelado') NOT NULL DEFAULT 'agendado'");
    ensureColumn($pdo, 'atendimento_agendamentos', 'observacoes', 'TEXT NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'converted_attendance_id', 'INT NULL');
    ensureColumn($pdo, 'atendimento_agendamentos', 'created_by', 'INT NULL');

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
        ['trabalhos',              'price',        'INT NOT NULL DEFAULT 0'],
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

    ensureColumn($pdo, 'settings', 'notification_email', "VARCHAR(255) NULL AFTER logo_path");
    ensureColumn($pdo, 'settings', 'sendgrid_api_key', "TEXT NULL AFTER notification_email");
    ensureColumn($pdo, 'settings', 'sendgrid_from_email', "VARCHAR(255) NULL AFTER sendgrid_api_key");
    ensureColumn($pdo, 'settings', 'sendgrid_from_name', "VARCHAR(255) NULL AFTER sendgrid_from_email");
    ensureColumn($pdo, 'settings', 'sendgrid_port', "INT NULL AFTER sendgrid_from_name");
    ensureColumn($pdo, 'avisos', 'imagem_path', "VARCHAR(512) NULL AFTER mensagem");
    ensureColumn($pdo, 'avisos', 'link_postagem', "VARCHAR(512) NULL AFTER imagem_path");

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

    // Backfill: usuários existentes (role=user) viram membros probatórios
    // Regra: se o email já existir em filhos, NÃO realiza a migração desse usuário.
    $pdo->exec(
        "INSERT INTO filhos (name, email, phone, grade, grade_date, status)
         SELECT u.name, u.email, NULLIF(u.phone, ''), 'Probatório', DATE(u.created_at), 'ativo'
         FROM users u
         LEFT JOIN filhos f ON f.email = u.email
         WHERE u.role = 'user'
           AND u.email IS NOT NULL
           AND u.email <> ''
           AND f.id IS NULL"
    );

    $pdo->exec(
        "INSERT INTO quimbandeiro (filho_id, probatorio)
         SELECT f.id, COALESCE(f.grade_date, CURDATE())
         FROM filhos f
         JOIN users u ON u.email = f.email
         LEFT JOIN quimbandeiro q ON q.filho_id = f.id
         WHERE u.role = 'user'
           AND u.email IS NOT NULL
           AND u.email <> ''
           AND q.filho_id IS NULL"
    );

    echo "OK — migrations concluídas com sucesso.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erro: " . $e->getMessage();
}
