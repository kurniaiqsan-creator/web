-- Migration: kolom deleted_at untuk soft-delete event.
-- Idempotent: cek information_schema sebelum ALTER (aman di DB lama & baru).

SET @add_col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'deleted_at');
SET @sql_col := IF(@add_col = 0, 'ALTER TABLE events ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at', 'DO 0');
PREPARE s1 FROM @sql_col;
EXECUTE s1;
DEALLOCATE PREPARE s1;

SET @add_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND INDEX_NAME = 'idx_tenant_deleted');
SET @sql_idx := IF(@add_idx = 0, 'ALTER TABLE events ADD INDEX idx_tenant_deleted (tenant_id, deleted_at)', 'DO 0');
PREPARE s2 FROM @sql_idx;
EXECUTE s2;
DEALLOCATE PREPARE s2;
