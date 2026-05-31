<?php

declare(strict_types=1);

/**
 * Loader .env minimalis (tanpa dependency).
 *
 * - Parsing baris `KEY=VALUE`.
 * - Mendukung komentar (`#`), baris kosong, dan prefix opsional `export `.
 * - Mendukung nilai ber-quote tunggal/ganda; escape `\n`, `\t`, `\"` di double-quote.
 * - Mendukung interpolasi `${OTHER_KEY}` dari variabel yang sudah dimuat.
 * - Nilai ditaruh ke putenv()/$_ENV/$_SERVER sehingga getenv() di config tetap jalan.
 * - TIDAK menimpa variabel environment yang sudah ada (env asli OS menang).
 */
class Env
{
    private static bool $loaded = false;

    /**
     * Muat file .env (sekali saja). Aman dipanggil walau file tidak ada.
     */
    public static function load(string $path, bool $overrideExisting = false): void
    {
        if (self::$loaded && !$overrideExisting) {
            return;
        }
        self::$loaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }

            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }

            $key = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));

            if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            $value = self::parseValue($value);

            // Jangan timpa env asli OS kecuali diminta.
            if (!$overrideExisting && self::has($key)) {
                continue;
            }

            self::set($key, $value);
        }
    }

    private static function parseValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        // Double-quoted: proses escape + interpolasi.
        if ($first === '"' && $last === '"' && strlen($value) >= 2) {
            $value = substr($value, 1, -1);
            $value = strtr($value, [
                '\\n' => "\n",
                '\\t' => "\t",
                '\\r' => "\r",
                '\\"' => '"',
                '\\\\' => '\\',
            ]);
            return self::interpolate($value);
        }

        // Single-quoted: literal (tanpa escape/interpolasi).
        if ($first === "'" && $last === "'" && strlen($value) >= 2) {
            return substr($value, 1, -1);
        }

        // Unquoted: buang komentar inline yang dipisah spasi.
        $hashPos = strpos($value, ' #');
        if ($hashPos !== false) {
            $value = rtrim(substr($value, 0, $hashPos));
        }

        return self::interpolate($value);
    }

    private static function interpolate(string $value): string
    {
        return preg_replace_callback('/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/', static function (array $m): string {
            $resolved = getenv($m[1]);
            return $resolved === false ? '' : $resolved;
        }, $value) ?? $value;
    }

    private static function has(string $key): bool
    {
        return getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key]);
    }

    private static function set(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Ambil nilai env dengan fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $value;
    }
}
