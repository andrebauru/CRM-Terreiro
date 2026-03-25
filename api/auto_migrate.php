<?php
/**
 * Auto-migration: cria todas as tabelas e colunas que faltam no banco.
 * Incluído automaticamente pelo tw-head.php em todas as páginas.
 * Usa um flag na sessão para rodar apenas 1x por sessão do navegador.
 */

declare(strict_types=1);

function runAutoMigrate(PDO $pdo): void
{
    // Só rodar 1x por sessão para não impactar performance
    if (!empty($_SESSION['_auto_migrated'])) {
        return;
    }

    try {
        // ── users ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin','staff','user') NOT NULL DEFAULT 'staff',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Ensure role ENUM includes 'user'
        try {
            $col = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'")->fetch();
            if ($col && strpos($col['Type'], "'user'") === false) {
                $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','staff','user') NOT NULL DEFAULT 'staff'");
            }
        } catch (Throwable $e) { /* ignore */ }
        ensureColumn($pdo, 'users', 'phone', "VARCHAR(50) NULL AFTER email");
        ensureColumn($pdo, 'users', 'allowed_pages', "TEXT NULL AFTER is_active");

        // ── login_attempts (brute force protection) ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(255) NULL,
                attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_attempted (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── settings ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) DEFAULT 'CRM Terreiro',
                logo_path VARCHAR(512) NULL,
                notification_email VARCHAR(255) NULL,
                sendgrid_api_key TEXT NULL,
                currency_code VARCHAR(10) DEFAULT 'JPY',
                currency_symbol VARCHAR(10) DEFAULT '¥',
                language VARCHAR(10) DEFAULT 'pt',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        ensureColumn($pdo, 'settings', 'notification_email', 'VARCHAR(255) NULL');
        ensureColumn($pdo, 'settings', 'sendgrid_api_key', 'TEXT NULL');
        // Seed settings if empty
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        if ($cnt === 0) {
            $pdo->exec("INSERT INTO settings (company_name) VALUES ('CRM Terreiro')");
        }

        // ── clients ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NULL,
                phone VARCHAR(50) NULL,
                phone_secondary VARCHAR(50) NULL,
                whatsapp VARCHAR(50) NULL,
                address TEXT NULL,
                city VARCHAR(100) NULL,
                state VARCHAR(50) NULL,
                zip_code VARCHAR(20) NULL,
                document VARCHAR(50) NULL,
                birth_date DATE NULL,
                source VARCHAR(100) NULL,
                notes TEXT NULL,
                status ENUM('active','inactive') DEFAULT 'active',
                created_by INT NULL,
                updated_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── services ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                price INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── attendances ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                data_atendimento DATE NULL,
                notes TEXT NULL,
                total_amount INT NOT NULL DEFAULT 0,
                payment_type ENUM('cash','installments') DEFAULT 'cash',
                is_delinquent TINYINT(1) DEFAULT 0,
                is_reversed TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client (client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        ensureColumn($pdo, 'attendances', 'data_atendimento', 'DATE NULL AFTER client_id');

        // ── atendimento_agendamentos ──
        $pdo->exec(" 
            CREATE TABLE IF NOT EXISTS atendimento_agendamentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                data_agendamento DATE NOT NULL,
                hora_agendamento TIME NOT NULL,
                tipo_atendimento ENUM('servico','trabalho') NOT NULL DEFAULT 'servico',
                referencia_id INT NULL,
                referencia_nome VARCHAR(255) NULL,
                valor_previsto INT NOT NULL DEFAULT 0,
                status ENUM('agendado','realizado','cancelado') NOT NULL DEFAULT 'agendado',
                observacoes TEXT NULL,
                converted_attendance_id INT NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_data_hora (data_agendamento, hora_agendamento),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
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

        // ── attendance_services ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendance_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attendance_id INT NOT NULL,
                service_id INT NOT NULL,
                price INT NOT NULL DEFAULT 0,
                INDEX idx_attendance (attendance_id),
                INDEX idx_service (service_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── attendance_installments ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendance_installments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attendance_id INT NOT NULL,
                installment_number INT NOT NULL DEFAULT 1,
                amount INT NOT NULL DEFAULT 0,
                due_date DATE NOT NULL,
                status ENUM('pending','paid') DEFAULT 'pending',
                receipt_path VARCHAR(512) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_attendance (attendance_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── trabalhos (catálogo) ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS trabalhos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                price INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── trabalho_realizacoes ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS trabalho_realizacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                trabalho_id INT NOT NULL,
                attendance_id INT NULL,
                cliente_nome VARCHAR(255) NULL,
                client_id INT NULL,
                data_realizacao DATE NOT NULL,
                status ENUM('Pendente','Realizado','Adiado') DEFAULT 'Pendente',
                nova_data DATE NULL,
                data_pagamento DATE NULL,
                observacoes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_trabalho (trabalho_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        ensureColumn($pdo, 'trabalho_realizacoes', 'attendance_id', 'INT NULL AFTER trabalho_id');
        ensureColumn($pdo, 'trabalho_realizacoes', 'client_id', 'INT NULL AFTER cliente_nome');
        ensureColumn($pdo, 'trabalho_realizacoes', 'data_pagamento', 'DATE NULL AFTER nova_data');

        // ── trabalho_datas_extras ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS trabalho_datas_extras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                trabalho_realizacao_id INT NOT NULL,
                data_extra DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (trabalho_realizacao_id) REFERENCES trabalho_realizacoes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── tipos_gira ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tipos_gira (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── giras ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS giras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo_gira_id INT NOT NULL,
                plataforma VARCHAR(255) NOT NULL DEFAULT 'Instagram',
                foto_path VARCHAR(512) NULL,
                data_postagem DATE NULL,
                data_realizacao DATE NOT NULL,
                descricao TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (tipo_gira_id) REFERENCES tipos_gira(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── filhos ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS filhos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                grade VARCHAR(100) NULL,
                phone VARCHAR(50) NULL,
                email VARCHAR(255) NULL,
                address TEXT NULL,
                birth_date DATE NULL,
                initiation_date DATE NULL,
                status ENUM('ativo','inativo') DEFAULT 'ativo',
                mensalidade_value INT NOT NULL DEFAULT 0,
                due_day INT NOT NULL DEFAULT 5,
                isento_mensalidade TINYINT(1) NOT NULL DEFAULT 0,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        ensureColumn($pdo, 'filhos', 'isento_mensalidade', "TINYINT(1) NOT NULL DEFAULT 0 AFTER due_day");

        // ── mensalidades_pagas ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS mensalidades_pagas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filho_id INT NOT NULL,
                paid_month VARCHAR(10) NOT NULL,
                paid_date DATE NULL,
                valor INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_filho_month (filho_id, paid_month)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── categorias_conta (NOVO) ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categorias_conta (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        // Seed categorias padrão
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM categorias_conta")->fetchColumn();
        if ($cnt === 0) {
            $pdo->exec("INSERT IGNORE INTO categorias_conta (nome) VALUES
                ('Aluguel'), ('Energia'), ('Água'), ('Internet'), ('Telefone'),
                ('Material de Limpeza'), ('Velas e Materiais'), ('Transporte'),
                ('Alimentação'), ('Manutenção'), ('Outros')
            ");

            // ── avisos ──
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS avisos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    titulo VARCHAR(255) NOT NULL,
                    mensagem TEXT NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_by INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_avisos_active_date (is_active, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            ensureColumn($pdo, 'avisos', 'titulo', 'VARCHAR(255) NOT NULL');
            ensureColumn($pdo, 'avisos', 'mensagem', 'TEXT NOT NULL');
            ensureColumn($pdo, 'avisos', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
            ensureColumn($pdo, 'avisos', 'created_by', 'INT NULL');
        }

        // ── contas_pagar (enhanced) ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contas_pagar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                descricao VARCHAR(255) NOT NULL,
                valor INT NOT NULL DEFAULT 0,
                categoria VARCHAR(255) NULL,
                fornecedor VARCHAR(255) NULL,
                data_vencimento DATE NOT NULL,
                status ENUM('Pendente','Pago') DEFAULT 'Pendente',
                data_pagamento DATE NULL,
                recorrencia ENUM('nenhuma','mensal','bimestral','trimestral','semestral','anual') DEFAULT 'nenhuma',
                parcela_num INT NULL,
                parcela_total INT NULL,
                parcela_grupo_id INT NULL,
                valor_pago INT NOT NULL DEFAULT 0,
                mes_referencia VARCHAR(7) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        ensureColumn($pdo, 'contas_pagar', 'fornecedor', "VARCHAR(255) NULL AFTER categoria");
        ensureColumn($pdo, 'contas_pagar', 'recorrencia', "ENUM('nenhuma','mensal','bimestral','trimestral','semestral','anual') DEFAULT 'nenhuma' AFTER data_pagamento");
        ensureColumn($pdo, 'contas_pagar', 'parcela_num', "INT NULL AFTER recorrencia");
        ensureColumn($pdo, 'contas_pagar', 'parcela_total', "INT NULL AFTER parcela_num");
        ensureColumn($pdo, 'contas_pagar', 'parcela_grupo_id', "INT NULL AFTER parcela_total");
        ensureColumn($pdo, 'contas_pagar', 'valor_pago', "INT NOT NULL DEFAULT 0 AFTER parcela_grupo_id");
        ensureColumn($pdo, 'contas_pagar', 'mes_referencia', "VARCHAR(7) NULL AFTER valor_pago");

        // ── entradas ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS entradas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                descricao VARCHAR(255) NOT NULL,
                valor INT NOT NULL DEFAULT 0,
                origem VARCHAR(50) DEFAULT 'manual',
                data_entrada DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── credito_casa ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS credito_casa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                entrada_id INT NULL,
                valor_original INT NOT NULL DEFAULT 0,
                percentual DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                valor_credito INT NOT NULL DEFAULT 0,
                descricao VARCHAR(255) NULL,
                data DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── caixa_movimentos ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS caixa_movimentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo ENUM('entrada','saida') NOT NULL,
                origem VARCHAR(50) NOT NULL,
                referencia_id INT NULL,
                mes VARCHAR(10) NOT NULL,
                data_movimento DATE NOT NULL,
                valor INT NOT NULL DEFAULT 0,
                status ENUM('previsto','realizado') DEFAULT 'previsto',
                descricao VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_origem_ref_mes (origem, referencia_id, mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── medium_configs ──
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS medium_configs (
                user_id INT NOT NULL PRIMARY KEY,
                pct_espaco DECIMAL(5,2) NOT NULL DEFAULT 20.00,
                pct_treinamento DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                pct_material DECIMAL(5,2) NOT NULL DEFAULT 20.00,
                pct_tata DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                pct_executor DECIMAL(5,2) NOT NULL DEFAULT 40.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_auto_medium_configs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── financial_transactions ──
        $pdo->exec("
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
                INDEX idx_financial_transactions_medium (medium_id),
                INDEX idx_financial_transactions_tata (tata_id),
                INDEX idx_financial_transactions_status_data (status_pagamento, data_realizacao),
                CONSTRAINT fk_auto_financial_transactions_medium FOREIGN KEY (medium_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_auto_financial_transactions_tata FOREIGN KEY (tata_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        ensureColumn($pdo, 'medium_configs', 'pct_espaco', "DECIMAL(5,2) NOT NULL DEFAULT 20.00");
        ensureColumn($pdo, 'medium_configs', 'pct_treinamento', "DECIMAL(5,2) NOT NULL DEFAULT 10.00");
        ensureColumn($pdo, 'medium_configs', 'pct_material', "DECIMAL(5,2) NOT NULL DEFAULT 20.00");
        ensureColumn($pdo, 'medium_configs', 'pct_tata', "DECIMAL(5,2) NOT NULL DEFAULT 10.00");
        ensureColumn($pdo, 'medium_configs', 'pct_executor', "DECIMAL(5,2) NOT NULL DEFAULT 40.00");
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

        try {
            // Backfill: usuários existentes (role=user) viram membros probatórios
            // Regra: se email já existir em filhos, não migra aquele usuário.
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
        } catch (Throwable $e) {
            // ignora falhas de backfill para não bloquear carregamento de página
        }

        $_SESSION['_auto_migrated'] = true;
    } catch (Throwable $e) {
        // Log error but don't break the page
        error_log('[AutoMigrate] ' . $e->getMessage());
    }
}
