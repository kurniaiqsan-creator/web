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

// Muat .env (jika ada) sebelum apa pun yang membaca getenv() (BASE_URL & config).
Env::load(BASE_PATH . '/.env');

// BASE_URL bisa sudah didefinisikan oleh root index.php (akses via subdirectory).
// Kalau belum, ambil dari APP_BASE_URL (yang kini bisa berasal dari .env).
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/'));
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        if ($path === '') {
            return BASE_URL === '' ? '/' : BASE_URL;
        }
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

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

// Onboarding publik dinonaktifkan untuk Phase 1 (single-tenant MVP).
// Endpoint API tetap tersedia di /api/v1/tenants untuk admin/seeding.
$router->get('/onboarding', function () {
    Router::redirect('/login');
});

// ===== E-TICKET (harus sebelum /{tenantSlug}) =====
$router->get('/t/{token}', function (string $token) {
    return (new PublicController())->eticket($token);
});

// ===== ADMIN ROUTES (harus sebelum /{tenantSlug}) =====
$router->group('/admin', function (Router $r) {
    $r->get('/dashboard', [AdminController::class, 'dashboard']);

    // Events
    $r->get('/events', [AdminController::class, 'events']);
    $r->get('/events/create', [AdminController::class, 'eventEditor']);
    $r->post('/events', [AdminController::class, 'eventSave']);
    $r->get('/events/{id}', [AdminController::class, 'eventEditor']);
    $r->post('/events/{id}', [AdminController::class, 'eventSave']);
    $r->post('/events/{id}/delete', [AdminController::class, 'eventDelete']);

    // Venues
    $r->get('/venues', [AdminController::class, 'venues']);
    $r->get('/venues/create', [AdminController::class, 'venueEditor']);
    $r->post('/venues', [AdminController::class, 'venueSave']);
    $r->get('/venues/{id}', [AdminController::class, 'venueEditor']);
    $r->post('/venues/{id}', [AdminController::class, 'venueSave']);
    $r->post('/venues/{id}/delete', [AdminController::class, 'venueDelete']);

    // Orders
    $r->get('/orders', [AdminController::class, 'orders']);
    $r->get('/orders/export', [AdminController::class, 'ordersExport']);
    $r->get('/orders/{id}', [AdminController::class, 'orderDetail']);
    $r->post('/orders/{id}/refund', [AdminController::class, 'orderRefund']);

    // Ticket categories
    $r->get('/ticket-categories', [AdminController::class, 'ticketCategories']);
    $r->post('/ticket-categories', [AdminController::class, 'ticketCategorySave']);
    $r->post('/ticket-categories/{id}', [AdminController::class, 'ticketCategorySave']);
    $r->post('/ticket-categories/{id}/delete', [AdminController::class, 'ticketCategoryDelete']);

    // Promotions
    $r->get('/promotions', [AdminController::class, 'promotions']);
    $r->get('/promotions/create', [AdminController::class, 'promotionEditor']);
    $r->post('/promotions', [AdminController::class, 'promotionSave']);
    $r->get('/promotions/{id}', [AdminController::class, 'promotionEditor']);
    $r->post('/promotions/{id}', [AdminController::class, 'promotionSave']);
    $r->post('/promotions/{id}/delete', [AdminController::class, 'promotionDelete']);

    // Lain
    $r->get('/customers', [AdminController::class, 'customers']);
    $r->get('/customers/{id}', [AdminController::class, 'customerDetail']);
    $r->get('/reports', [AdminController::class, 'reports']);
    $r->get('/scanner', [AdminController::class, 'scanner']);
    $r->get('/settings', [AdminController::class, 'settings']);
    $r->post('/settings', [AdminController::class, 'settingsSave']);
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
