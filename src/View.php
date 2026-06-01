<?php

class View
{
    public static function render(string $view, array $data = []): string
    {
        $viewPath = VIEW_PATH . '/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new RuntimeException("View '{$view}' tidak ditemukan di: {$viewPath}");
        }

        extract($data);
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    public static function layout(string $layout, string $content, array $data = []): string
    {
        $data['content'] = $content;
        return self::render("layouts/{$layout}", $data);
    }

    public static function e(string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function formatRupiah(int $cents): string
    {
        return 'Rp' . number_format($cents, 0, ',', '.');
    }

    public static function formatDate(string $dateStr, string $timezone = 'Asia/Jakarta'): string
    {
        $date = new DateTime($dateStr);
        $date->setTimezone(new DateTimeZone($timezone));
        return $date->format('l, d F Y, H:i');
    }

    public static function formatTime(string $dateStr): string
    {
        $date = new DateTime($dateStr);
        return $date->format('H:i');
    }

    public static function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    /**
     * Render avatar: foto bila ada `$url`, atau lingkaran gradient berisi inisial.
     * Ukuran via inline style agar bisa dipakai di header (kecil) maupun profil (besar).
     */
    public static function avatarHtml(?string $url, string $initials, int $size = 40): string
    {
        $px = max(16, $size);
        if ($url !== null && trim($url) !== '') {
            $src = self::e(base_url($url));
            return '<img src="' . $src . '" alt="Foto profil" class="visi-avatar-photo" '
                 . 'style="width:' . $px . 'px;height:' . $px . 'px">';
        }
        $fs = max(10, (int)round($px * 0.4));
        return '<span class="visi-avatar" style="width:' . $px . 'px;height:' . $px . 'px;font-size:' . $fs . 'px">'
             . self::e($initials) . '</span>';
    }

    /**
     * Daftar kategori event untuk discovery (filter chips + badge di /events).
     * Disimpan di kolom events.settings->category (tanpa kolom DB baru).
     * Key = nilai tersimpan; value = label, emoji, dan warna aksen kartu.
     */
    public static function eventCategories(): array
    {
        return [
            'konser'    => ['label' => 'Konser',    'emoji' => '🎵', 'color' => '#5C3BFE'],
            'workshop'  => ['label' => 'Workshop',  'emoji' => '💡', 'color' => '#FF6B6B'],
            'festival'  => ['label' => 'Festival',  'emoji' => '🎪', 'color' => '#F59E0B'],
            'komunitas' => ['label' => 'Komunitas', 'emoji' => '👥', 'color' => '#10B981'],
            'olahraga'  => ['label' => 'Olahraga',  'emoji' => '⚽', 'color' => '#3B82F6'],
            'lainnya'   => ['label' => 'Lainnya',   'emoji' => '📅', 'color' => '#6B7280'],
        ];
    }
}
