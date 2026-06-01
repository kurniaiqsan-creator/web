-- Migration: soft-delete untuk users & venues (Phase 2).
-- events & tenants sudah punya deleted_at. Idempotent via cek information_schema.

-- users.deleted_at
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'deleted_at'
);
SET @ddl := IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- venues.deleted_at
SET @col_exists2 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'venues' AND COLUMN_NAME = 'deleted_at'
);
SET @ddl2 := IF(@col_exists2 = 0,
    'ALTER TABLE venues ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL',
    'SELECT 1');
PREPARE stmt2 FROM @ddl2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
