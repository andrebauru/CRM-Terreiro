-- database/seed.sql

-- Seed initial users
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@crm-terreiro.local', '$2y$12$zjavbsvT3XcWsnPAaJdK8uWMALzhFa28yu1lSS.l4rqeOgygsgqFO', 'admin'),
('Staff User', 'staff@crm-terreiro.local', '$2y$12$a97bo0U5kvxCY3NkZTmtGeSM9akRzRDKJbJyG4QQMCX7ElP8K2VVS', 'staff');

-- You can add more seed data for clients, services, jobs here later
