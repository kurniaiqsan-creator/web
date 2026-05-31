<?php

declare(strict_types=1);

/**
 * Orkestrator notifikasi order: kirim e-ticket via email (SMTP) & WhatsApp (Fonnte).
 *
 * Setiap percobaan kirim dicatat ke notifications_outbox (status pending→sent/failed)
 * untuk audit & retry. Aman dipanggil walau kredensial belum diisi (otomatis skip,
 * dicatat sebagai 'failed' dengan alasan jelas).
 */
class Notifier
{
    /**
     * Kirim notifikasi "order lunas + e-ticket" untuk satu order.
     * Idempotensi ringan: lewati channel yang sudah pernah 'sent' untuk order ini.
     */
    public static function sendOrderPaid(int $orderId): void
    {
        $order = Database::fetch(
            "SELECT o.*, e.title AS event_title, e.start_time, v.name AS venue_name, t.name AS tenant_name, t.slug AS tenant_slug
             FROM orders o
             LEFT JOIN events e ON e.id = o.event_id
             LEFT JOIN venues v ON v.id = e.venue_id
             LEFT JOIN tenants t ON t.id = o.tenant_id
             WHERE o.id = ?",
            [$orderId]
        );
        if (!$order) {
            return;
        }

        $tickets = Database::fetchAll(
            'SELECT ticket_token, seat_label, status FROM tickets WHERE order_id = ? ORDER BY id',
            [$orderId]
        );

        $tenantSettings = self::tenantSettings((int)$order['tenant_id']);

        // ===== EMAIL =====
        $email = trim((string)($order['customer_email'] ?? ''));
        if ($email !== '' && !self::alreadySent($orderId, 'email')) {
            self::dispatchEmail($order, $tickets, $tenantSettings, $email);
        }

        // ===== WHATSAPP =====
        $phone = trim((string)($order['customer_phone'] ?? ''));
        if ($phone !== '' && !self::alreadySent($orderId, 'whatsapp')) {
            self::dispatchWhatsApp($order, $tickets, $tenantSettings, $phone);
        }
    }

    private static function dispatchEmail(array $order, array $tickets, array $tenantSettings, string $email): void
    {
        $subject = 'E-Ticket Pesanan ' . $order['order_code'] . ' — ' . ($order['event_title'] ?? 'Event');
        $html = self::buildEmailHtml($order, $tickets);

        $outboxId = Database::insert('notifications_outbox', [
            'tenant_id' => (int)$order['tenant_id'],
            'order_id'  => (int)$order['id'],
            'channel'   => 'email',
            'recipient' => $email,
            'subject'   => $subject,
            'body'      => $html,
            'status'    => 'pending',
            'attempts'  => 1,
        ]);

        $mailer = Mailer::fromConfig($tenantSettings);
        if ($mailer === null) {
            self::markFailed($outboxId, 'SMTP belum dikonfigurasi (Pengaturan > Notifikasi)');
            return;
        }

        $res = $mailer->send($email, (string)($order['customer_name'] ?? ''), $subject, $html);
        if ($res['ok']) {
            self::markSent($outboxId, '');
        } else {
            self::markFailed($outboxId, $res['error']);
        }
    }

    private static function dispatchWhatsApp(array $order, array $tickets, array $tenantSettings, string $phone): void
    {
        $message = self::buildWhatsAppText($order, $tickets);

        $outboxId = Database::insert('notifications_outbox', [
            'tenant_id' => (int)$order['tenant_id'],
            'order_id'  => (int)$order['id'],
            'channel'   => 'whatsapp',
            'recipient' => $phone,
            'subject'   => null,
            'body'      => $message,
            'status'    => 'pending',
            'attempts'  => 1,
        ]);

        $fonnte = Fonnte::fromConfig($tenantSettings);
        if ($fonnte === null) {
            self::markFailed($outboxId, 'Fonnte token belum dikonfigurasi (Pengaturan > Notifikasi)');
            return;
        }

        $res = $fonnte->send($phone, $message);
        if ($res['ok']) {
            self::markSent($outboxId, $res['response']);
        } else {
            self::markFailed($outboxId, $res['error'], $res['response']);
        }
    }

    // ===== Builders =====

    private static function buildEmailHtml(array $order, array $tickets): string
    {
        $brand = '#f97316';
        $eventTitle = htmlspecialchars((string)($order['event_title'] ?? 'Event'), ENT_QUOTES, 'UTF-8');
        $venue = htmlspecialchars((string)($order['venue_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $when = !empty($order['start_time']) ? self::fmtDate((string)$order['start_time']) : '-';
        $code = htmlspecialchars((string)$order['order_code'], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars((string)($order['customer_name'] ?? 'Pelanggan'), ENT_QUOTES, 'UTF-8');
        $total = 'Rp' . number_format((int)$order['total_amount_cents'], 0, ',', '.');

        $ticketRows = '';
        foreach ($tickets as $t) {
            $url = self::ticketUrl((string)$t['ticket_token']);
            $qr = self::qrImg((string)$t['ticket_token']);
            $seat = htmlspecialchars((string)($t['seat_label'] ?: 'GA'), ENT_QUOTES, 'UTF-8');
            $ticketRows .= '<tr>'
                . '<td style="padding:12px;border-bottom:1px solid #eee;vertical-align:middle">'
                . '<div style="font-weight:600">Kursi/Tipe: ' . $seat . '</div>'
                . '<div style="font-size:12px;color:#666;word-break:break-all">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</div>'
                . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;margin-top:6px;color:' . $brand . '">Buka E-Ticket</a>'
                . '</td>'
                . '<td style="padding:12px;border-bottom:1px solid #eee;text-align:right">'
                . ($qr !== '' ? '<img src="' . $qr . '" width="110" height="110" alt="QR">' : '')
                . '</td></tr>';
        }

        return '<!DOCTYPE html><html><body style="font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:24px">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #eee">'
            . '<div style="height:6px;background:' . $brand . '"></div>'
            . '<div style="padding:24px">'
            . '<h1 style="font-size:20px;margin:0 0 4px">Pembayaran Berhasil 🎉</h1>'
            . '<p style="color:#555;margin:0 0 16px">Halo ' . $name . ', terima kasih! Pesanan kamu sudah lunas.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:16px">'
            . '<tr><td style="color:#888;padding:4px 0">Order</td><td style="text-align:right;font-weight:600">' . $code . '</td></tr>'
            . '<tr><td style="color:#888;padding:4px 0">Event</td><td style="text-align:right;font-weight:600">' . $eventTitle . '</td></tr>'
            . '<tr><td style="color:#888;padding:4px 0">Waktu</td><td style="text-align:right">' . $when . '</td></tr>'
            . '<tr><td style="color:#888;padding:4px 0">Lokasi</td><td style="text-align:right">' . $venue . '</td></tr>'
            . '<tr><td style="color:#888;padding:4px 0">Total</td><td style="text-align:right;font-weight:700">' . $total . '</td></tr>'
            . '</table>'
            . '<h2 style="font-size:15px;margin:16px 0 8px">E-Ticket (' . count($tickets) . ')</h2>'
            . '<table style="width:100%;border-collapse:collapse">' . $ticketRows . '</table>'
            . '<p style="color:#888;font-size:12px;margin-top:16px">Tunjukkan QR code saat masuk venue. Jangan bagikan tiket ini ke orang lain.</p>'
            . '</div></div></body></html>';
    }

    private static function buildWhatsAppText(array $order, array $tickets): string
    {
        $lines = [];
        $lines[] = '*Pembayaran Berhasil* 🎉';
        $lines[] = '';
        $lines[] = 'Halo ' . (string)($order['customer_name'] ?? 'Pelanggan') . ', pesanan kamu sudah lunas.';
        $lines[] = '';
        $lines[] = '*Order:* ' . (string)$order['order_code'];
        if (!empty($order['event_title'])) {
            $lines[] = '*Event:* ' . (string)$order['event_title'];
        }
        if (!empty($order['start_time'])) {
            $lines[] = '*Waktu:* ' . self::fmtDate((string)$order['start_time']);
        }
        if (!empty($order['venue_name'])) {
            $lines[] = '*Lokasi:* ' . (string)$order['venue_name'];
        }
        $lines[] = '*Total:* Rp' . number_format((int)$order['total_amount_cents'], 0, ',', '.');
        $lines[] = '';
        $lines[] = '*E-Ticket:*';
        foreach ($tickets as $t) {
            $seat = (string)($t['seat_label'] ?: 'GA');
            $lines[] = '• ' . $seat . ': ' . self::ticketUrl((string)$t['ticket_token']);
        }
        $lines[] = '';
        $lines[] = 'Tunjukkan QR code di link saat masuk venue. Terima kasih! 🙏';
        return implode("\n", $lines);
    }

    /**
     * Coba kirim ulang satu baris outbox (dipakai dari admin log viewer).
     * Return true kalau berhasil terkirim.
     */
    public static function retry(int $outboxId): bool
    {
        $row = Database::fetch('SELECT * FROM notifications_outbox WHERE id = ?', [$outboxId]);
        if (!$row || $row['status'] === 'sent') {
            return false;
        }

        $tenantSettings = self::tenantSettings((int)$row['tenant_id']);

        // Naikkan counter percobaan.
        Database::update('notifications_outbox',
            ['attempts' => (int)$row['attempts'] + 1, 'status' => 'pending', 'last_error' => null],
            'id = ?', [$outboxId]
        );

        if ($row['channel'] === 'email') {
            $mailer = Mailer::fromConfig($tenantSettings);
            if ($mailer === null) {
                self::markFailed($outboxId, 'SMTP belum dikonfigurasi (Pengaturan > Notifikasi)');
                return false;
            }
            $name = '';
            if (!empty($row['order_id'])) {
                $o = Database::fetch('SELECT customer_name FROM orders WHERE id = ?', [(int)$row['order_id']]);
                $name = (string)($o['customer_name'] ?? '');
            }
            $res = $mailer->send((string)$row['recipient'], $name, (string)$row['subject'], (string)$row['body']);
        } else { // whatsapp
            $fonnte = Fonnte::fromConfig($tenantSettings);
            if ($fonnte === null) {
                self::markFailed($outboxId, 'Fonnte token belum dikonfigurasi (Pengaturan > Notifikasi)');
                return false;
            }
            $res = $fonnte->send((string)$row['recipient'], (string)$row['body']);
            $res['response'] = $res['response'] ?? '';
        }

        if (($res['ok'] ?? false) === true) {
            self::markSent($outboxId, $res['response'] ?? '');
            return true;
        }
        self::markFailed($outboxId, $res['error'] ?? 'Gagal kirim', $res['response'] ?? '');
        return false;
    }

    // ===== Helpers =====

    private static function ticketUrl(string $token): string
    {
        $base = function_exists('base_url') ? base_url('/t/' . $token) : ('/t/' . $token);
        // Untuk email/WA perlu URL absolut. Pakai APP_URL kalau base_url relatif.
        if (!preg_match('#^https?://#', $base)) {
            $appUrl = rtrim((string)(getenv('APP_URL') ?: ''), '/');
            if ($appUrl !== '') {
                $base = $appUrl . '/t/' . $token;
            }
        }
        return $base;
    }

    private static function qrImg(string $token): string
    {
        if (class_exists('Qr') && Qr::isAvailable()) {
            $uri = Qr::dataUri(self::ticketUrl($token), 220, 10);
            if ($uri !== null) {
                return $uri;
            }
        }
        return '';
    }

    private static function fmtDate(string $dateStr): string
    {
        try {
            $d = new DateTime($dateStr);
            return $d->format('d M Y, H:i');
        } catch (Throwable $e) {
            return $dateStr;
        }
    }

    /** @return array<string,mixed> */
    private static function tenantSettings(int $tenantId): array
    {
        if ($tenantId <= 0) {
            return [];
        }
        $row = Database::fetch('SELECT settings FROM tenants WHERE id = ?', [$tenantId]);
        $decoded = json_decode($row['settings'] ?? '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function alreadySent(int $orderId, string $channel): bool
    {
        $row = Database::fetch(
            "SELECT id FROM notifications_outbox WHERE order_id = ? AND channel = ? AND status = 'sent' LIMIT 1",
            [$orderId, $channel]
        );
        return $row !== null;
    }

    private static function markSent(int $outboxId, string $response): void
    {
        Database::update('notifications_outbox', [
            'status'            => 'sent',
            'sent_at'           => date('Y-m-d H:i:s'),
            'provider_response' => $response !== '' ? mb_substr($response, 0, 2000) : null,
        ], 'id = ?', [$outboxId]);
    }

    private static function markFailed(int $outboxId, string $error, string $response = ''): void
    {
        Database::update('notifications_outbox', [
            'status'            => 'failed',
            'last_error'        => mb_substr($error, 0, 1000),
            'provider_response' => $response !== '' ? mb_substr($response, 0, 2000) : null,
        ], 'id = ?', [$outboxId]);
    }
}
