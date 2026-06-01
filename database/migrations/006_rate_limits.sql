-- Migration: rate limiting (fixed-window per bucket).
-- Dipakai RateLimiter untuk membatasi request per IP pada API & webhook.
-- Idempotent: CREATE TABLE IF NOT EXISTS aman dijalankan berulang.

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bucket VARCHAR(190) NOT NULL,
    window_start INT NOT NULL,
    hits INT NOT NULL DEFAULT 0,
    UNIQUE KEY uk_bucket_window (bucket, window_start),
    INDEX idx_window (window_start)
) ENGINE=InnoDB;
