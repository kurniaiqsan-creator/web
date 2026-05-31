-- Migration: outbox notifikasi (email & WhatsApp).
-- Setiap baris = satu pesan keluar. Dipakai untuk logging, audit, dan retry ringan.
-- Idempotent: CREATE TABLE IF NOT EXISTS aman dijalankan berulang.

CREATE TABLE IF NOT EXISTS notifications_outbox (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT NULL,
    order_id BIGINT NULL,
    channel ENUM('email','whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NULL,
    body MEDIUMTEXT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    provider_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_order (order_id),
    INDEX idx_tenant_channel (tenant_id, channel),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
