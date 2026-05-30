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

        return View::render('public/event', [
            'title'   => $event['title'],
            'tenant'  => $tenant,
            'event'   => $event,
            'categories' => $categories,
            'seats'   => $seats,
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

        return View::render('public/checkout', [
            'title' => 'Checkout — ' . $event['title'], 'tenant' => $tenant, 'event' => $event,
        ]);
    }

    public function confirmation(string $tenantSlug, string $eventSlug): string
    {
        $tenant = Database::fetch('SELECT * FROM tenants WHERE slug = ?', [$tenantSlug]);
        return View::render('public/confirmation', [
            'title' => 'Konfirmasi', 'tenant' => $tenant,
            'orderCode' => $_GET['order'] ?? 'ORD-000789',
        ]);
    }

    public function eticket(string $token): string
    {
        $ticket = Database::fetch(
            "SELECT t.*, o.order_code, e.title as event_title, v.name as venue_name
             FROM tickets t JOIN orders o ON t.order_id = o.id
             JOIN events e ON t.event_id = e.id JOIN venues v ON e.venue_id = v.id
             WHERE t.ticket_token = ?", [$token]
        );
        if (!$ticket) { http_response_code(404); return Router::renderError(404, 'Tiket tidak ditemukan'); }

        return View::render('public/eticket', ['title' => 'E-Ticket', 'ticket' => $ticket]);
    }

    public function onboarding(): string
    {
        return View::render('public/onboarding', ['title' => 'Onboarding — Visi']);
    }
}
