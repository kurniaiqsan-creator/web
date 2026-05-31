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
        $categories = Database::fetchAll('SELECT * FROM ticket_categories WHERE tenant_id = ? ORDER BY price_cents', [$tenantId]);

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

    /**
     * Handle POST /admin/events (create) atau POST /admin/events/{id} (update).
     * Body: form-encoded fields + JSON 'layout' (seat positions) + JSON 'categories' (ticket categories).
     */
    public function eventSave(?string $id = null): never
    {
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            Session::flash('Sesi tenant tidak valid', 'error');
            Router::redirect('/login');
        }

        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $venueId     = (int)($_POST['venue_id'] ?? 0);
        $type        = ($_POST['type'] ?? 'seat_map') === 'general_admission' ? 'general_admission' : 'seat_map';
        $startTime   = (string)($_POST['start_time'] ?? '');
        $endTime     = (string)($_POST['end_time'] ?? '');
        $statusInput = (string)($_POST['status'] ?? 'draft');
        $status      = in_array($statusInput, ['draft', 'published', 'cancelled'], true) ? $statusInput : 'draft';
        $capacity    = (int)($_POST['capacity'] ?? 0);

        // Validasi minimum
        $errors = [];
        if ($title === '')          $errors[] = 'Nama event wajib diisi';
        if ($venueId <= 0)          $errors[] = 'Venue wajib dipilih';
        if ($startTime === '')      $errors[] = 'Tanggal mulai wajib diisi';
        if ($endTime !== '' && $startTime !== '' && strtotime($endTime) < strtotime($startTime)) {
            $errors[] = 'Tanggal selesai harus setelah tanggal mulai';
        }
        if (!empty($errors)) {
            Session::flash(implode('. ', $errors), 'error');
            Router::redirect($id ? "/admin/events/{$id}" : '/admin/events/create');
        }

        // Verifikasi venue milik tenant ini
        $venueOwn = Database::fetch('SELECT id FROM venues WHERE id = ? AND tenant_id = ?', [$venueId, $tenantId]);
        if (!$venueOwn) {
            Session::flash('Venue tidak ditemukan untuk tenant ini', 'error');
            Router::redirect($id ? "/admin/events/{$id}" : '/admin/events/create');
        }

        $settings = ['type' => $type];
        if ($type === 'general_admission') {
            $settings['capacity'] = $capacity;
        }

        $startSql = $startTime !== '' ? str_replace('T', ' ', $startTime) . ':00' : null;
        $endSql   = $endTime !== ''   ? str_replace('T', ' ', $endTime)   . ':00' : null;

        $data = [
            'tenant_id'    => $tenantId,
            'venue_id'     => $venueId,
            'title'        => $title,
            'description'  => $description !== '' ? $description : null,
            'start_time'   => $startSql,
            'end_time'     => $endSql,
            'status'       => $status,
            'settings'     => json_encode($settings, JSON_UNESCAPED_UNICODE),
        ];

        try {
            Database::beginTransaction();

            if ($id) {
                $exists = Database::fetch('SELECT id FROM events WHERE id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
                if (!$exists) {
                    Database::rollback();
                    Session::flash('Event tidak ditemukan', 'error');
                    Router::redirect('/admin/events');
                }
                Database::update('events', $data, 'id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
                $eventId = (int)$id;
            } else {
                $eventId = Database::insert('events', $data);
            }

            // Seat map: replace-all strategy (sederhana untuk MVP).
            // Layout dikirim sebagai JSON: [{label, row, col, category_id, status, x, y}, ...]
            $layoutRaw = (string)($_POST['layout'] ?? '');
            $layout = $layoutRaw !== '' ? json_decode($layoutRaw, true) : null;

            if ($type === 'seat_map' && is_array($layout)) {
                // Hapus seats lama yang belum punya order_items (mencegah hilangnya tiket terjual).
                $sold = Database::fetch(
                    'SELECT COUNT(*) AS c FROM seats WHERE event_id = ? AND status = "sold"',
                    [$eventId]
                );
                if (((int)($sold['c'] ?? 0)) > 0 && $id) {
                    // Update mode: jangan rombak seats jika sudah ada yang terjual.
                    // Hanya update kategori/status seats yang masih available/blocked.
                } else {
                    Database::query('DELETE FROM seats WHERE event_id = ?', [$eventId]);

                    $tenantCategoryIds = array_column(
                        Database::fetchAll('SELECT id FROM ticket_categories WHERE tenant_id = ?', [$tenantId]),
                        'id'
                    );

                    foreach ($layout as $seat) {
                        if (!is_array($seat)) continue;
                        $label = (string)($seat['label'] ?? '');
                        $row   = (string)($seat['row']   ?? '');
                        $col   = (int)($seat['col']    ?? 0);
                        $cat   = (int)($seat['category_id'] ?? 0);
                        $st    = (string)($seat['status'] ?? 'available');
                        if ($label === '' || $row === '' || $col <= 0) continue;
                        if ($cat > 0 && !in_array($cat, $tenantCategoryIds, true)) $cat = 0;
                        if (!in_array($st, ['available', 'blocked'], true)) $st = 'available';

                        // price_cents diambil dari kategori (kalau ada).
                        $priceCents = 0;
                        if ($cat > 0) {
                            $catRow = Database::fetch('SELECT price_cents FROM ticket_categories WHERE id = ?', [$cat]);
                            $priceCents = (int)($catRow['price_cents'] ?? 0);
                        }

                        Database::insert('seats', [
                            'event_id'    => $eventId,
                            'seat_label'  => $label,
                            'row_label'   => $row,
                            'col_number'  => $col,
                            'category_id' => $cat > 0 ? $cat : null,
                            'price_cents' => $priceCents,
                            'status'      => $st,
                            'metadata'    => json_encode([
                                'x' => $seat['x'] ?? null,
                                'y' => $seat['y'] ?? null,
                            ], JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }

            Database::commit();
            Session::flash(
                $id ? 'Event diperbarui' : 'Event dibuat',
                'success'
            );
            Router::redirect("/admin/events/{$eventId}");
        } catch (Throwable $e) {
            Database::rollback();
            error_log('eventSave error: ' . $e->getMessage());
            Session::flash('Gagal menyimpan event: ' . $e->getMessage(), 'error');
            Router::redirect($id ? "/admin/events/{$id}" : '/admin/events/create');
        }
    }

    /**
     * POST /admin/events/{id}/delete — soft-delete via status='cancelled' (schema belum punya deleted_at di events).
     */
    public function eventDelete(string $id): never
    {
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        Database::update('events', ['status' => 'cancelled'], 'id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
        Session::flash('Event dibatalkan', 'success');
        Router::redirect('/admin/events');
    }

    // ===== VENUES =====

    public function venues(): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $venues = Database::fetchAll(
            "SELECT v.*, COUNT(e.id) AS event_count
             FROM venues v
             LEFT JOIN events e ON e.venue_id = v.id
             WHERE v.tenant_id = ?
             GROUP BY v.id
             ORDER BY v.name",
            [$tenantId]
        );

        return View::render('admin/venues', [
            'title' => 'Venue', 'venues' => $venues,
        ]);
    }

    public function venueEditor(string $id = null): string
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;
        $venue = $id ? Database::fetch('SELECT * FROM venues WHERE id = ? AND tenant_id = ?', [$id, $tenantId]) : null;

        return View::render('admin/venue-editor', [
            'title' => $venue ? 'Edit Venue' : 'Buat Venue',
            'venue' => $venue,
        ]);
    }

    public function venueSave(?string $id = null): never
    {
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        $name     = trim((string)($_POST['name'] ?? ''));
        $address  = trim((string)($_POST['address'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        $lat      = (isset($_POST['lat']) && $_POST['lat'] !== '') ? (float)$_POST['lat'] : null;
        $lng      = (isset($_POST['lng']) && $_POST['lng'] !== '') ? (float)$_POST['lng'] : null;
        $mapUrl   = trim((string)($_POST['map_image_url'] ?? ''));

        if ($name === '') {
            Session::flash('Nama venue wajib diisi', 'error');
            Router::redirect($id ? "/admin/venues/{$id}" : '/admin/venues/create');
        }

        $data = [
            'tenant_id'     => $tenantId,
            'name'          => $name,
            'address'       => $address !== '' ? $address : null,
            'lat'           => $lat,
            'lng'           => $lng,
            'capacity'      => $capacity,
            'map_image_url' => $mapUrl !== '' ? $mapUrl : null,
        ];

        if ($id) {
            $exists = Database::fetch('SELECT id FROM venues WHERE id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
            if (!$exists) {
                Session::flash('Venue tidak ditemukan', 'error');
                Router::redirect('/admin/venues');
            }
            Database::update('venues', $data, 'id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
            Session::flash('Venue diperbarui', 'success');
        } else {
            Database::insert('venues', $data);
            Session::flash('Venue dibuat', 'success');
        }

        Router::redirect('/admin/venues');
    }

    public function venueDelete(string $id): never
    {
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        $used = Database::fetch('SELECT COUNT(*) AS c FROM events WHERE venue_id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
        if (((int)($used['c'] ?? 0)) > 0) {
            Session::flash('Venue masih dipakai oleh event aktif', 'error');
            Router::redirect('/admin/venues');
        }
        Database::query('DELETE FROM venues WHERE id = ? AND tenant_id = ?', [(int)$id, $tenantId]);
        Session::flash('Venue dihapus', 'success');
        Router::redirect('/admin/venues');
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
                "SELECT u.id, u.name, u.email,
                        COALESCE(u.phone, MAX(o.customer_phone)) AS phone,
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
        $tenant['branding'] = json_decode($tenant['branding'] ?? '{}', true) ?: [];
        $tenant['settings'] = json_decode($tenant['settings'] ?? '{}', true) ?: [];

        return View::render('admin/settings', [
            'title' => 'Pengaturan', 'tenant' => $tenant,
        ]);
    }

    /**
     * POST /admin/settings — section-based update.
     * section=branding | payment | notifications
     */
    public function settingsSave(): never
    {
        $tenantId = (int)($_SESSION['tenant_id'] ?? 0);
        $section  = (string)($_POST['section'] ?? '');

        $tenant = Database::fetch('SELECT * FROM tenants WHERE id = ?', [$tenantId]);
        if (!$tenant) {
            Session::flash('Tenant tidak ditemukan', 'error');
            Router::redirect('/admin/settings');
        }

        $branding = json_decode($tenant['branding'] ?? '{}', true) ?: [];
        $settings = json_decode($tenant['settings'] ?? '{}', true) ?: [];

        switch ($section) {
            case 'branding':
                $name = trim((string)($_POST['name'] ?? ''));
                if ($name !== '') {
                    Database::update('tenants', ['name' => $name], 'id = ?', [$tenantId]);
                }
                $primaryColor = trim((string)($_POST['primary_color'] ?? ''));
                $emailFrom    = trim((string)($_POST['email_from'] ?? ''));
                $replyTo      = trim((string)($_POST['reply_to'] ?? ''));
                if ($primaryColor !== '') $branding['primary_color'] = $primaryColor;
                if ($emailFrom !== '')    $branding['email_from'] = $emailFrom;
                if ($replyTo !== '')      $branding['reply_to'] = $replyTo;
                Database::update('tenants',
                    ['branding' => json_encode($branding, JSON_UNESCAPED_UNICODE)],
                    'id = ?', [$tenantId]
                );
                Session::flash('Branding disimpan');
                break;

            case 'payment':
                $provider = (string)($_POST['provider'] ?? 'midtrans');
                if (!in_array($provider, ['midtrans', 'xendit', 'doku'], true)) $provider = 'midtrans';
                $settings['payment'] = $settings['payment'] ?? [];
                $settings['payment']['provider'] = $provider;
                // Hanya update key kalau benar2 diketik (skip dot-mask).
                $serverKey = (string)($_POST['server_key'] ?? '');
                $clientKey = (string)($_POST['client_key'] ?? '');
                if ($serverKey !== '' && !str_contains($serverKey, '•')) {
                    $settings['payment']['server_key'] = $serverKey;
                }
                if ($clientKey !== '' && !str_contains($clientKey, '•')) {
                    $settings['payment']['client_key'] = $clientKey;
                }
                Database::update('tenants',
                    ['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE)],
                    'id = ?', [$tenantId]
                );
                Session::flash('Pengaturan PG disimpan');
                break;

            case 'notifications':
                $settings['notifications'] = $settings['notifications'] ?? [];
                foreach (['sendgrid_api_key', 'twilio_account_sid', 'twilio_auth_token'] as $secret) {
                    $val = (string)($_POST[$secret] ?? '');
                    if ($val !== '' && !str_contains($val, '•')) {
                        $settings['notifications'][$secret] = $val;
                    }
                }
                $twilioFrom = trim((string)($_POST['twilio_from'] ?? ''));
                if ($twilioFrom !== '') $settings['notifications']['twilio_from'] = $twilioFrom;
                Database::update('tenants',
                    ['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE)],
                    'id = ?', [$tenantId]
                );
                Session::flash('Pengaturan notifikasi disimpan');
                break;

            default:
                Session::flash('Section tidak dikenal', 'error');
        }

        Router::redirect('/admin/settings');
    }
}
