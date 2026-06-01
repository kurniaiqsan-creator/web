-- Migration: kolom avatar_url untuk foto profil user (customer & admin/staff).
-- Idempotent: cek information_schema sebelum ALTER (aman di DB lama & baru).

SET @add_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar_url');
SET @sql_col := IF(@add_col = 0, 'ALTER TABLE users ADD COLUMN avatar_url VARCHAR(1024) NULL AFTER phone', 'DO 0');
PREPARE s1 FROM @sql_col;
EXECUTE s1;
DEALLOCATE PREPARE s1;
