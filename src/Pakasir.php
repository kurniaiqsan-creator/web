<?php

declare(strict_types=1);

/**
 * Client Pakasir payment gateway (https://pakasir.com).
 *
 * Pakasir butuh `slug` (project) + `api_key`. Konfigurasi diambil per-tenant dari
 * tenants.settings.payment.pakasir, atau fallback ke .env (PAKASIR_SLUG/PAKASIR_API_KEY).
 *
 * Endpoint (lihat https://pakasir.com/p/docs):
 * - Redirect: GET  {base}/pay/{slug}/{amount}?order_id=...&redirect=...
 * - Create:   POST {base}/api/transactioncreate/{method}
 * - Detail:   GET  {base}/api/transactiondetail?project=&amount=&order_id=&api_key=
 * - Cancel:   POST {base}/api/transactioncancel
 * - Simulate: POST {base}/api/paymentsimulation  (khusus sandbox)
 *
 * Catatan amount: di codebase ini `total_amount_cents` menyimpan rupiah utuh
 * (bukan sen), jadi amount Pakasir = nilai itu langsung.
 */
class Pakasir
{
    private string $slug;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $slug, string $apiKey, ?string $baseUrl = null)
    {
        $this->slug = $slug;
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?: (string)(getenv('PAKASIR_BASE_URL') ?: 'https://app.pakasir.com'), '/');
    }

    /**
     * Bangun instance dari konfigurasi tenant (settings.payment.pakasir) dengan
     * fallback ke .env. Return null kalau slug/api_key tidak tersedia.
     *
     * @param array<string,mixed>|null $tenantSettings Hasil json_decode tenants.settings.
     */
    public static function fromConfig(?array $tenantSettings = null): ?self
    {
        $slug = '';
        $apiKey = '';

        $pk = $tenantSettings['payment']['pakasir'] ?? null;
        if (is_array($pk)) {
            $slug = trim((string)($pk['slug'] ?? ''));
            $apiKey = trim((string)($pk['api_key'] ?? ''));
        }

        if ($slug === '') {
            $slug = trim((string)(getenv('PAKASIR_SLUG') ?: ''));
        }
        if ($apiKey === '') {
            $apiKey = trim((string)(getenv('PAKASIR_API_KEY') ?: ''));
        }

        if ($slug === '' || $apiKey === '') {
            return null;
        }

        return new self($slug, $apiKey);
    }

    /** Apakah mode sandbox aktif (mengizinkan simulasi pembayaran). */
    public static function isSandbox(): bool
    {
        $val = strtolower((string)(getenv('PAKASIR_SANDBOX') ?: ''));
        return in_array($val, ['1', 'true', 'yes', 'on'], true);
    }

    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * URL halaman pembayaran untuk redirect customer (tanpa panggil API).
     * $method 'all' = tampilkan semua metode; 'qris' = QRIS only.
     */
    public function paymentUrl(int $amount, string $orderId, ?string $redirectUrl = null, string $method = 'all'): string
    {
        $qs = ['order_id' => $orderId];
        if ($redirectUrl !== null && $redirectUrl !== '') {
            $qs['redirect'] = $redirectUrl;
        }
        if ($method === 'qris') {
            $qs['qris_only'] = '1';
        }
        return $this->baseUrl . '/pay/' . rawurlencode($this->slug) . '/' . $amount . '?' . http_build_query($qs);
    }

    /**
     * Buat transaksi via API (mengembalikan detail VA/QRIS).
     * @return array{ok:bool, payment?:array, error?:string}
     */
    public function createTransaction(string $method, int $amount, string $orderId, ?string $redirectUrl = null): array
    {
        $res = $this->request('POST', '/api/transactioncreate/' . rawurlencode($method), [
            'project'      => $this->slug,
            'order_id'     => $orderId,
            'amount'       => $amount,
            'api_key'      => $this->apiKey,
            'redirect_url' => $redirectUrl,
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error']];
        }
        $json = $res['json'];
        if (!isset($json['payment'])) {
            return ['ok' => false, 'error' => $json['message'] ?? 'Gagal membuat transaksi'];
        }
        return ['ok' => true, 'payment' => $json['payment']];
    }

    /**
     * Cek status transaksi (sumber kebenaran untuk verifikasi webhook).
     * @return array{ok:bool, transaction?:array, error?:string}
     */
    public function detail(int $amount, string $orderId): array
    {
        $query = http_build_query([
            'project'  => $this->slug,
            'amount'   => $amount,
            'order_id' => $orderId,
            'api_key'  => $this->apiKey,
        ]);
        $res = $this->request('GET', '/api/transactiondetail?' . $query);

        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error']];
        }
        $json = $res['json'];
        if (!isset($json['transaction'])) {
            return ['ok' => false, 'error' => $json['message'] ?? 'Transaksi tidak ditemukan'];
        }
        return ['ok' => true, 'transaction' => $json['transaction']];
    }

    /** Simulasi pembayaran sukses (hanya sandbox). */
    public function simulate(int $amount, string $orderId): array
    {
        $res = $this->request('POST', '/api/paymentsimulation', [
            'project'  => $this->slug,
            'order_id' => $orderId,
            'amount'   => $amount,
            'api_key'  => $this->apiKey,
        ]);
        return $res['ok']
            ? ['ok' => true, 'json' => $res['json']]
            : ['ok' => false, 'error' => $res['error']];
    }

    /** Batalkan transaksi. */
    public function cancel(int $amount, string $orderId): array
    {
        $res = $this->request('POST', '/api/transactioncancel', [
            'project'  => $this->slug,
            'order_id' => $orderId,
            'amount'   => $amount,
            'api_key'  => $this->apiKey,
        ]);
        return $res['ok']
            ? ['ok' => true, 'json' => $res['json']]
            : ['ok' => false, 'error' => $res['error']];
    }

    /**
     * HTTP request helper (cURL). Body dikirim sebagai JSON untuk POST.
     * @param array<string,mixed>|null $body
     * @return array{ok:bool, json?:array, status?:int, error?:string}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $payload = json_encode(array_filter($body ?? [], static fn($v) => $v !== null), JSON_UNESCAPED_SLASHES);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => 'cURL: ' . $err];
        }
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string)$raw, true);
        if (!is_array($json)) {
            return ['ok' => false, 'status' => $status, 'error' => 'Respon non-JSON (HTTP ' . $status . ')'];
        }
        if ($status >= 400) {
            return ['ok' => false, 'status' => $status, 'json' => $json, 'error' => $json['message'] ?? ('HTTP ' . $status)];
        }
        return ['ok' => true, 'status' => $status, 'json' => $json];
    }
}
