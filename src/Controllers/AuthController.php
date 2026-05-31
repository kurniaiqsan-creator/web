<?php

class AuthController
{
    public function loginForm(): string
    {
        if (!empty($_SESSION['user_id'])) {
            Router::redirect('/admin/dashboard');
        }
        return View::render('auth/login', ['title' => 'Masuk']);
    }

    public function login(): string
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND role IN ('tenant_admin','staff','system_admin')",
            [$email]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['user_name'] = $user['name'];

            Router::redirect('/admin/dashboard');
        }

        return View::render('auth/login', [
            'title' => 'Masuk',
            'error' => 'Email atau password salah',
        ]);
    }

    public function logout(): never
    {
        session_destroy();
        Router::redirect('/login');
    }
}
