<?php

/**
 * Akun customer (publik, per-tenant).
 *
 * Sengaja dipisah dari AuthController (admin) supaya session tidak bentrok:
 *   - Admin/staff  : $_SESSION['user_id'] + role (dipakai adminMiddleware).
 *   - Customer     : $_SESSION['customer_id'] + customer_tenant_id.
 * Keduanya bisa aktif berbarengan tanpa saling menimpa.
 *
 * Saat daftar/masuk, order guest lama (user_id NULL) dengan email yang sama
 * otomatis di-link ke akun ini supaya histori belanja tidak hilang.
 */
class CustomerController
{
    private function tenant(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM tenants WHERE slug = ? AND deleted_at IS NULL', [$slug]);
    }

    /** Tautkan order guest (user_id NULL) ber-email sama ke akun customer. */
    private function linkGuestOrders(int $userId, int $tenantId, string $email): void
    {
        if ($email === '') return;
        Database::query(
            'UPDATE orders SET user_id = ?
             WHERE tenant_id = ? AND user_id IS NULL AND customer_email = ?',
            [$userId, $tenantId, $email]
        );
    }

    private function loginCustomer(array $user): void
    {
        $_SESSION['customer_id']        = (int)$user['id'];
        $_SESSION['customer_name']      = $user['name'];
        $_SESSION['customer_email']     = $user['email'];
        $_SESSION['customer_tenant_id'] = (int)$user['tenant_id'];
    }

    // ===== LOGIN =====

    public function loginForm(string $tenantSlug): string
    {
        $tenant = $this->tenant($tenantSlug);
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        if (!empty($_SESSION['customer_id']) && (int)$_SESSION['customer_tenant_id'] === (int)$tenant['id']) {
            Router::redirect('/' . $tenant['slug'] . '/akun');
        }

        return View::render('auth/customer-login', [
            'title'  => 'Masuk — ' . $tenant['name'],
            'tenant' => $tenant,
            'next'   => $_GET['next'] ?? '',
        ]);
    }

    public function login(string $tenantSlug): string
    {
        $tenant = $this->tenant($tenantSlug);
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $next = (string)($_POST['next'] ?? '');

        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND tenant_id = ? AND role = 'customer'",
            [$email, $tenant['id']]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return View::render('auth/customer-login', [
                'title'  => 'Masuk — ' . $tenant['name'],
                'tenant' => $tenant,
                'error'  => 'Email atau password salah.',
                'email'  => $email,
                'next'   => $next,
            ]);
        }

        $this->loginCustomer($user);
        $this->linkGuestOrders((int)$user['id'], (int)$tenant['id'], $user['email']);

        Router::redirect($this->safeNext($next, $tenant['slug']));
    }

    // ===== REGISTER =====

    public function registerForm(string $tenantSlug): string
    {
        $tenant = $this->tenant($tenantSlug);
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        if (!empty($_SESSION['customer_id']) && (int)$_SESSION['customer_tenant_id'] === (int)$tenant['id']) {
            Router::redirect('/' . $tenant['slug'] . '/akun');
        }

        return View::render('auth/customer-register', [
            'title'  => 'Daftar — ' . $tenant['name'],
            'tenant' => $tenant,
            'next'   => $_GET['next'] ?? '',
        ]);
    }

    public function register(string $tenantSlug): string
    {
        $tenant = $this->tenant($tenantSlug);
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        $next = (string)($_POST['next'] ?? '');

        $renderError = fn(string $msg) => View::render('auth/customer-register', [
            'title'  => 'Daftar — ' . $tenant['name'],
            'tenant' => $tenant,
            'error'  => $msg,
            'name'   => $name,
            'email'  => $email,
            'phone'  => $phone,
            'next'   => $next,
        ]);

        if ($name === '' || $email === '' || $password === '') {
            return $renderError('Nama, email, dan password wajib diisi.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $renderError('Format email tidak valid.');
        }
        if (strlen($password) < 6) {
            return $renderError('Password minimal 6 karakter.');
        }
        if ($password !== $confirm) {
            return $renderError('Konfirmasi password tidak cocok.');
        }

        // Email harus unik (kolom users.email UNIQUE). Cek dulu untuk pesan ramah.
        $exists = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            return $renderError('Email sudah terdaftar. Silakan masuk.');
        }

        try {
            $userId = Database::insert('users', [
                'email'         => $email,
                'name'          => $name,
                'phone'         => $phone !== '' ? $phone : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => 'customer',
                'tenant_id'     => $tenant['id'],
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            return $renderError('Gagal membuat akun. Coba lagi.');
        }

        $this->loginCustomer([
            'id' => $userId, 'name' => $name, 'email' => $email, 'tenant_id' => $tenant['id'],
        ]);
        $this->linkGuestOrders((int)$userId, (int)$tenant['id'], $email);

        Session::flash('Akun berhasil dibuat. Selamat datang, ' . $name . '!');
        Router::redirect($this->safeNext($next, $tenant['slug']));
    }

    // ===== LOGOUT =====

    public function logout(string $tenantSlug): never
    {
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_name'],
            $_SESSION['customer_email'],
            $_SESSION['customer_tenant_id']
        );
        Router::redirect('/' . $tenantSlug);
    }

    // ===== AKUN / TIKET SAYA =====

    public function account(string $tenantSlug): string
    {
        $tenant = $this->tenant($tenantSlug);
        if (!$tenant) { http_response_code(404); return Router::renderError(404, 'Tenant tidak ditemukan'); }

        if (empty($_SESSION['customer_id']) || (int)$_SESSION['customer_tenant_id'] !== (int)$tenant['id']) {
            Router::redirect('/' . $tenant['slug'] . '/masuk?next=' . urlencode('/' . $tenant['slug'] . '/akun'));
        }

        $customerId = (int)$_SESSION['customer_id'];

        $customer = Database::fetch('SELECT id, name, email, phone FROM users WHERE id = ?', [$customerId]);

        $orders = Database::fetchAll(
            "SELECT o.*, e.title AS event_title, e.start_time, v.name AS venue_name
             FROM orders o
             LEFT JOIN events e ON e.id = o.event_id
             LEFT JOIN venues v ON v.id = e.venue_id
             WHERE o.tenant_id = ? AND o.user_id = ?
             ORDER BY o.created_at DESC",
            [$tenant['id'], $customerId]
        );

        // Kumpulkan tiket per order (untuk link e-ticket).
        $orderIds = array_map(static fn($o) => (int)$o['id'], $orders);
        $ticketsByOrder = [];
        if ($orderIds !== []) {
            $ph = implode(',', array_fill(0, count($orderIds), '?'));
            $tickets = Database::fetchAll(
                "SELECT order_id, ticket_token, seat_label, status FROM tickets
                 WHERE order_id IN ({$ph}) ORDER BY id ASC",
                $orderIds
            );
            foreach ($tickets as $t) {
                $ticketsByOrder[(int)$t['order_id']][] = $t;
            }
        }

        return View::render('public/account', [
            'title'          => 'Akun Saya — ' . $tenant['name'],
            'tenant'         => $tenant,
            'customer'       => $customer,
            'orders'         => $orders,
            'ticketsByOrder' => $ticketsByOrder,
        ]);
    }

    /** Cegah open-redirect: hanya izinkan path internal milik tenant ini. */
    private function safeNext(string $next, string $slug): string
    {
        if ($next !== '' && str_starts_with($next, '/' . $slug . '/') && !str_contains($next, '//')) {
            return $next;
        }
        return '/' . $slug . '/akun';
    }
}
