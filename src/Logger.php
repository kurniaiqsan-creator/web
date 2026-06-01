<?php

declare(strict_types=1);

/**
 * Structured logger minimalis (JSON lines) — observability ringan tanpa dependency.
 *
 * Menulis satu objek JSON per baris ke storage/logs/app-YYYY-MM-DD.log.
 * Cocok untuk di-tail, di-grep, atau dikirim ke agregator log nanti.
 *
 * Level: debug, info, warning, error.
 * Aman dipanggil kapan saja; gagal tulis tidak melempar exception.
 */
class Logger
{
    private static ?string $requestId = null;

    private static function dir(): string
    {
        $dir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /** ID unik per request, untuk korelasi antar baris log. */
    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = bin2hex(random_bytes(6));
        }
        return self::$requestId;
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'ts'         => date('c'),
            'level'      => $level,
            'message'    => $message,
            'request_id' => self::requestId(),
            'method'     => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'        => $_SERVER['REQUEST_URI'] ?? null,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        ];
        if ($context !== []) {
            $entry['context'] = self::redact($context);
        }

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        $file = self::dir() . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $m, array $c = []): void    { self::log('info', $m, $c); }
    public static function warning(string $m, array $c = []): void { self::log('warning', $m, $c); }
    public static function error(string $m, array $c = []): void   { self::log('error', $m, $c); }
    public static function debug(string $m, array $c = []): void   { self::log('debug', $m, $c); }

    /**
     * Samarkan nilai sensitif agar tidak bocor ke log.
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private static function redact(array $context): array
    {
        $sensitive = ['password', 'password_hash', 'api_key', 'token', 'secret', 'authorization', 'mail_password'];
        foreach ($context as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), $sensitive, true)) {
                $context[$k] = '***';
            } elseif (is_array($v)) {
                $context[$k] = self::redact($v);
            }
        }
        return $context;
    }
}
