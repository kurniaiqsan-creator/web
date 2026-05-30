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
}
