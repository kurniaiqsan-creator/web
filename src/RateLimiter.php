<?php

declare(strict_types=1);

/**
 * Rate limiter sederhana berbasis MySQL (fixed-window counter).
 *
 * Dipakai untuk membatasi request pada endpoint sensitif (API, webhook, login).
 * Strategi: per (bucket, window) hitung hits. Bucket biasanya gabungan
 * nama-endpoint + IP klien. Window = blok waktu selebar $windowSeconds.
 *
 * Desain:
 * - FAIL-OPEN: bila terjadi error DB, jangan blok trafik (kembalikan true).
 *   Lebih baik kelewat membatasi daripada menjatuhkan situs karena limiter.
 * - Tidak butuh Redis; cukup untuk skala MVP. Tabel: rate_limits (migration 006).
 */
class RateLimiter
{
    /**
     * Catat satu hit dan kembalikan true bila masih dalam batas.
     *
     * @param string $key            Pengenal bucket (mis. "api:1.2.3.4").
     * @param int    $maxHits        Maksimum hit per window.
     * @param int    $windowSeconds  Lebar window dalam detik.
     * @return bool true = boleh lanjut, false = melebihi batas.
     */
    public static function allow(string $key, int $maxHits = 60, int $windowSeconds = 60): bool
    {
        $windowStart = (int) (floor(time() / $windowSeconds) * $windowSeconds);
        $bucket = substr($key, 0, 190);

        try {
            // Upsert: tambah hit untuk (bucket, window) ini.
            Database::query(
                "INSERT INTO rate_limits (bucket, window_start, hits)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE hits = hits + 1",
                [$bucket, $windowStart]
            );

            $row = Database::fetch(
                "SELECT hits FROM rate_limits WHERE bucket = ? AND window_start = ?",
                [$bucket, $windowStart]
            );

            $hits = (int) ($row['hits'] ?? 0);
            return $hits <= $maxHits;
        } catch (Throwable $e) {
            // Fail-open: jangan jatuhkan request hanya karena limiter bermasalah.
            error_log('RateLimiter error: ' . $e->getMessage());
            return true;
        }
    }

    /** IP klien terbaik-usaha (hormati proxy umum, fallback ke REMOTE_ADDR). */
    public static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', (string) $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * Helper untuk endpoint: enforce limit, kirim 429 + JSON bila lewat.
     * Kembalikan true bila boleh lanjut. Bila false, caller harus berhenti.
     */
    public static function enforce(string $scope, int $maxHits = 60, int $windowSeconds = 60): bool
    {
        $key = $scope . ':' . self::clientIp();
        if (self::allow($key, $maxHits, $windowSeconds)) {
            return true;
        }

        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . $windowSeconds);
        echo json_encode([
            'error' => [
                'code'    => 'RATE_LIMITED',
                'message' => 'Terlalu banyak permintaan. Coba lagi sebentar.',
            ],
        ], JSON_UNESCAPED_UNICODE);
        return false;
    }

    /** Bersihkan baris window lama (opsional, dipanggil sesekali). */
    public static function gc(int $olderThanSeconds = 3600): void
    {
        try {
            Database::query(
                "DELETE FROM rate_limits WHERE window_start < ?",
                [time() - $olderThanSeconds]
            );
        } catch (Throwable $e) {
            error_log('RateLimiter gc error: ' . $e->getMessage());
        }
    }
}
