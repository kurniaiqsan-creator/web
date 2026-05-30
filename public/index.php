<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/views');
define('CONFIG_PATH', BASE_PATH . '/config');

// Autoload sederhana
spl_autoload_register(function (string $class): void {
    $paths = [
        BASE_PATH . '/src/' . $class . '.php',
        BASE_PATH . '/src/Controllers/' . $class . '.php',
        BASE_PATH . '/src/Models/' . $class . '.php',
        BASE_PATH . '/src/Services/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Muat config
$appConfig = require CONFIG_PATH . '/app.php';
date_default_timezone_set($appConfig['timezone']);

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Router
$router = new Router();

// ===== AUTH MIDDLEWARE =====
function authMiddleware(): void
{
    if (empty($_SESSION['user_id'])) {
        Router::redirect('/login');
    }
}

function adminMiddleware(): void
{
    authMiddleware();
    if (($_SESSION['role'] ?? '') !== 'tenant_admin' && ($_SESSION['role'] ?? '') !== 'staff') {
        http_response_code(403);
        echo Router::renderError(403, 'Akses ditolak');
        exit;
    }
}

function tenantMiddleware(): void
{
    if (empty($_SESSION['tenant_id'])) {
        Router::redirect('/login');
    }
}

// ===== ROOT =====
$router->get('/', function () {
    return (new PublicController())->home();
});

// ===== AUTH ROUTES (harus sebelum /{tenantSlug}) =====
$router->get('/login', function () {
    return (new AuthController())->loginForm();
});

$router->post('/login', function () {
    return (new AuthController())->login();
});

$router->get('/logout', function () {
    return (new AuthController())->logout();
});

$router->get('/onboarding', function () {
    return (new PublicController())->onboarding();
});

$router->post('/onboarding', function () {
    return (new ApiController())->createTenant();
});

// ===== E-TICKET (harus sebelum /{tenantSlug}) =====
$router->get('/t/{token}', function (string $token) {
    return (new PublicController())->eticket($token);
});

// ===== ADMIN ROUTES (harus sebelum /{tenantSlug}) =====
$router->group('/admin', function (Router $r) {
    $r->get('/dashboard', [AdminController::class, 'dashboard']);
    $r->get('/events', [AdminController::class, 'events']);
    $r->get('/events/create', [AdminController::class, 'eventEditor']);
    $r->get('/events/{id}', [AdminController::class, 'eventEditor']);
    $r->get('/orders', [AdminController::class, 'orders']);
    $r->get('/reports', [AdminController::class, 'reports']);
    $r->get('/scanner', [AdminController::class, 'scanner']);
    $r->get('/settings', [AdminController::class, 'settings']);
}, [adminMiddleware(...), tenantMiddleware(...)]);

// ===== API ROUTES (harus sebelum /{tenantSlug}) =====
$router->group('/api/v1', function (Router $r) {
    $r->post('/tenants', [ApiController::class, 'createTenant']);
    $r->post('/seat-holds', [ApiController::class, 'createSeatHold']);
    $r->post('/seat-holds/{id}/release', [ApiController::class, 'releaseSeatHold']);
    $r->post('/orders', [ApiController::class, 'createOrder']);
    $r->post('/payments/create-intent', [ApiController::class, 'createPaymentIntent']);
    $r->post('/promotions/evaluate', [ApiController::class, 'evaluatePromo']);
    $r->post('/tickets/validate', [ApiController::class, 'validateTicket']);
    $r->get('/orders/{id}', [ApiController::class, 'getOrder']);
    $r->get('/events/{id}/seats', [ApiController::class, 'getEventSeats']);
});

// ===== WEBHOOK (harus sebelum /{tenantSlug}) =====
$router->post('/webhooks/payment', [ApiController::class, 'paymentWebhook']);

// ===== TENANT ROUTES (catch-all, harus TERAKHIR) =====
$router->get('/{tenantSlug}', function (string $tenantSlug) {
    return (new PublicController())->tenantHome($tenantSlug);
});

$router->get('/{tenantSlug}/events/{eventSlug}', function (string $tenantSlug, string $eventSlug) {
    return (new PublicController())->eventDetail($tenantSlug, $eventSlug);
});

$router->get('/{tenantSlug}/events/{eventSlug}/checkout', function (string $tenantSlug, string $eventSlug) {
    return (new PublicController())->checkout($tenantSlug, $eventSlug);
});

$router->get('/{tenantSlug}/events/{eventSlug}/confirmation', function (string $tenantSlug, string $eventSlug) {
    return (new PublicController())->confirmation($tenantSlug, $eventSlug);
});

// Dispatch
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_GET['url'] ?? '/';

// Method spoofing untuk PUT/DELETE via POST
if ($method === 'POST' && !empty($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$router->dispatch($method, $uri);
