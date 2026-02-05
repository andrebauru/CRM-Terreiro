-- Migration: Add notes field to clients table
-- Run this migration to add the observations/notes field

ALTER TABLE clients ADD COLUMN notes TEXT AFTER address;

-- Update timestamp
UPDATE clients SET updated_at = CURRENT_TIMESTAMP WHERE notes IS NULL;
