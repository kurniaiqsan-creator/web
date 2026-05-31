<?php

declare(strict_types=1);

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Pembuat QR code server-side via endroid/qr-code (jika ter-install).
 *
 * Kalau library belum ada (vendor/ belum di-install), method mengembalikan
 * status 'unavailable' sehingga view bisa fallback ke QR client-side.
 */
class Qr
{
    /** Apakah library QR server-side tersedia. */
    public static function isAvailable(): bool
    {
        return class_exists(QrCode::class) && class_exists(PngWriter::class);
    }

    /**
     * Hasilkan PNG (binary string) untuk konten tertentu.
     * Return null kalau library tidak tersedia atau gagal.
     */
    public static function pngBytes(string $content, int $size = 220, int $margin = 12): ?string
    {
        if (!self::isAvailable()) {
            return null;
        }

        try {
            $qr = new QrCode(
                data: $content,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: $size,
                margin: $margin
            );
            return (new PngWriter())->write($qr)->getString();
        } catch (Throwable $e) {
            error_log('Qr::pngBytes error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hasilkan data URI (base64 PNG) untuk dipakai langsung di <img src>.
     * Return null kalau tidak tersedia.
     */
    public static function dataUri(string $content, int $size = 220, int $margin = 12): ?string
    {
        $bytes = self::pngBytes($content, $size, $margin);
        if ($bytes === null) {
            return null;
        }
        return 'data:image/png;base64,' . base64_encode($bytes);
    }
}
