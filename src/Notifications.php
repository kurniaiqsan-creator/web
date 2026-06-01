<?php

/**
 * Feed notifikasi untuk lonceng di header publik.
 *
 * Dirancang agar AMAN dipanggil langsung dari layout (views/layouts/main.php):
 * selalu mengembalikan struktur yang konsisten dan membungkus query dalam
 * try/catch sehingga error DB tidak pernah merusak rendering halaman.
 *
 * Tidak menambah tabel baru — datanya diturunkan dari `orders` (untuk customer)
 * dan `orders` + `notifications_outbox` (untuk admin/staff).
 */
class Notifications
{
    /**
     * @return array{mode:string, unread:int, items:array<int,array<string,mixed>>}
     */
    public static function forViewer(?array $tenant): array
    {
        $empty = ['mode' => 'guest', 'unread' => 0, 'items' => []];
        if (!$tenant || !isset($tenant['id'])) {
            return $empty;
        }
        $tenantId = (int)$tenant['id'];

        try {
            // Customer yang login pada tenant ini → feed status pesanan.
            if (!empty($_SESSION['customer_id'])
                && (int)($_SESSION['customer_tenant_id'] ?? 0) === $tenantId) {
                return self::forCustomer($tenantId, (int)$_SESSION['customer_id']);
            }
            // Admin/staff yang login → ringkasan operasional ringan.
            if (!empty($_SESSION['user_id'])) {
                return self::forAdmin($tenantId);
            }
        } catch (Throwable $e) {
            return $empty;
        }

        return $empty;
    }

    /** @return array{mode:string, unread:int, items:array<int,array<string,mixed>>} */
    private static function forCustomer(int $tenantId, int $customerId): array
    {
        $orders = Database::fetchAll(
            "SELECT o.order_code, o.status, o.event_id, o.created_at, e.title AS event_title
             FROM orders o
             LEFT JOIN events e ON e.id = o.event_id
             WHERE o.tenant_id = ? AND o.user_id = ?
             ORDER BY o.created_at DESC
             LIMIT 6",
            [$tenantId, $customerId]
        );

        $items = [];
        $unread = 0;
        foreach ($orders as $o) {
            [$text, $icon, $isUnread] = self::orderPresentation((string)$o['status']);
            if ($isUnread) {
                $unread++;
            }
            $items[] = [
                'title'  => $o['event_title'] ?? 'Pesanan',
                'text'   => $text,
                'url'    => base_url('/events/' . (int)$o['event_id'] . '/confirmation?order='
                            . rawurlencode((string)$o['order_code'])),
                'time'   => self::ago((string)$o['created_at']),
                'unread' => $isUnread,
                'icon'   => $icon,
            ];
        }

        return ['mode' => 'customer', 'unread' => $unread, 'items' => $items];
    }

    /** @return array{0:string,1:string,2:bool} [teks, ikon, perlu-perhatian] */
    private static function orderPresentation(string $status): array
    {
        return match ($status) {
            'paid'      => ['Pembayaran berhasil — tiket siap', 'cil-check-circle', false],
            'pending'   => ['Menunggu pembayaran',              'cil-clock',        true],
            'failed'    => ['Pembayaran gagal',                 'cil-x-circle',     true],
            'cancelled' => ['Pesanan dibatalkan',               'cil-ban',          false],
            'refunded'  => ['Dana dikembalikan',                'cil-loop-circular',false],
            default     => ['Status pesanan diperbarui',        'cil-bell',         false],
        };
    }

    /** @return array{mode:string, unread:int, items:array<int,array<string,mixed>>} */
    private static function forAdmin(int $tenantId): array
    {
        $failed = Database::fetch(
            "SELECT COUNT(*) AS c FROM notifications_outbox WHERE tenant_id = ? AND status = 'failed'",
            [$tenantId]
        );
        $pending = Database::fetch(
            "SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ? AND status = 'pending'",
            [$tenantId]
        );

        $fc = (int)($failed['c'] ?? 0);
        $pc = (int)($pending['c'] ?? 0);

        $items = [];
        if ($fc > 0) {
            $items[] = [
                'title'  => 'Notifikasi gagal terkirim',
                'text'   => $fc . ' pesan perlu dikirim ulang',
                'url'    => base_url('/admin/logs'),
                'time'   => '',
                'unread' => true,
                'icon'   => 'cil-warning',
            ];
        }
        if ($pc > 0) {
            $items[] = [
                'title'  => 'Order menunggu pembayaran',
                'text'   => $pc . ' order masih pending',
                'url'    => base_url('/admin/orders'),
                'time'   => '',
                'unread' => true,
                'icon'   => 'cil-clock',
            ];
        }

        return ['mode' => 'admin', 'unread' => count($items), 'items' => $items];
    }

    /** Format "waktu lalu" ringkas dalam bahasa Indonesia. */
    private static function ago(string $dt): string
    {
        $ts = strtotime($dt);
        if (!$ts) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60)     return 'baru saja';
        if ($diff < 3600)   return (int)floor($diff / 60) . ' mnt lalu';
        if ($diff < 86400)  return (int)floor($diff / 3600) . ' jam lalu';
        if ($diff < 604800) return (int)floor($diff / 86400) . ' hari lalu';
        return date('d M Y', $ts);
    }
}
