-- Migração: adicionar foto de perfil para o chat
-- Execute apenas se a coluna ainda não existir

ALTER TABLE users
  ADD COLUMN foto_perfil VARCHAR(512) NULL AFTER phone;

-- Query para lista de contatos do chat (usuários ativos, exceto o logado)
-- Substitua :current_user_id pelo valor de $_SESSION['user_id'] no backend
SELECT id, name, email, foto_perfil
FROM users
WHERE is_active = 1
  AND id <> :current_user_id
ORDER BY name ASC;
