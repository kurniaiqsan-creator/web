-- Migration: tambah kolom customer ke tabel orders.
-- Jalankan terhadap database existing untuk mendukung halaman /admin/customers.
USE visi;

ALTER TABLE orders
    ADD COLUMN customer_name  VARCHAR(255) NULL AFTER order_code,
    ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_name,
    ADD COLUMN customer_phone VARCHAR(32)  NULL AFTER customer_email,
    ADD INDEX idx_tenant_customer (tenant_id, customer_email);
