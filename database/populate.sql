-- =============================================
-- CRM Terreiro - Script de População Inicial
-- =============================================
-- Execute APÓS o update_v2.sql
-- =============================================

-- =============================================
-- Usuário Administrador
-- =============================================
-- Usuário: Andretsc
-- Email: andretsc@gmail.com
-- Senha: 23071988

INSERT INTO users (name, email, password, role, is_active, created_at) VALUES
('Andretsc', 'andretsc@gmail.com', '$2y$12$BQDO8I0Ev6pd5oiTckT6buHyb5CJqcHpH8pAzsXvkd8Jc7PQ4fLxS', 'admin', 1, NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password);

-- =============================================
-- Configurações Iniciais
-- =============================================
-- Limpa configurações antigas se existirem
TRUNCATE TABLE settings;

INSERT INTO settings (company_name, client_name, currency_code, currency_symbol, timezone) VALUES
('CRM Terreiro', 'Cliente', 'JPY', '¥', 'Asia/Tokyo');

-- =============================================
-- Serviços de Exemplo
-- =============================================
INSERT INTO services (name, description, price, duration_minutes, is_active, created_at) VALUES
('Consulta Espiritual', 'Consulta para orientação espiritual e aconselhamento', 150.00, 60, 1, NOW()),
('Trabalho de Limpeza', 'Trabalho espiritual para limpeza energética', 300.00, 90, 1, NOW()),
('Trabalho de Proteção', 'Trabalho espiritual para proteção pessoal e do lar', 350.00, 90, 1, NOW()),
('Banho de Descarrego', 'Preparação de banho para descarrego espiritual', 80.00, 30, 1, NOW()),
('Oferenda', 'Preparação e entrega de oferenda aos orixás', 200.00, 60, 1, NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description), price = VALUES(price);

-- =============================================
-- Clientes de Exemplo
-- =============================================
INSERT INTO clients (name, email, phone, whatsapp, address, city, state, zip_code, source, notes, status, created_by, created_at) VALUES
('Maria Silva', 'maria@email.com', '(11) 98765-4321', '5511987654321', 'Rua das Flores, 123', 'São Paulo', 'SP', '01234-567', 'indicacao', 'Cliente desde 2023. Muito dedicada.', 'active', 1, NOW()),
('João Santos', 'joao@email.com', '(11) 91234-5678', '5511912345678', 'Av. Brasil, 456', 'São Paulo', 'SP', '04567-890', 'instagram', 'Veio através do Instagram.', 'active', 1, NOW()),
('Ana Costa', 'ana@email.com', '(21) 99876-5432', '5521998765432', 'Rua do Sol, 789', 'Rio de Janeiro', 'RJ', '20000-000', 'whatsapp', 'Indicação da Maria Silva.', 'active', 1, NOW());

-- =============================================
-- Trabalhos de Exemplo
-- =============================================
INSERT INTO jobs (client_id, service_id, title, description, status, priority, channel, start_date, due_date, installments, installment_value, created_by, created_at) VALUES
(1, 1, 'Consulta - Maria Silva', 'Consulta de orientação espiritual agendada', 'completed', 'medium', 'whatsapp', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 1, 150.00, 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 3, 'Proteção - Maria Silva', 'Trabalho de proteção para residência', 'in_progress', 'high', 'presencial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 2, 175.00, 1, NOW()),
(2, 2, 'Limpeza - João Santos', 'Trabalho de limpeza energética', 'pending', 'medium', 'instagram', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1, 300.00, 1, NOW()),
(3, 4, 'Banho - Ana Costa', 'Preparação de banho de descarrego', 'pending', 'low', 'whatsapp', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 1, 80.00, 1, NOW());

-- =============================================
-- Resultado
-- =============================================
SELECT '========================================' AS '';
SELECT 'DADOS INSERIDOS COM SUCESSO!' AS Status;
SELECT '========================================' AS '';
SELECT 'Credenciais de acesso:' AS Info;
SELECT '  Email: andretsc@gmail.com' AS Login;
SELECT '  Senha: 23071988' AS Senha;
SELECT '========================================' AS '';
