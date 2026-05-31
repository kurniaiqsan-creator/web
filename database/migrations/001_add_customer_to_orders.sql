-- Migration: tambah kolom customer ke tabel orders.
-- Idempotent: aman dijalankan baik pada DB lama (kolom belum ada) maupun DB baru
-- hasil schema.sql (kolom sudah ada). Memakai information_schema + prepared stmt
-- supaya tidak butuh DELIMITER / stored procedure.

SET @add_name := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_name');
SET @sql_name := IF(@add_name = 0, 'ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) NULL AFTER order_code', 'DO 0');
PREPARE s1 FROM @sql_name;
EXECUTE s1;
DEALLOCATE PREPARE s1;

SET @add_email := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_email');
SET @sql_email := IF(@add_email = 0, 'ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_name', 'DO 0');
PREPARE s2 FROM @sql_email;
EXECUTE s2;
DEALLOCATE PREPARE s2;

SET @add_phone := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_phone');
SET @sql_phone := IF(@add_phone = 0, 'ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(32) NULL AFTER customer_email', 'DO 0');
PREPARE s3 FROM @sql_phone;
EXECUTE s3;
DEALLOCATE PREPARE s3;

SET @add_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_tenant_customer');
SET @sql_idx := IF(@add_idx = 0, 'ALTER TABLE orders ADD INDEX idx_tenant_customer (tenant_id, customer_email)', 'DO 0');
PREPARE s4 FROM @sql_idx;
EXECUTE s4;
DEALLOCATE PREPARE s4;
