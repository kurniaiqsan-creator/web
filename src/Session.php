<?php

/**
 * Helpers tipis untuk session: flash message (after redirect) & CSRF token.
 * Tidak ada framework, ditulis minimal supaya gampang diganti nanti.
 */
class Session
{
    public static function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['_flash'] = ['message' => $message, 'type' => $type];
    }

    public static function consumeFlash(): ?array
    {
        if (!isset($_SESSION['_flash'])) return null;
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrfToken(), $token);
    }
}
