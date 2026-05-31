<?php

declare(strict_types=1);

/**
 * Client WhatsApp via Fonnte (https://fonnte.com).
 *
 * Endpoint: POST https://api.fonnte.com/send
 * Auth: header `Authorization: <TOKEN>` (tanpa Bearer).
 * Param: target (nomor), message (teks). countryCode default 62.
 *
 * Token per-tenant dari tenants.settings.notifications.fonnte_token,
 * fallback ke .env (FONNTE_TOKEN).
 */
class Fonnte
{
    private string $token;
    private string $baseUrl;

    public function __construct(string $token, ?string $baseUrl = null)
    {
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl ?: (string)(getenv('FONNTE_BASE_URL') ?: 'https://api.fonnte.com'), '/');
    }

    /**
     * @param array<string,mixed>|null $tenantSettings
     */
    public static function fromConfig(?array $tenantSettings = null): ?self
    {
        $n = $tenantSettings['notifications'] ?? [];
        if (!is_array($n)) {
            $n = [];
        }
        $token = trim((string)($n['fonnte_token'] ?? getenv('FONNTE_TOKEN') ?: ''));
        if ($token === '') {
            return null;
        }
        return new self($token);
    }

    /**
     * Normalisasi nomor Indonesia ke format Fonnte (08xxxx / 62xxxx keduanya OK;
     * di sini kita standardkan ke 62...). Return '' kalau tak valid.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }
        // Nomor Indonesia wajar 10-15 digit setelah prefix.
        if (strlen($digits) < 9 || strlen($digits) > 16) {
            return '';
        }
        return $digits;
    }

    /**
     * Kirim pesan teks WA. Return ['ok'=>bool, 'error'=>string, 'response'=>string].
     * @return array{ok:bool,error:string,response:string}
     */
    public function send(string $target, string $message): array
    {
        $normalized = self::normalizePhone($target);
        if ($normalized === '') {
            return ['ok' => false, 'error' => 'Nomor tujuan tidak valid', 'response' => ''];
        }

        $ch = curl_init($this->baseUrl . '/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'target'      => $normalized,
                'message'     => $message,
                'countryCode' => '62',
            ],
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $this->token],
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => 'cURL: ' . $err, 'response' => ''];
        }
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$raw, true);
        // Fonnte balas { status: true/false, ... } (kadang "Status" kapital).
        $ok = is_array($json) && (($json['status'] ?? $json['Status'] ?? false) === true);
        $error = '';
        if (!$ok) {
            $error = is_array($json)
                ? (string)($json['reason'] ?? $json['detail'] ?? ('HTTP ' . $status))
                : ('HTTP ' . $status);
        }

        return ['ok' => $ok, 'error' => $error, 'response' => (string)$raw];
    }
}
