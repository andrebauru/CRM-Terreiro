-- database/seed.sql

-- Seed initial users
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'andre@crm.quimbanda.jp', '$2y$12$zyDqXNni06F0mPom81I.PeKRqgGdMi5r4Z/CptBFlfQl69H.xtpdy', 'admin'),
('Staff User', 'staff@crm-terreiro.local', '$2y$12$a97bo0U5kvxCY3NkZTmtGeSM9akRzRDKJbJyG4QQMCX7ElP8K2VVS', 'staff');

-- Seed default settings
INSERT INTO settings (client_name, company_name, logo_path, currency_code, currency_symbol, timezone) VALUES
('Cliente', 'Empresa', NULL, 'JPY', '¥', 'Asia/Tokyo');

-- You can add more seed data for clients, services, jobs here later
