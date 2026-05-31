<?php

class PublicController
{
    public function home(): string
    {
        return View::render('public/home', ['title' => 'Visi — Platform Tiket']);
    }

    public function tenantHome(string $tenantSlug): string
    {
        $tenant = Database::fetch(
            'SELECT * FROM tenants WHERE slug = ? AND deleted_at IS NULL', [$tenantSlug]
        );
        if (!$tenant) {
            http_response_code(404);
            return Router::renderError(404, 'Tenant tidak ditemukan');
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

    public function eventDetail(string $tenantSlug, string $eventSlug): string
    {
        $tenant = Database::fetch('SELECT * FROM tenants WHERE slug = ? AND deleted_at IS NULL', [$tenantSlug]);
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

        return View::render('public/event', [
            'title'   => $event['title'],
            'tenant'  => $tenant,
            'event'   => $event,
            'categories' => $categories,
            'seats'   => $seats,
            'gaTiers' => $gaTiers,
        ]);
    }

    public function checkout(string $tenantSlug, string $eventSlug): string
    {
        $tenant = Database::fetch('SELECT * FROM tenants WHERE slug = ?', [$tenantSlug]);
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

    public function confirmation(string $tenantSlug, string $eventSlug): string
    {
        $tenant = Database::fetch('SELECT * FROM tenants WHERE slug = ?', [$tenantSlug]);
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        $orderCode = $_GET['order'] ?? '';
        $order = $orderCode ? Database::fetch(
            'SELECT * FROM orders WHERE order_code = ? AND tenant_id = ?',
            [$orderCode, $tenant['id']]
        ) : null;

        $tickets = [];
        if ($order) {
            $tickets = Database::fetchAll(
                "SELECT t.*, e.title as event_title, e.start_time, v.name as venue_name
                 FROM tickets t
                 JOIN events e ON t.event_id = e.id
                 JOIN venues v ON e.venue_id = v.id
                 WHERE t.order_id = ? ORDER BY t.id ASC",
                [$order['id']]
            );
        }

        return View::render('public/confirmation', [
            'title'     => 'Konfirmasi',
            'tenant'    => $tenant,
            'order'     => $order,
            'orderCode' => $order['order_code'] ?? $orderCode,
            'tickets'   => $tickets,
        ]);
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
