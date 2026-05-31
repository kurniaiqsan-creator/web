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
            'user_id'    => $_SESSION['user_id'] ?? ($_SESSION['customer_id'] ?? null),
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

            // === Enforce kuota General Admission (event_inventory). ===
            // Item tanpa seat_label = tiket GA; agregasi jumlah per kategori, lalu
            // kunci baris inventaris (FOR UPDATE) dan cek tersedia = quota - sold - held.
            $gaQty = [];
            foreach ($resolvedItems as $it) {
                if ($it['seat_label'] === null && $it['category_id'] !== null) {
                    $cid = (int)$it['category_id'];
                    $gaQty[$cid] = ($gaQty[$cid] ?? 0) + 1;
                }
            }
            foreach ($gaQty as $cid => $qty) {
                $inv = Database::fetch(
                    'SELECT id, quota, sold, held FROM event_inventory
                     WHERE event_id = ? AND category_id = ? FOR UPDATE',
                    [$eventId, $cid]
                );
                if (!$inv) {
                    Database::rollback();
                    return $this->error('GA_NO_INVENTORY', 'Kuota tiket untuk kategori ini belum diatur', 422);
                }
                $available = (int)$inv['quota'] - (int)$inv['sold'] - (int)$inv['held'];
                if ($available < $qty) {
                    Database::rollback();
                    return $this->json([
                        'error' => [
                            'code'    => 'GA_SOLD_OUT',
                            'message' => 'Tiket tidak mencukupi',
                            'details' => ['category_id' => $cid, 'available' => max(0, $available), 'requested' => $qty],
                        ]
                    ], 409);
                }
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

            // Tautkan ke akun customer bila sedang login pada tenant yang sama.
            // Kalau guest (belum login), user_id tetap NULL.
            $customerUserId = null;
            if (!empty($_SESSION['customer_id'])
                && (int)($_SESSION['customer_tenant_id'] ?? 0) === $tenantId) {
                $customerUserId = (int)$_SESSION['customer_id'];
            }

            $orderId = Database::insert('orders', [
                'order_uid'         => 'order_' . bin2hex(random_bytes(8)),
                'tenant_id'         => $tenantId,
                'user_id'           => $customerUserId,
                'event_id'          => $eventId,
                'order_code'        => $orderCode,
                'customer_name'     => $customer['name']  ?? null,
                'customer_email'    => $customer['email'] ?? null,
                'customer_phone'    => $customer['phone'] ?? null,
                'total_amount_cents'=> $totalCents,
                'currency'          => $currency,
                'status'            => 'pending',
                'payment_provider'  => 'pakasir',
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

            // Tahan kuota GA: tambah held sebanyak qty per kategori (dilepas saat
            // order dibayar → pindah ke sold, atau saat order pending basi dibatalkan).
            foreach ($gaQty as $cid => $qty) {
                Database::query(
                    'UPDATE event_inventory SET held = held + ?
                     WHERE event_id = ? AND category_id = ?',
                    [$qty, $eventId, $cid]
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

            // Bangun URL pembayaran Pakasir (kalau dikonfigurasi).
            $tenantSettings = $this->tenantSettings($tenantId);
            $pakasir = Pakasir::fromConfig($tenantSettings);
            $paymentUrl = null;
            if ($pakasir !== null) {
                $slug = Database::fetch('SELECT slug FROM tenants WHERE id = ?', [$tenantId]);
                $redirect = ($slug && $eventId)
                    ? base_url('/' . $slug['slug'] . '/events/' . $eventId . '/confirmation?order=' . rawurlencode($orderCode))
                    : null;
                $paymentUrl = $pakasir->paymentUrl($totalCents, $orderCode, $redirect, 'all');
            }

            return $this->json([
                'order_id'        => (string)$orderId,
                'order_code'      => $orderCode,
                'subtotal_cents'  => $subtotalCents,
                'discount_cents'  => $discountCents,
                'total_amount_cents' => $totalCents,
                'currency'        => $currency,
                'status'          => 'pending',
                'payment_session' => [
                    'provider'     => 'pakasir',
                    'checkout_url' => $paymentUrl,
                    'configured'   => $pakasir !== null,
                    'sandbox'      => Pakasir::isSandbox(),
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

        // Lepas held GA dari order pending basi: kurangi event_inventory.held sebanyak
        // jumlah item GA (tanpa seat_label) pada order tsb. GREATEST jaga-jaga underflow.
        Database::query(
            "UPDATE event_inventory ei
             JOIN (
                 SELECT oi.category_id, COUNT(*) AS qty
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 WHERE oi.event_id = ?
                   AND oi.seat_label IS NULL
                   AND oi.category_id IS NOT NULL
                   AND o.status = 'pending'
                   AND o.created_at < (NOW() - INTERVAL ? SECOND)
                 GROUP BY oi.category_id
             ) rel ON rel.category_id = ei.category_id
             SET ei.held = GREATEST(ei.held - rel.qty, 0)
             WHERE ei.event_id = ?",
            [$eventId, $ttl, $eventId]
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
        $orderCode = (string)($data['order_id'] ?? '');
        if ($orderCode === '') {
            return $this->error('VALIDATION', 'order_id wajib diisi');
        }

        $order = Database::fetch('SELECT * FROM orders WHERE order_code = ?', [$orderCode]);
        if (!$order) {
            return $this->error('NOT_FOUND', 'Order tidak ditemukan', 404);
        }

        $tenantSettings = $this->tenantSettings((int)$order['tenant_id']);
        $pakasir = Pakasir::fromConfig($tenantSettings);
        if ($pakasir === null) {
            return $this->error('PG_NOT_CONFIGURED', 'Payment gateway (Pakasir) belum dikonfigurasi', 503);
        }

        $amount = (int)$order['total_amount_cents'];
        $method = (string)($data['method'] ?? 'all');

        // Redirect kembali ke halaman konfirmasi setelah bayar.
        $redirect = null;
        $slug = Database::fetch('SELECT slug FROM tenants WHERE id = ?', [(int)$order['tenant_id']]);
        if ($slug && $order['event_id']) {
            $redirect = base_url('/' . $slug['slug'] . '/events/' . $order['event_id'] . '/confirmation?order=' . rawurlencode($orderCode));
        }

        $paymentUrl = $pakasir->paymentUrl($amount, $orderCode, $redirect, $method);

        Database::update('orders',
            ['payment_provider' => 'pakasir', 'payment_reference' => $orderCode],
            'id = ?', [(int)$order['id']]
        );

        return $this->json([
            'provider'     => 'pakasir',
            'order_code'   => $orderCode,
            'amount'       => $amount,
            'checkout_url' => $paymentUrl,
            'sandbox'      => Pakasir::isSandbox(),
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

    /**
     * POST /api/v1/payments/simulate — simulasi pembayaran sukses (HANYA sandbox).
     * Body: { order_code }. Memanggil Pakasir paymentsimulation lalu finalize lokal.
     */
    public function simulatePayment(): string
    {
        if (!Pakasir::isSandbox()) {
            return $this->error('FORBIDDEN', 'Simulasi hanya tersedia di mode sandbox', 403);
        }

        $data = $this->input();
        $orderCode = (string)($data['order_code'] ?? '');
        $order = $orderCode !== ''
            ? Database::fetch('SELECT * FROM orders WHERE order_code = ?', [$orderCode])
            : null;
        if (!$order) {
            return $this->error('NOT_FOUND', 'Order tidak ditemukan', 404);
        }

        $tenantSettings = $this->tenantSettings((int)$order['tenant_id']);
        $pakasir = Pakasir::fromConfig($tenantSettings);

        // Kalau Pakasir terkonfigurasi, panggil API simulasi (memicu webhook asli).
        if ($pakasir !== null) {
            $res = $pakasir->simulate((int)$order['total_amount_cents'], $orderCode);
            if (!$res['ok']) {
                return $this->error('SIMULATE_FAILED', $res['error'] ?? 'Gagal simulasi', 502);
            }
            return $this->json(['status' => 'simulated', 'note' => 'Webhook Pakasir akan menyelesaikan order']);
        }

        // Tanpa kredensial: finalize langsung (dev lokal tanpa akun Pakasir).
        if ($order['status'] !== 'paid') {
            $this->finalizeOrder((int)$order['id']);
        }
        return $this->json(['status' => 'finalized_local']);
    }

    public function validateTicket(): string
    {
        $data = $this->input();
        $token = $data['ticket_token'] ?? '';
        $sessionTenantId = (int)($_SESSION['tenant_id'] ?? 0);

        $ticket = Database::fetch(
            "SELECT t.*, o.order_code, o.tenant_id AS order_tenant_id, o.status AS order_status
             FROM tickets t
             JOIN orders o ON t.order_id = o.id WHERE t.ticket_token = ?",
            [$token]
        );

        // Tiket tidak ada, ATAU milik tenant lain (cegah staff lintas-tenant memvalidasi).
        if (!$ticket || ($sessionTenantId > 0 && (int)$ticket['order_tenant_id'] !== $sessionTenantId)) {
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
                'order_id'   => $ticket['order_code'],
                'status'     => 'valid',
                'seat_label' => $ticket['seat_label'],
                'used'       => true,
                'scanned_at' => date('Y-m-d H:i:s'),
                'result'     => 'validated',
            ]);
        }

        if ($ticket['status'] === 'used') {
            // Catat percobaan scan ulang (audit: ketahuan ada yang coba masuk 2x).
            Database::insert('ticket_scans', [
                'ticket_id'           => $ticket['id'],
                'scanned_by_user_id'  => $_SESSION['user_id'] ?? null,
                'scanner_id'          => $data['scanner_id'] ?? 'web',
                'scanned_at'          => date('Y-m-d H:i:s'),
                'location'            => json_encode($data['location'] ?? []),
                'result'              => 'already_used',
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            return $this->json([
                'ticket_id'  => $ticket['id'],
                'order_id'   => $ticket['order_code'],
                'status'     => 'used',
                'seat_label' => $ticket['seat_label'],
                'result'     => 'already_used',
                'used_at'    => $ticket['used_at'],
            ]);
        }

        return $this->json([
            'ticket_id'  => $ticket['id'],
            'order_id'   => $ticket['order_code'],
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

        // Pakasir mengirim: { amount, order_id, project, status, payment_method, completed_at }
        // order_id = order_code di sistem kita.
        $orderCode = (string)($payload['order_id'] ?? '');
        $amount    = (int)($payload['amount'] ?? 0);
        $status    = (string)($payload['status'] ?? '');
        $provider  = 'pakasir';

        $verified = false;
        $finalized = false;

        // Cari order dulu (untuk tenant context & verifikasi nominal).
        $order = $orderCode !== ''
            ? Database::fetch('SELECT * FROM orders WHERE order_code = ?', [$orderCode])
            : null;

        // Verifikasi ke Pakasir (sumber kebenaran), jangan percaya payload mentah.
        if ($order && $status === 'completed') {
            $tenantSettings = $this->tenantSettings((int)$order['tenant_id']);
            $pakasir = Pakasir::fromConfig($tenantSettings);
            if ($pakasir !== null) {
                $detail = $pakasir->detail((int)$order['total_amount_cents'], $orderCode);
                if ($detail['ok'] && ($detail['transaction']['status'] ?? '') === 'completed') {
                    $verified = true;
                }
            } else {
                // Tidak ada kredensial (mis. dev tanpa setup): percayai payload tapi
                // tetap cek nominal cocok dengan order.
                $verified = ($amount === (int)$order['total_amount_cents']);
            }

            if ($verified && $order['status'] !== 'paid') {
                $this->finalizeOrder((int)$order['id']);
                $finalized = true;
            }
        }

        // Log webhook (dengan hasil verifikasi).
        Database::insert('webhook_logs', [
            'provider'            => $provider,
            'endpoint'            => '/webhooks/payment',
            'raw_request'         => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'headers'             => json_encode(getallheaders()),
            'verification_result' => json_encode(['verified' => $verified, 'finalized' => $finalized, 'order_found' => (bool)$order]),
            'processed_at'        => date('Y-m-d H:i:s'),
            'success'             => $verified ? 1 : 0,
            'response_code'       => 200,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        return $this->json(['status' => 'ok', 'verified' => $verified]);
    }

    /**
     * Ambil tenants.settings (decoded) untuk tenant tertentu.
     * @return array<string,mixed>
     */
    private function tenantSettings(int $tenantId): array
    {
        if ($tenantId <= 0) {
            return [];
        }
        $row = Database::fetch('SELECT settings FROM tenants WHERE id = ?', [$tenantId]);
        $decoded = json_decode($row['settings'] ?? '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param int $orderId ID numerik order (BUKAN order_code).
     */
    private function finalizeOrder(int $orderId): void
    {
        Database::beginTransaction();
        try {
            Database::update('orders', [
                'status'      => 'paid',
                'updated_at'  => date('Y-m-d H:i:s'),
            ], 'id = ?', [$orderId]);

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
                } elseif ($item['category_id']) {
                    // GA: pindahkan 1 tiket dari held → sold. GREATEST mencegah held
                    // jadi negatif kalau hold sudah terlanjur dilepas (mis. order basi).
                    Database::query(
                        'UPDATE event_inventory
                         SET sold = sold + 1, held = GREATEST(held - 1, 0)
                         WHERE event_id = ? AND category_id = ?',
                        [$item['event_id'], $item['category_id']]
                    );
                }
            }

            Database::commit();
        } catch (Exception $e) {
            Database::rollback();
            error_log('Failed to finalize order: ' . $e->getMessage());
            return;
        }

        // Kirim notifikasi e-ticket (email + WA) di luar transaksi DB supaya
        // panggilan SMTP/HTTP yang lambat tidak menahan lock. Kegagalan kirim
        // tidak membatalkan order (sudah lunas) — tercatat di notifications_outbox.
        try {
            Notifier::sendOrderPaid($orderId);
        } catch (Throwable $e) {
            error_log('Notifier error for order ' . $orderId . ': ' . $e->getMessage());
        }
    }
}
