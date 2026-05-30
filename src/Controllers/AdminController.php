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

    public function customers(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        $customers = Database::fetchAll(
            "SELECT
                COALESCE(u.id, 0) AS user_id,
                COALESCE(u.name, o.customer_name, '(tanpa nama)') AS name,
                COALESCE(u.email, o.customer_email) AS email,
                COALESCE(u.phone, o.customer_phone) AS phone,
                COUNT(o.id) AS order_count,
                COALESCE(SUM(CASE WHEN o.status = 'paid' THEN o.total_amount_cents ELSE 0 END), 0) AS total_spent_cents,
                COALESCE(SUM(CASE WHEN o.status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_orders,
                MAX(o.created_at) AS last_order_at
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.tenant_id = ?
               AND COALESCE(u.email, o.customer_email) IS NOT NULL
             GROUP BY COALESCE(u.id, 0), COALESCE(u.email, o.customer_email),
                      COALESCE(u.name, o.customer_name, '(tanpa nama)'),
                      COALESCE(u.phone, o.customer_phone)
             ORDER BY last_order_at DESC
             LIMIT 200",
            [$tenantId]
        );

        return View::render('admin/customers', [
            'title'     => 'Customer',
            'customers' => $customers,
        ]);
    }

    public function customerDetail(string $id): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $id = urldecode($id);

        if (str_starts_with($id, 'email:')) {
            $email = substr($id, 6);
            $customer = Database::fetch(
                "SELECT
                    NULL AS id,
                    COALESCE(MAX(o.customer_name), '(tanpa nama)') AS name,
                    o.customer_email AS email,
                    MAX(o.customer_phone) AS phone,
                    COUNT(o.id) AS order_count,
                    COALESCE(SUM(CASE WHEN o.status = 'paid' THEN o.total_amount_cents ELSE 0 END), 0) AS total_spent_cents
                 FROM orders o
                 WHERE o.tenant_id = ? AND o.customer_email = ?
                 GROUP BY o.customer_email",
                [$tenantId, $email]
            );
            $orders = Database::fetchAll(
                "SELECT o.*, e.title AS event_title
                 FROM orders o
                 LEFT JOIN events e ON e.id = o.event_id
                 WHERE o.tenant_id = ? AND o.customer_email = ?
                 ORDER BY o.created_at DESC",
                [$tenantId, $email]
            );
        } else {
            $customer = Database::fetch(
                "SELECT u.id, u.name, u.email, u.phone,
                        COUNT(o.id) AS order_count,
                        COALESCE(SUM(CASE WHEN o.status = 'paid' THEN o.total_amount_cents ELSE 0 END), 0) AS total_spent_cents
                 FROM users u
                 LEFT JOIN orders o ON o.user_id = u.id AND o.tenant_id = ?
                 WHERE u.id = ?
                 GROUP BY u.id",
                [$tenantId, (int)$id]
            );
            $orders = Database::fetchAll(
                "SELECT o.*, e.title AS event_title
                 FROM orders o
                 LEFT JOIN events e ON e.id = o.event_id
                 WHERE o.tenant_id = ? AND o.user_id = ?
                 ORDER BY o.created_at DESC",
                [$tenantId, (int)$id]
            );
        }

        if (!$customer) {
            http_response_code(404);
            return Router::renderError(404, 'Customer tidak ditemukan');
        }

        return View::render('admin/customer-detail', [
            'title'    => $customer['name'] ?? 'Customer',
            'customer' => $customer,
            'orders'   => $orders,
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
