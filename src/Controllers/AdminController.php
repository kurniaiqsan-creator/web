<?php

class AdminController
{
    public function dashboard(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        $salesToday = Database::fetch(
            "SELECT COALESCE(SUM(total_amount_cents), 0) as total
             FROM orders WHERE tenant_id = ? AND status = 'paid' AND DATE(updated_at) = CURDATE()",
            [$tenantId]
        );

        $ordersPending = Database::fetch(
            "SELECT COUNT(*) as cnt FROM orders WHERE tenant_id = ? AND status = 'pending'",
            [$tenantId]
        );

        $ticketsSold = Database::fetch(
            "SELECT COUNT(*) as cnt FROM tickets t
             JOIN orders o ON t.order_id = o.id
             WHERE o.tenant_id = ? AND t.status = 'valid'",
            [$tenantId]
        );

        $recentOrders = Database::fetchAll(
            "SELECT * FROM orders WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 10",
            [$tenantId]
        );

        return View::render('admin/dashboard', [
            'title'        => 'Dashboard',
            'salesToday'   => $salesToday['total'] ?? 0,
            'ordersPending'=> $ordersPending['cnt'] ?? 0,
            'ticketsSold'  => $ticketsSold['cnt'] ?? 0,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function events(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $events = Database::fetchAll(
            "SELECT e.*, v.name as venue_name FROM events e
             JOIN venues v ON e.venue_id = v.id
             WHERE e.tenant_id = ? ORDER BY e.start_time DESC",
            [$tenantId]
        );

        return View::render('admin/events', [
            'title' => 'Event', 'events' => $events,
        ]);
    }

    public function eventEditor(string $id = null): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $event = $id ? Database::fetch('SELECT * FROM events WHERE id = ? AND tenant_id = ?', [$id, $tenantId]) : null;
        $venues = Database::fetchAll('SELECT * FROM venues WHERE tenant_id = ?', [$tenantId]);
        $categories = Database::fetchAll('SELECT * FROM ticket_categories WHERE tenant_id = ?', [$tenantId]);

        if ($event) {
            $seats = Database::fetchAll('SELECT * FROM seats WHERE event_id = ? ORDER BY row_label, col_number', [$event['id']]);
            $event['settings'] = json_decode($event['settings'] ?? '{}', true);
        } else {
            $seats = [];
        }

        return View::render('admin/event-editor', [
            'title'      => $event ? 'Edit Event' : 'Buat Event',
            'event'      => $event,
            'venues'     => $venues,
            'categories' => $categories,
            'seats'      => $seats,
        ]);
    }

    public function orders(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $orders = Database::fetchAll(
            "SELECT * FROM orders WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 50",
            [$tenantId]
        );

        return View::render('admin/orders', [
            'title' => 'Pesanan', 'orders' => $orders,
        ]);
    }

    public function reports(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        $byEvent = Database::fetchAll(
            "SELECT e.title, COUNT(o.id) as order_count, COALESCE(SUM(o.total_amount_cents), 0) as total_sales
             FROM events e
             LEFT JOIN orders o ON e.id = o.event_id AND o.status = 'paid'
             WHERE e.tenant_id = ?
             GROUP BY e.id ORDER BY e.start_time DESC",
            [$tenantId]
        );

        return View::render('admin/reports', [
            'title' => 'Laporan', 'byEvent' => $byEvent,
        ]);
    }

    public function scanner(): string
    {
        return View::render('admin/scanner', ['title' => 'Scanner Tiket']);
    }

    public function settings(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $tenant = Database::fetch('SELECT * FROM tenants WHERE id = ?', [$tenantId]);
        $tenant['branding'] = json_decode($tenant['branding'] ?? '{}', true);

        return View::render('admin/settings', [
            'title' => 'Pengaturan', 'tenant' => $tenant,
        ]);
    }
}
