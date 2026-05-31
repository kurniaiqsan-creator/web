<?php

class PublicController
{
    /** Tenant tunggal (single-tenant MVP). Diambil sekali. */
    private function defaultTenant(): ?array
    {
        return Database::fetch(
            'SELECT * FROM tenants WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1'
        );
    }

    public function home(): string
    {
        // Landing page (hero + fitur). Tenant di-pass untuk header (logo, tombol Masuk/Daftar).
        return View::render('public/home', [
            'title'  => 'Visi — Platform Tiket',
            'tenant' => $this->defaultTenant(),
        ]);
    }

    public function events(): string
    {
        $tenant = $this->defaultTenant();
        if (!$tenant) {
            http_response_code(404);
            return Router::renderError(404, 'Belum ada tenant');
        }
        $events = Database::fetchAll(
            "SELECT e.*, v.name as venue_name FROM events e
             JOIN venues v ON e.venue_id = v.id
             WHERE e.tenant_id = ? AND e.status = 'published' ORDER BY e.start_time ASC",
            [$tenant['id']]
        );
        return View::render('public/tenant-home', [
            'title' => $tenant['name'], 'tenant' => $tenant, 'events' => $events
        ]);
    }

    public function eventDetail(string $eventSlug): string
    {
        $tenant = $this->defaultTenant();
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Event tidak ditemukan'); }

        $event = Database::fetch(
            "SELECT e.*, v.name as venue_name, v.address, v.capacity
             FROM events e JOIN venues v ON e.venue_id = v.id
             WHERE e.tenant_id = ? AND e.id = ? AND e.status = 'published'",
            [$tenant['id'], $eventSlug]
        );
        if (!$event) { http_response_code(404); return Router::renderError(404, 'Event tidak ditemukan'); }

        $categories = Database::fetchAll('SELECT * FROM ticket_categories WHERE tenant_id = ?', [$tenant['id']]);
        $seats = Database::fetchAll(
            'SELECT * FROM seats WHERE event_id = ? ORDER BY row_label, col_number', [$event['id']]
        );
        $event['settings'] = json_decode($event['settings'] ?? '{}', true);

        // General Admission: ambil inventaris per kategori + harga, hitung sisa.
        $gaTiers = [];
        if (($event['settings']['type'] ?? 'seat_map') === 'general_admission') {
            $gaTiers = Database::fetchAll(
                "SELECT ei.category_id, ei.quota, ei.sold, ei.held,
                        c.name, c.price_cents,
                        GREATEST(ei.quota - ei.sold - ei.held, 0) AS available
                 FROM event_inventory ei
                 JOIN ticket_categories c ON c.id = ei.category_id
                 WHERE ei.event_id = ?
                 ORDER BY c.price_cents",
                [$event['id']]
            );
        }

        // Promo aktif untuk event ini (untuk ditampilkan di halaman event).
        $promos = Database::fetchAll(
            "SELECT code, type, value, valid_to, applicable_event_ids
             FROM promotions
             WHERE tenant_id = ?
               AND NOW() BETWEEN valid_from AND valid_to
               AND (usage_limit = 0 OR used_count < usage_limit)
             ORDER BY value DESC",
            [$tenant['id']]
        );
        // Saring: hanya promo yang applicable ke event ini (atau berlaku semua event).
        $activePromos = [];
        foreach ($promos as $p) {
            $applicable = json_decode($p['applicable_event_ids'] ?? 'null', true);
            if (is_array($applicable) && !empty($applicable)) {
                if (!in_array((int)$event['id'], array_map('intval', $applicable), true)) {
                    continue;
                }
            }
            $activePromos[] = $p;
        }

        return View::render('public/event', [
            'title'   => $event['title'],
            'tenant'  => $tenant,
            'event'   => $event,
            'categories' => $categories,
            'seats'   => $seats,
            'gaTiers' => $gaTiers,
            'activePromos' => $activePromos,
        ]);
    }

    public function checkout(string $eventSlug): string
    {
        $tenant = $this->defaultTenant();
        $event = Database::fetch(
            "SELECT e.*, v.name as venue_name FROM events e JOIN venues v ON e.venue_id = v.id WHERE e.id = ?",
            [$eventSlug]
        );
        if (!$tenant || !$event) { http_response_code(404); return Router::renderError(404, 'Event tidak ditemukan'); }

        // Prefill data pemesan bila customer sedang login pada tenant ini.
        $customer = null;
        if (!empty($_SESSION['customer_id']) && (int)($_SESSION['customer_tenant_id'] ?? 0) === (int)$tenant['id']) {
            $customer = Database::fetch(
                'SELECT name, email, phone FROM users WHERE id = ?',
                [(int)$_SESSION['customer_id']]
            );
        }

        return View::render('public/checkout', [
            'title' => 'Checkout — ' . $event['title'], 'tenant' => $tenant, 'event' => $event,
            'customer' => $customer,
        ]);
    }

    public function confirmation(string $eventSlug): string
    {
        $tenant = $this->defaultTenant();
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        $orderCode = $_GET['order'] ?? '';
        $order = $orderCode ? Database::fetch(
            'SELECT * FROM orders WHERE order_code = ? AND tenant_id = ?',
            [$orderCode, $tenant['id']]
        ) : null;

        $tickets = [];
        $paymentUrl = null;
        $qrByToken = [];
        if ($order) {
            $tickets = Database::fetchAll(
                "SELECT t.*, e.title as event_title, e.start_time, v.name as venue_name
                 FROM tickets t
                 JOIN events e ON t.event_id = e.id
                 JOIN venues v ON e.venue_id = v.id
                 WHERE t.order_id = ? ORDER BY t.id ASC",
                [$order['id']]
            );

            // QR server-side (PNG data URI) per tiket — biar konsisten dengan halaman /t/{token}.
            // Kalau library belum ada (vendor/ belum di-install), view fallback ke QR JS.
            foreach ($tickets as $t) {
                $qrByToken[$t['ticket_token']] = Qr::dataUri(base_url('/t/' . $t['ticket_token']), 200, 10);
            }

            // Order masih pending → sediakan link bayar Pakasir untuk retry.
            if ($order['status'] === 'pending') {
                $settings = json_decode($tenant['settings'] ?? '{}', true);
                $pakasir = Pakasir::fromConfig(is_array($settings) ? $settings : []);
                if ($pakasir !== null) {
                    $redirect = base_url('/events/' . $order['event_id'] . '/confirmation?order=' . rawurlencode((string)$order['order_code']));
                    $paymentUrl = $pakasir->paymentUrl((int)$order['total_amount_cents'], (string)$order['order_code'], $redirect, 'all');
                }
            }
        }

        return View::render('public/confirmation', [
            'title'      => 'Konfirmasi',
            'tenant'     => $tenant,
            'order'      => $order,
            'orderCode'  => $order['order_code'] ?? $orderCode,
            'tickets'    => $tickets,
            'qrByToken'  => $qrByToken,
            'paymentUrl' => $paymentUrl,
            'sandbox'    => Pakasir::isSandbox(),
        ]);
    }

    /**
     * POST /events/{id}/cancel-order — batalkan order pending milik tenant ini.
     */
    public function cancelOrder(string $eventSlug): string
    {
        $tenant = $this->defaultTenant();
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        $orderCode = (string)($_POST['order'] ?? '');
        $order = $orderCode !== ''
            ? Database::fetch('SELECT * FROM orders WHERE order_code = ? AND tenant_id = ?', [$orderCode, $tenant['id']])
            : null;

        if ($order && $order['status'] === 'pending') {
            Database::beginTransaction();
            try {
                Database::update('orders', ['status' => 'cancelled'], 'id = ?', [(int)$order['id']]);
                // Lepas kursi yang diblokir order ini.
                Database::query(
                    "UPDATE seats s
                     JOIN order_items oi ON oi.event_id = s.event_id AND oi.seat_label = s.seat_label
                     SET s.status = 'available'
                     WHERE oi.order_id = ? AND s.status = 'blocked'",
                    [(int)$order['id']]
                );
                // Lepas held GA.
                Database::query(
                    "UPDATE event_inventory ei
                     JOIN (SELECT category_id, COUNT(*) qty FROM order_items
                           WHERE order_id = ? AND seat_label IS NULL AND category_id IS NOT NULL
                           GROUP BY category_id) rel ON rel.category_id = ei.category_id
                     SET ei.held = GREATEST(ei.held - rel.qty, 0)
                     WHERE ei.event_id = ?",
                    [(int)$order['id'], (int)$order['event_id']]
                );
                Database::commit();
                Session::flash('Pesanan dibatalkan', 'success');
            } catch (Throwable $e) {
                Database::rollback();
                Session::flash('Gagal membatalkan pesanan', 'error');
            }
        }

        Router::redirect('/events/' . $eventSlug . '/confirmation?order=' . rawurlencode($orderCode));
    }

    public function eticket(string $token): string
    {
        $ticket = Database::fetch(
            "SELECT t.*, o.order_code, e.title as event_title, e.start_time, v.name as venue_name
             FROM tickets t JOIN orders o ON t.order_id = o.id
             JOIN events e ON t.event_id = e.id JOIN venues v ON e.venue_id = v.id
             WHERE t.ticket_token = ?", [$token]
        );
        if (!$ticket) { http_response_code(404); return Router::renderError(404, 'Tiket tidak ditemukan'); }

        // QR server-side (PNG data URI) bila library tersedia; kalau tidak, view fallback ke JS.
        $qrContent = base_url('/t/' . $ticket['ticket_token']);
        $qrDataUri = Qr::dataUri($qrContent, 200, 10);

        return View::render('public/eticket', [
            'title'     => 'E-Ticket',
            'ticket'    => $ticket,
            'qrDataUri' => $qrDataUri,
        ]);
    }

    /**
     * GET /t/{token}/qr.png — stream PNG QR untuk tiket (server-side).
     * Berguna untuk print/email yang tidak bisa eksekusi JS.
     */
    public function eticketQr(string $token): string
    {
        $ticket = Database::fetch('SELECT ticket_token FROM tickets WHERE ticket_token = ?', [$token]);
        if (!$ticket) {
            http_response_code(404);
            return Router::renderError(404, 'Tiket tidak ditemukan');
        }

        $png = Qr::pngBytes(base_url('/t/' . $token), 320, 16);
        if ($png === null) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            return 'QR generator tidak tersedia (jalankan composer install).';
        }

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . strlen($png));
        echo $png;
        return '';
    }

    public function onboarding(): string
    {
        return View::render('public/onboarding', ['title' => 'Onboarding — Visi']);
    }
}
