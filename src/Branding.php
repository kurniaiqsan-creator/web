<?php

declare(strict_types=1);

/**
 * Branding per-tenant.
 *
 * Mengubah `tenants.branding.primary_color` menjadi blok <style> yang meng-override
 * CSS var `--cui-primary` / `--cui-primary-rgb` (dan turunan seat-selected) untuk
 * theme light & dark. Dipakai di layout admin & publik.
 *
 * Kalau tenant tidak punya warna kustom, fungsi mengembalikan string kosong sehingga
 * palet default Visi (orange) dari visi.css tetap berlaku.
 */
class Branding
{
    /** Cache tenant aktif (per-request) supaya tidak query berulang. */
    private static ?array $cachedTenant = null;
    private static bool $cacheLoaded = false;

    /**
     * Ambil URL logo tenant (atau '' jika tidak ada).
     * Bila $tenant null, fallback ke tenant pada session (admin area).
     */
    public static function logoUrl(?array $tenant = null): string
    {
        $branding = self::resolveBranding($tenant);
        $logo = isset($branding['logo_url']) ? trim((string)$branding['logo_url']) : '';
        return $logo;
    }

    /**
     * Judul situs (suffix tab browser & brand fallback).
     * Dari `tenants.branding.site_title`, default 'Visi'.
     * Bila $tenant null, fallback ke tenant pada session (admin area).
     */
    public static function siteTitle(?array $tenant = null): string
    {
        $branding = self::resolveBranding($tenant);
        $val = isset($branding['site_title']) ? trim((string)$branding['site_title']) : '';
        return $val !== '' ? $val : 'Visi';
    }

    /**
     * Teks footer (setelah "© TAHUN").
     * Dari `tenants.branding.footer_text`, default 'Visi — Platform Tiket'.
     * Bila $tenant null, fallback ke tenant pada session (admin area).
     */
    public static function footerText(?array $tenant = null): string
    {
        $branding = self::resolveBranding($tenant);
        $val = isset($branding['footer_text']) ? trim((string)$branding['footer_text']) : '';
        return $val !== '' ? $val : 'Visi — Platform Tiket';
    }

    /**
     * Tagline footer (deskripsi singkat di kolom brand footer).
     * Dari `tenants.branding.footer_tagline`, default teks discovery.
     */
    public static function footerTagline(?array $tenant = null): string
    {
        $branding = self::resolveBranding($tenant);
        $val = isset($branding['footer_tagline']) ? trim((string)$branding['footer_tagline']) : '';
        return $val !== '' ? $val
            : 'Platform penemu event lokal terbaik. Temukan, simpan, dan ikuti event seru di sekitarmu.';
    }

    /** Nama tenant aktif dari session (admin area), atau '' jika tidak ada. */
    public static function tenantName(): string
    {
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return '';
        }
        try {
            $row = Database::fetch('SELECT name FROM tenants WHERE id = ?', [$tenantId]);
            return (string)($row['name'] ?? '');
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Hasilkan <style> override untuk tenant tertentu.
     * Bila $tenant null, fallback ke tenant pada session (admin area).
     */
    public static function styleTag(?array $tenant = null): string
    {
        $branding = self::resolveBranding($tenant);
        $primary = isset($branding['primary_color']) ? trim((string)$branding['primary_color']) : '';

        $rgb = self::hexToRgb($primary);
        if ($rgb === null) {
            return '';
        }

        [$r, $g, $b] = $rgb;
        $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
        $rgbStr = "{$r}, {$g}, {$b}";

        // Warna turunan untuk hover/active (gelapkan) & varian dark mode (cerahkan).
        $hover  = self::shade($r, $g, $b, -0.12);
        $active = self::shade($r, $g, $b, -0.24);
        $darkPrimary = self::shade($r, $g, $b, 0.18);
        $darkRgb = self::hexToRgb($darkPrimary);
        $darkRgbStr = $darkRgb ? "{$darkRgb[0]}, {$darkRgb[1]}, {$darkRgb[2]}" : $rgbStr;

        return <<<HTML
<style id="visi-tenant-branding">
:root, [data-coreui-theme="light"] {
  --cui-primary: {$hex};
  --cui-primary-rgb: {$rgbStr};
  --visi-seat-selected: {$hex};
  --visi-seat-selected-stroke: {$active};
}
[data-coreui-theme="dark"] {
  --cui-primary: {$darkPrimary};
  --cui-primary-rgb: {$darkRgbStr};
  --visi-seat-selected: {$darkPrimary};
  --visi-seat-selected-stroke: {$hex};
}
[data-coreui-theme="light"] .btn-primary,
[data-coreui-theme="dark"] .btn-primary {
  --cui-btn-hover-bg: {$hover};
  --cui-btn-hover-border-color: {$hover};
  --cui-btn-active-bg: {$active};
  --cui-btn-active-border-color: {$active};
}
</style>
HTML;
    }

    /** @return array<string,mixed> */
    private static function resolveBranding(?array $tenant): array
    {
        if ($tenant !== null) {
            return self::decodeBranding($tenant['branding'] ?? null);
        }

        // Fallback: tenant dari session (area admin).
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return [];
        }

        if (!self::$cacheLoaded) {
            self::$cacheLoaded = true;
            try {
                self::$cachedTenant = Database::fetch(
                    'SELECT branding FROM tenants WHERE id = ?',
                    [$tenantId]
                );
            } catch (Throwable $e) {
                self::$cachedTenant = null;
            }
        }

        return self::decodeBranding(self::$cachedTenant['branding'] ?? null);
    }

    /** @return array<string,mixed> */
    private static function decodeBranding(mixed $branding): array
    {
        if (is_array($branding)) {
            return $branding;
        }
        if (is_string($branding) && $branding !== '') {
            $decoded = json_decode($branding, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Parse hex (#rgb / #rrggbb) ke [r,g,b]. Return null kalau bukan hex valid.
     * @return array{0:int,1:int,2:int}|null
     */
    private static function hexToRgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');
        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }
        return [
            (int)hexdec(substr($hex, 0, 2)),
            (int)hexdec(substr($hex, 2, 2)),
            (int)hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Geser warna lebih gelap (amount negatif) atau lebih terang (positif).
     * amount dalam rentang -1..1.
     */
    private static function shade(int $r, int $g, int $b, float $amount): string
    {
        if ($amount < 0) {
            $factor = 1 + $amount;
            $r = (int)round($r * $factor);
            $g = (int)round($g * $factor);
            $b = (int)round($b * $factor);
        } else {
            $r = (int)round($r + (255 - $r) * $amount);
            $g = (int)round($g + (255 - $g) * $amount);
            $b = (int)round($b + (255 - $b) * $amount);
        }
        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
