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
        $tenantId = $data['tenant_id'] ?? ($_SESSION['tenant_id'] ?? 0);
        $holdId = $data['seat_hold_id'] ?? null;
        $items = $data['items'] ?? [];
        $customer = $data['customer'] ?? [];
        $currency = $data['currency'] ?? 'idr';
        $eventId = $data['event_id'] ?? ($items[0]['event_id'] ?? null);
        $discountCents = max(0, (int)($data['discount_cents'] ?? 0));

        if (empty($items)) {
            return $this->error('VALIDATION', 'Items wajib diisi');
        }

        $subtotalCents = array_sum(array_column($items, 'price_cents'));
        $totalCents = max(0, $subtotalCents - $discountCents);
        $orderCode = 'ORD-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Database::beginTransaction();
        try {
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

            foreach ($items as $item) {
                Database::insert('order_items', [
                    'order_id'     => $orderId,
                    'event_id'     => $item['event_id'] ?? $eventId,
                    'seat_label'   => $item['seat_label'] ?? null,
                    'category_id'  => $item['category_id'] ?? null,
                    'price_cents'  => $item['price_cents'] ?? 0,
                ]);
            }

            Database::commit();

            return $this->json([
                'order_id'        => (string)$orderId,
                'order_code'      => $orderCode,
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

        $totalDiscount = 0;
        $applied = [];
        $subtotal = array_sum(array_column($cart, 'price_cents'));

        foreach ($codes as $code) {
            $promo = Database::fetch(
                "SELECT * FROM promotions WHERE code = ? AND NOW() BETWEEN valid_from AND valid_to AND used_count < usage_limit",
                [strtoupper($code)]
            );

            if ($promo) {
                $discount = $promo['type'] === 'percentage'
                    ? (int)round($subtotal * $promo['value'] / 100)
                    : (int)($promo['value'] * 100);
                $discount = min($discount, $subtotal);

                $totalDiscount += $discount;
                $applied[] = [
                    'code'           => $promo['code'],
                    'discount_cents' => $discount,
                    'applies_to'     => array_column($cart, 'seat_label'),
                    'stackable'      => true,
                ];
            }
        }

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
