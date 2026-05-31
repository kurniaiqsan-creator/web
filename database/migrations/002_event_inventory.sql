-- Migration: tabel inventaris per-event untuk General Admission (GA / tanpa kursi).
-- Setiap baris = kuota satu kategori tiket pada satu event.
-- Anti-oversell ditegakkan dengan SELECT ... FOR UPDATE pada baris ini saat order.
--
--   tersedia = quota - sold - held
--     held = jumlah tiket pada order 'pending' (belum bayar, masih dalam TTL)
--     sold = jumlah tiket pada order 'paid'
--
-- Idempotent: CREATE TABLE IF NOT EXISTS aman dijalankan berulang & pada DB baru
-- hasil schema.sql (yang sudah memuat tabel ini).

CREATE TABLE IF NOT EXISTS event_inventory (
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
