-- Visi Database Schema
-- MySQL 8.0+ / MariaDB 10.5+

CREATE DATABASE IF NOT EXISTS visi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE visi;

-- ===== TENANTS =====
CREATE TABLE tenants (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(128) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    owner_user_id BIGINT NULL,
    branding JSON NULL,
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- ===== USERS =====
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(32) NULL,
    avatar_url VARCHAR(1024) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('tenant_admin','staff','customer','system_admin') DEFAULT 'customer',
    tenant_id BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_role (tenant_id, role),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== VENUES =====
CREATE TABLE venues (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    lat DECIMAL(9,6) NULL,
    lng DECIMAL(9,6) NULL,
    capacity INT DEFAULT 0,
    map_image_url VARCHAR(1024) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== EVENTS =====
CREATE TABLE events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    venue_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    timezone VARCHAR(64) DEFAULT 'Asia/Jakarta',
    status ENUM('draft','published','cancelled') DEFAULT 'draft',
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_tenant_start (tenant_id, start_time),
    INDEX idx_tenant_deleted (tenant_id, deleted_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===== SEAT MAPS =====
CREATE TABLE seat_maps (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    layout JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== TICKET CATEGORIES =====
CREATE TABLE ticket_categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    name VARCHAR(128) NOT NULL,
    price_cents INT NOT NULL DEFAULT 0,
    quota INT DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== SEATS =====
CREATE TABLE seats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT NOT NULL,
    seat_map_id BIGINT NULL,
    seat_label VARCHAR(64) NOT NULL,
    row_label VARCHAR(8) NOT NULL,
    col_number INT NOT NULL,
    category_id BIGINT NULL,
    price_cents INT NOT NULL DEFAULT 0,
    status ENUM('available','blocked','sold') DEFAULT 'available',
    metadata JSON NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_event_seat (event_id, seat_label),
    INDEX idx_event_category (event_id, category_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_map_id) REFERENCES seat_maps(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== EVENT INVENTORY (General Admission / tanpa kursi) =====
-- Inventaris per-event untuk event GA. Anti-oversell via SELECT ... FOR UPDATE.
--   tersedia = quota - sold - held
CREATE TABLE event_inventory (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT NOT NULL,
    category_id BIGINT NOT NULL,
    label VARCHAR(128) NULL,
    quota INT NOT NULL DEFAULT 0,
    sold INT NOT NULL DEFAULT 0,
    held INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_event_category (event_id, category_id),
    INDEX idx_event (event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== SEAT HOLDS =====
CREATE TABLE seat_holds (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    hold_uid VARCHAR(64) NOT NULL UNIQUE,
    event_id BIGINT NOT NULL,
    user_id BIGINT NULL,
    seats JSON NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active','released','expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_expires (event_id, expires_at),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== ORDERS =====
CREATE TABLE orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_uid VARCHAR(64) NOT NULL UNIQUE,
    tenant_id BIGINT NOT NULL,
    user_id BIGINT NULL,
    event_id BIGINT NULL,
    order_code VARCHAR(64) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NULL,
    customer_email VARCHAR(255) NULL,
    customer_phone VARCHAR(32) NULL,
    total_amount_cents INT NOT NULL DEFAULT 0,
    currency VARCHAR(8) DEFAULT 'idr',
    status ENUM('pending','paid','failed','cancelled','refunded') DEFAULT 'pending',
    payment_provider VARCHAR(32) NULL,
    payment_reference VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_code (order_code),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_customer (tenant_id, customer_email),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== ORDER ITEMS =====
CREATE TABLE order_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    ticket_id BIGINT NULL,
    event_id BIGINT NOT NULL,
    seat_label VARCHAR(64) NULL,
    category_id BIGINT NULL,
    price_cents INT NOT NULL DEFAULT 0,
    metadata JSON NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===== TICKETS =====
CREATE TABLE tickets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ticket_uid VARCHAR(64) NOT NULL UNIQUE,
    order_id BIGINT NOT NULL,
    event_id BIGINT NOT NULL,
    seat_label VARCHAR(64) NULL,
    ticket_token CHAR(36) NOT NULL UNIQUE,
    qr_code_url VARCHAR(1024) NULL,
    status ENUM('valid','used','refunded','cancelled') DEFAULT 'valid',
    issued_at DATETIME NULL,
    used_at DATETIME NULL,
    metadata JSON NULL,
    INDEX idx_ticket_token (ticket_token),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===== PAYMENTS =====
CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    provider VARCHAR(32) NOT NULL,
    provider_payment_id VARCHAR(255) NULL,
    amount_cents INT NOT NULL,
    currency VARCHAR(8) DEFAULT 'idr',
    status ENUM('created','processing','succeeded','failed','refunded') DEFAULT 'created',
    raw_payload JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_payment (provider, provider_payment_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== PROMOTIONS =====
CREATE TABLE promotions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT NOT NULL,
    code VARCHAR(64) NOT NULL UNIQUE,
    type ENUM('percentage','fixed') DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    usage_limit INT DEFAULT 0,
    used_count INT DEFAULT 0,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL,
    applicable_event_ids JSON NULL,
    rules_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== WEBHOOK LOGS =====
CREATE TABLE webhook_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(64) NULL,
    endpoint VARCHAR(255) NULL,
    raw_request JSON NULL,
    headers JSON NULL,
    verification_result JSON NULL,
    processed_at DATETIME NULL,
    success BOOLEAN DEFAULT 0,
    response_code INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===== TICKET SCANS =====
CREATE TABLE ticket_scans (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT NOT NULL,
    scanned_by_user_id BIGINT NULL,
    scanner_id VARCHAR(128) NULL,
    scanned_at DATETIME NOT NULL,
    location JSON NULL,
    result ENUM('validated','already_used','invalid') NOT NULL,
    photo_url VARCHAR(1024) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== REFUNDS =====
CREATE TABLE refunds (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    refund_uid VARCHAR(64) NOT NULL UNIQUE,
    order_id BIGINT NOT NULL,
    provider_refund_id VARCHAR(255) NULL,
    amount_cents INT NOT NULL,
    reason VARCHAR(255) NULL,
    status ENUM('requested','processing','succeeded','failed') DEFAULT 'requested',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== NOTIFICATIONS OUTBOX (email & WhatsApp) =====
CREATE TABLE notifications_outbox (
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

-- ===== PASSWORD RESETS =====
CREATE TABLE password_resets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_hash),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== SEED DATA (DEMO) =====
INSERT INTO tenants (slug, name, branding, created_at) VALUES
('acoustic-nights', 'Acoustic Nights', '{"logo_url":"","primary_color":"#FF5722","email_from":"no-reply@acousticnights.com","reply_to":"support@acousticnights.com"}', NOW());

INSERT INTO users (email, name, password_hash, role, tenant_id, created_at) VALUES
('admin@acousticnights.com', 'Budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tenant_admin', 1, NOW());
-- password: password

INSERT INTO venues (tenant_id, name, address, lat, lng, capacity) VALUES
(1, 'Studio Kecil Kemang', 'Jl. Kemang 10, Jakarta Selatan', -6.229386, 106.819543, 60);

INSERT INTO events (tenant_id, venue_id, title, description, start_time, end_time, status, settings) VALUES
(1, 1, 'Konser Akustik Malam Minggu', 'Sebuah pertunjukan akustik intim bersama musisi indie terbaik.', '2026-06-15 19:00:00', '2026-06-15 22:00:00', 'published', '{"type":"seat_map","allow_overbook":false}'),
(1, 1, 'Stand-up Comedy Night', 'Malam penuh tawa bersama komika ternama.', '2026-08-10 19:00:00', '2026-08-10 22:00:00', 'published', '{"type":"seat_map","allow_overbook":false}');

INSERT INTO ticket_categories (tenant_id, name, price_cents, quota) VALUES
(1, 'VIP', 200000, 18),
(1, 'Regular', 150000, 42);

-- Generate 60 seats (6 rows x 10 cols)
-- VIP: first 3 columns per row, Regular: columns 4-10
INSERT INTO seats (event_id, seat_label, row_label, col_number, category_id, price_cents, status)
SELECT 1, CONCAT(r.lbl, '-', c.col), r.lbl, c.col,
       CASE WHEN c.col <= 3 THEN 1 ELSE 2 END,
       CASE WHEN c.col <= 3 THEN 200000 ELSE 150000 END,
       CASE WHEN c.col <= 3 AND c.col = 2 THEN 'sold' ELSE 'available' END
FROM (
    SELECT 'A' AS lbl UNION SELECT 'B' UNION SELECT 'C' UNION SELECT 'D' UNION SELECT 'E' UNION SELECT 'F'
) r
CROSS JOIN (
    SELECT 1 AS col UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) c;

INSERT INTO promotions (tenant_id, code, type, value, usage_limit, used_count, valid_from, valid_to) VALUES
(1, 'PROMO10', 'percentage', 10.00, 100, 5, '2026-01-01 00:00:00', '2026-12-31 23:59:59');
