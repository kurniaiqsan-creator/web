<?php

class ApiController
{
    private function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function error(string $code, string $message, int $status = 400): string
    {
        return $this->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }

    private function input(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }

    public function createTenant(): string
    {
        $data = $this->input();

        $slug = $data['slug'] ?? '';
        $name = $data['name'] ?? '';
        $ownerEmail = $data['owner']['email'] ?? '';
        $ownerName = $data['owner']['name'] ?? '';
        $branding = $data['branding'] ?? [];

        if (empty($slug) || empty($name) || empty($ownerEmail)) {
            return $this->error('VALIDATION', 'Slug, nama, dan email owner wajib diisi');
        }

        // Check slug availability
        $exist = Database::fetch('SELECT id FROM tenants WHERE slug = ?', [$slug]);
        if ($exist) {
            return $this->error('SLUG_TAKEN', 'Slug sudah digunakan');
        }

        Database::beginTransaction();
        try {
            // Create user (owner)
            $userId = Database::insert('users', [
                'email'         => $ownerEmail,
                'name'          => $ownerName,
                'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
                'role'          => 'tenant_admin',
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            // Create tenant
            $tenantId = Database::insert('tenants', [
                'slug'          => $slug,
                'name'          => $name,
                'owner_user_id' => $userId,
                'branding'      => json_encode($branding),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            // Update user tenant_id
            Database::update('users', ['tenant_id' => $tenantId], 'id = ?', [$userId]);

            Database::commit();

            return $this->json([
                'tenant_id'     => $tenantId,
                'slug'          => $slug,
                'status'        => 'created',
                'onboarding_url' => "/admin/dashboard",
            ], 201);
        } catch (Exception $e) {
            Database::rollback();
            return $this->error('CREATE_FAILED', $e->getMessage(), 500);
        }
    }

    public function createSeatHold(): string
    {
        $data = $this->input();
        $eventId = $data['event_id'] ?? 0;
        $seatLabels = $data['seats'] ?? [];
        $expiresIn = $data['expires_in_seconds'] ?? 300;

        if (empty($eventId) || empty($seatLabels)) {
            return $this->error('VALIDATION', 'event_id dan seats wajib diisi');
        }

        // Check seat availability
        $placeholders = implode(',', array_fill(0, count($seatLabels), '?'));
        $params = array_merge([$eventId], $seatLabels);

        $locked = Database::fetchAll(
            "SELECT seat_label FROM seats WHERE event_id = ? AND seat_label IN ({$placeholders}) AND status != 'available'",
            $params
        );

        if (!empty($locked)) {
            return $this->json([
                'error' => [
                    'code'    => 'SEAT_CONFLICT',
                    'message' => 'Seats not available',
                    'details' => ['conflicts' => array_column($locked, 'seat_label')],
                ]
            ], 409);
        }

        // Create hold record
        $holdUid = 'hold_' . bin2hex(random_bytes(8));
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        Database::insert('seat_holds', [
            'hold_uid'   => $holdUid,
            'event_id'   => $eventId,
            'user_id'    => $_SESSION['user_id'] ?? null,
            'seats'      => json_encode($seatLabels),
            'expires_at' => $expiresAt,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json([
            'seat_hold_id' => $holdUid,
            'event_id'     => $eventId,
            'seats'         => $seatLabels,
            'expires_at'   => $expiresAt,
            'status'       => 'active',
        ], 201);
    }

    public function releaseSeatHold(string $id): string
    {
        Database::update('seat_holds', ['status' => 'released'], 'hold_uid = ?', [$id]);
        return $this->json(['status' => 'released']);
    }

    public function createOrder(): string
    {
        $data = $this->input();
        $tenantId = (int)($data['tenant_id'] ?? ($_SESSION['tenant_id'] ?? 0));
        $holdId = $data['seat_hold_id'] ?? null;
        $items = $data['items'] ?? [];
        $customer = $data['customer'] ?? [];
        $currency = $data['currency'] ?? 'idr';
        $eventId = (int)($data['event_id'] ?? ($items[0]['event_id'] ?? 0));
        $promoCode = isset($data['promo_code']) ? strtoupper(trim((string)$data['promo_code'])) : '';

        if (empty($items) || $eventId <= 0) {
            return $this->error('VALIDATION', 'Items dan event_id wajib diisi');
        }

        Database::beginTransaction();
        try {
            // Self-healing inventory: lepaskan kursi yang dikunci order pending basi
            // (mis. user kabur tanpa bayar) sebelum cek ketersediaan.
            $this->releaseStalePendingSeats($eventId);

            // Harga otoritatif diambil dari DB, BUKAN dari client (cegah manipulasi harga).
            // Sekaligus enforce inventory: kursi harus available, lock baris (FOR UPDATE).
            $resolvedItems = [];
            $subtotalCents = 0;
            $seatLabels = [];

            foreach ($items as $item) {
                $seatLabel = isset($item['seat_label']) ? (string)$item['seat_label'] : '';

                if ($seatLabel !== '') {
                    $seat = Database::fetch(
                        "SELECT id, price_cents, status, category_id FROM seats
                         WHERE event_id = ? AND seat_label = ? FOR UPDATE",
                        [$eventId, $seatLabel]
                    );
                    if (!$seat) {
                        Database::rollback();
                        return $this->error('SEAT_NOT_FOUND', "Kursi {$seatLabel} tidak ditemukan", 422);
                    }
                    if ($seat['status'] !== 'available') {
                        Database::rollback();
                        return $this->json([
                            'error' => [
                                'code'    => 'SEAT_UNAVAILABLE',
                                'message' => "Kursi {$seatLabel} sudah tidak tersedia",
                                'details' => ['seat_label' => $seatLabel, 'status' => $seat['status']],
                            ]
                        ], 409);
                    }
                    $price = (int)$seat['price_cents'];
                    $catId = $seat['category_id'] !== null ? (int)$seat['category_id'] : null;
                    $seatLabels[] = $seatLabel;
                } else {
                    // General admission: harga dari kategori.
                    $catId = isset($item['category_id']) ? (int)$item['category_id'] : null;
                    $price = 0;
                    if ($catId !== null) {
                        $cat = Database::fetch(
                            'SELECT price_cents FROM ticket_categories WHERE id = ? AND tenant_id = ?',
                            [$catId, $tenantId]
                        );
                        if (!$cat) {
                            Database::rollback();
                            return $this->error('CATEGORY_NOT_FOUND', 'Kategori tiket tidak ditemukan', 422);
                        }
                        $price = (int)$cat['price_cents'];
                    }
                }

                $resolvedItems[] = [
                    'event_id'    => $eventId,
                    'seat_label'  => $seatLabel !== '' ? $seatLabel : null,
                    'category_id' => $catId,
                    'price_cents' => $price,
                ];
                $subtotalCents += $price;
            }

            // Evaluasi promo server-side (abaikan discount_cents dari client).
            $discountCents = 0;
            $promoApplied = null;
            if ($promoCode !== '') {
                $promo = $this->resolvePromo($promoCode, $tenantId, $eventId);
                if ($promo !== null) {
                    $discountCents = $this->computeDiscount($promo, $subtotalCents);
                    $promoApplied = $promo;
                }
            }

            $totalCents = max(0, $subtotalCents - $discountCents);
            $orderCode = 'ORD-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $orderId = Database::insert('orders', [
                'order_uid'         => 'order_' . bin2hex(random_bytes(8)),
                'tenant_id'         => $tenantId,
                'event_id'          => $eventId,
                'order_code'        => $orderCode,
                'customer_name'     => $customer['name']  ?? null,
                'customer_email'    => $customer['email'] ?? null,
                'customer_phone'    => $customer['phone'] ?? null,
                'total_amount_cents'=> $totalCents,
                'currency'          => $currency,
                'status'            => 'pending',
                'payment_provider'  => 'midtrans',
                'created_at'        => date('Y-m-d H:i:s'),
            ]);

            foreach ($resolvedItems as $item) {
                Database::insert('order_items', [
                    'order_id'     => $orderId,
                    'event_id'     => $item['event_id'],
                    'seat_label'   => $item['seat_label'],
                    'category_id'  => $item['category_id'],
                    'price_cents'  => $item['price_cents'],
                ]);
            }

            // Blokir kursi yang dipesan supaya tidak diambil order lain sebelum bayar.
            if (!empty($seatLabels)) {
                $ph = implode(',', array_fill(0, count($seatLabels), '?'));
                Database::query(
                    "UPDATE seats SET status = 'blocked'
                     WHERE event_id = ? AND seat_label IN ({$ph}) AND status = 'available'",
                    array_merge([$eventId], $seatLabels)
                );
            }

            // Track penggunaan promo.
            if ($promoApplied !== null && $discountCents > 0) {
                Database::query(
                    'UPDATE promotions SET used_count = used_count + 1 WHERE id = ?',
                    [(int)$promoApplied['id']]
                );
            }

            Database::commit();

            return $this->json([
                'order_id'        => (string)$orderId,
                'order_code'      => $orderCode,
                'subtotal_cents'  => $subtotalCents,
                'discount_cents'  => $discountCents,
                'total_amount_cents' => $totalCents,
                'currency'        => $currency,
                'status'          => 'pending',
                'payment_session' => [
                    'provider'         => 'midtrans',
                    'payment_intent_id' => 'midpi_' . bin2hex(random_bytes(4)),
                    'checkout_url'     => '/checkout/pay/' . $orderCode,
                ],
            ], 201);
        } catch (Exception $e) {
            Database::rollback();
            return $this->error('CREATE_FAILED', $e->getMessage(), 500);
        }
    }

    /**
     * Cari promo valid (aktif, dalam periode, belum habis kuota, applicable ke event).
     * Return row promo atau null.
     */
    private function resolvePromo(string $code, int $tenantId, int $eventId): ?array
    {
        $promo = Database::fetch(
            "SELECT * FROM promotions
             WHERE code = ? AND tenant_id = ?
               AND NOW() BETWEEN valid_from AND valid_to
               AND (usage_limit = 0 OR used_count < usage_limit)",
            [$code, $tenantId]
        );
        if (!$promo) {
            return null;
        }

        // Cek applicable_event_ids (kalau di-set, event harus termasuk).
        $applicable = json_decode($promo['applicable_event_ids'] ?? 'null', true);
        if (is_array($applicable) && !empty($applicable)) {
            $applicableInts = array_map('intval', $applicable);
            if (!in_array($eventId, $applicableInts, true)) {
                return null;
            }
        }

        return $promo;
    }

    /** Hitung diskon (cents) dari sebuah promo terhadap subtotal. */
    private function computeDiscount(array $promo, int $subtotalCents): int
    {
        $discount = ($promo['type'] === 'percentage')
            ? (int)round($subtotalCents * (float)$promo['value'] / 100)
            : (int)round((float)$promo['value']); // 'fixed' = nilai rupiah langsung
        return max(0, min($discount, $subtotalCents));
    }

    /**
     * Lepaskan kursi yang masih 'blocked' karena order pending yang sudah kedaluwarsa
     * (lebih tua dari TTL), lalu set order tsb 'cancelled'. Mencegah kursi terkunci
     * selamanya tanpa cron. TTL default = seat_hold_ttl di config app (fallback 300s).
     */
    private function releaseStalePendingSeats(int $eventId): void
    {
        $ttl = 300;
        $cfgPath = BASE_PATH . '/config/app.php';
        if (is_file($cfgPath)) {
            $cfg = require $cfgPath;
            $ttl = (int)($cfg['seat_hold_ttl'] ?? 300);
        }

        // Kembalikan kursi dari order pending basi ke available.
        Database::query(
            "UPDATE seats s
             JOIN order_items oi ON oi.event_id = s.event_id AND oi.seat_label = s.seat_label
             JOIN orders o ON o.id = oi.order_id
             SET s.status = 'available'
             WHERE s.event_id = ?
               AND s.status = 'blocked'
               AND o.status = 'pending'
               AND o.created_at < (NOW() - INTERVAL ? SECOND)",
            [$eventId, $ttl]
        );

        // Batalkan order pending basi untuk event ini.
        Database::query(
            "UPDATE orders SET status = 'cancelled'
             WHERE event_id = ? AND status = 'pending'
               AND created_at < (NOW() - INTERVAL ? SECOND)",
            [$eventId, $ttl]
        );
    }

    public function createPaymentIntent(): string
    {
        $data = $this->input();
        return $this->json([
            'provider'          => 'midtrans',
            'payment_intent_id' => 'midpi_' . bin2hex(random_bytes(4)),
            'checkout_url'      => '/checkout/pay/' . ($data['order_id'] ?? ''),
        ]);
    }

    public function evaluatePromo(): string
    {
        $data = $this->input();
        $codes = $data['promo_codes'] ?? [];
        $cart = $data['cart'] ?? [];
        $tenantId = (int)($data['tenant_id'] ?? ($_SESSION['tenant_id'] ?? 0));
        $eventId = (int)($data['event_id'] ?? 0);

        $totalDiscount = 0;
        $applied = [];
        $subtotal = array_sum(array_column($cart, 'price_cents'));

        foreach ($codes as $code) {
            $code = strtoupper(trim((string)$code));
            if ($code === '') {
                continue;
            }

            // Kalau tenant diketahui, pakai resolver yang event-aware & tenant-scoped.
            // Fallback ke lookup global (kompat lama) kalau tenant tidak dikirim.
            $promo = $tenantId > 0
                ? $this->resolvePromo($code, $tenantId, $eventId)
                : Database::fetch(
                    "SELECT * FROM promotions WHERE code = ?
                       AND NOW() BETWEEN valid_from AND valid_to
                       AND (usage_limit = 0 OR used_count < usage_limit)",
                    [$code]
                );

            if ($promo) {
                $discount = $this->computeDiscount($promo, $subtotal);
                if ($discount <= 0) {
                    continue;
                }
                $totalDiscount += $discount;
                $applied[] = [
                    'code'           => $promo['code'],
                    'discount_cents' => $discount,
                    'applies_to'     => array_column($cart, 'seat_label'),
                    'stackable'      => true,
                ];
            }
        }

        $totalDiscount = min($totalDiscount, $subtotal);

        return $this->json([
            'applied_promos'       => $applied,
            'total_discount_cents' => $totalDiscount,
            'final_amount_cents'   => max(0, $subtotal - $totalDiscount),
        ]);
    }

    public function validateTicket(): string
    {
        $data = $this->input();
        $token = $data['ticket_token'] ?? '';

        $ticket = Database::fetch(
            "SELECT t.*, o.order_code FROM tickets t
             JOIN orders o ON t.order_id = o.id WHERE t.ticket_token = ?",
            [$token]
        );

        if (!$ticket) {
            return $this->json([
                'ticket_id' => 0,
                'status'    => 'invalid',
                'result'    => 'invalid',
            ]);
        }

        $markUsed = $data['mark_used'] ?? false;
        if ($markUsed && $ticket['status'] === 'valid') {
            Database::update('tickets', [
                'status'  => 'used',
                'used_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$ticket['id']]);

            Database::insert('ticket_scans', [
                'ticket_id'           => $ticket['id'],
                'scanned_by_user_id'  => $_SESSION['user_id'] ?? null,
                'scanner_id'          => $data['scanner_id'] ?? 'web',
                'scanned_at'          => date('Y-m-d H:i:s'),
                'location'            => json_encode($data['location'] ?? []),
                'result'              => 'validated',
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            return $this->json([
                'ticket_id'  => $ticket['id'],
                'order_id'   => (string)$ticket['order_id'],
                'status'     => 'valid',
                'seat_label' => $ticket['seat_label'],
                'used'       => true,
                'scanned_at' => date('Y-m-d H:i:s'),
                'result'     => 'validated',
            ]);
        }

        if ($ticket['status'] === 'used') {
            return $this->json([
                'ticket_id' => $ticket['id'],
                'status'    => 'used',
                'result'    => 'already_used',
                'used_at'   => $ticket['used_at'],
            ]);
        }

        return $this->json([
            'ticket_id'  => $ticket['id'],
            'order_id'   => (string)$ticket['order_id'],
            'status'     => $ticket['status'],
            'seat_label' => $ticket['seat_label'],
            'used'       => false,
            'result'     => 'validated',
        ]);
    }

    public function getOrder(string $id): string
    {
        $order = Database::fetch(
            "SELECT o.*, oi.* FROM orders o
             LEFT JOIN order_items oi ON o.id = oi.order_id
             WHERE o.id = ? OR o.order_code = ?",
            [$id, $id]
        );

        if (!$order) {
            return $this->error('NOT_FOUND', 'Order tidak ditemukan', 404);
        }

        return $this->json($order);
    }

    public function getEventSeats(string $id): string
    {
        $seats = Database::fetchAll(
            'SELECT * FROM seats WHERE event_id = ? ORDER BY row_label, col_number',
            [$id]
        );

        return $this->json(['seats' => $seats]);
    }

    public function paymentWebhook(): string
    {
        $payload = $this->input();

        // Log webhook
        Database::insert('webhook_logs', [
            'provider'     => $payload['data']['object']['provider'] ?? 'midtrans',
            'endpoint'     => '/webhooks/payment',
            'raw_request'  => json_encode($payload),
            'headers'      => json_encode(getallheaders()),
            'success'      => 1,
            'response_code'=> 200,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        // Process payment
        $type = $payload['type'] ?? '';
        if ($type === 'payment_intent.succeeded') {
            $meta = $payload['data']['object']['metadata'] ?? [];
            $orderId = $meta['order_id'] ?? null;

            if ($orderId) {
                $this->finalizeOrder($orderId);
            }
        }

        return $this->json(['status' => 'ok']);
    }

    private function finalizeOrder(string $orderId): void
    {
        Database::beginTransaction();
        try {
            Database::update('orders', [
                'status'      => 'paid',
                'updated_at'  => date('Y-m-d H:i:s'),
            ], 'id = ? OR order_code = ?', [$orderId, $orderId]);

            // Create tickets
            $items = Database::fetchAll('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);

            foreach ($items as $item) {
                $token = bin2hex(random_bytes(16));
                $ticketId = Database::insert('tickets', [
                    'ticket_uid'   => 'tkt_' . bin2hex(random_bytes(6)),
                    'order_id'     => $item['order_id'],
                    'event_id'     => $item['event_id'],
                    'seat_label'   => $item['seat_label'],
                    'ticket_token' => $token,
                    'qr_code_url'  => '/t/' . $token,
                    'status'       => 'valid',
                    'issued_at'    => date('Y-m-d H:i:s'),
                ]);

                // Update order_items with ticket_id
                Database::update('order_items', ['ticket_id' => $ticketId], 'id = ?', [$item['id']]);

                // Mark seat as sold
                if ($item['seat_label']) {
                    Database::update('seats', ['status' => 'sold'], 'event_id = ? AND seat_label = ?', [
                        $item['event_id'], $item['seat_label']
                    ]);
                }
            }

            Database::commit();
        } catch (Exception $e) {
            Database::rollback();
            error_log('Failed to finalize order: ' . $e->getMessage());
        }
    }
}
