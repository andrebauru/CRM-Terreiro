-- Adiciona suporte a notificações por SendGrid em Avisos e Giras
-- Data: 2026-03-26

ALTER TABLE settings
ADD COLUMN notification_email VARCHAR(255) NULL AFTER logo_path,
ADD COLUMN sendgrid_api_key TEXT NULL AFTER notification_email;

-- Nota: Após rodar este script, configure:
-- 1. Acesse a página de Configurações
-- 2. Preencha "E-mail para notificações"
-- 3. Preencha "API Key SendGrid"
-- 4. Salve as configurações
-- 
-- A partir daí, toda criação/atualização/exclusão em Avisos e Giras
-- disparará um email automático via SendGrid.
