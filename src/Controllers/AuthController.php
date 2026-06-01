<?php

class AuthController
{
    /** Tenant default (single-tenant MVP) untuk link Daftar / Lupa password. */
    private function defaultTenant(): ?array
    {
        return Database::fetch(
            'SELECT slug, name FROM tenants WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1'
        );
    }

    public function loginForm(): string
    {
        // Sudah login sebagai admin/staff → langsung ke dashboard.
        if (!empty($_SESSION['user_id'])) {
            Router::redirect('/admin/dashboard');
        }
        // Sudah login sebagai customer → langsung ke akun.
        if (!empty($_SESSION['customer_id'])) {
            Router::redirect('/akun');
        }

        return View::render('auth/login', [
            'title'  => 'Masuk',
            'tenant' => $this->defaultTenant(),
            'next'   => $_GET['next'] ?? '',
        ]);
    }

    /**
     * Login terpadu: cari user by email (email unik global), lalu arahkan
     * sesuai role — admin/staff ke panel admin, customer ke halaman akun
     * (atau ke `next` bila aman, mis. balik ke checkout).
     */
    public function login(): string
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $next = (string)($_POST['next'] ?? '');

        $user = Database::fetch("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user && password_verify($password, $user['password_hash'])) {
            $role = (string)($user['role'] ?? '');

            // --- Admin / staff / system admin ---
            if (in_array($role, ['tenant_admin', 'staff', 'system_admin'], true)) {
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['role']        = $user['role'];
                $_SESSION['tenant_id']   = $user['tenant_id'];
                $_SESSION['user_name']   = $user['name'];
                $_SESSION['user_avatar'] = $user['avatar_url'] ?? null;

                Router::redirect('/admin/dashboard');
            }

            // --- Customer ---
            if ($role === 'customer') {
                $_SESSION['customer_id']        = (int)$user['id'];
                $_SESSION['customer_name']      = $user['name'];
                $_SESSION['customer_email']     = $user['email'];
                $_SESSION['customer_tenant_id'] = (int)$user['tenant_id'];
                $_SESSION['customer_avatar']    = $user['avatar_url'] ?? null;

                // Tautkan order guest lama (email sama) ke akun ini.
                Database::query(
                    'UPDATE orders SET user_id = ?
                     WHERE tenant_id = ? AND user_id IS NULL AND customer_email = ?',
                    [(int)$user['id'], (int)$user['tenant_id'], $user['email']]
                );

                Router::redirect($this->safeNext($next));
            }
        }

        return View::render('auth/login', [
            'title'  => 'Masuk',
            'tenant' => $this->defaultTenant(),
            'error'  => 'Email atau password salah',
            'next'   => $next,
        ]);
    }

    /** Cegah open-redirect: hanya izinkan path internal relatif. */
    private function safeNext(string $next): string
    {
        if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//') && !str_contains($next, '://')) {
            return $next;
        }
        return '/akun';
    }

    public function logout(): never
    {
        session_destroy();
        Router::redirect('/login');
    }
}
