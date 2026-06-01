<!DOCTYPE html>
<html lang="id" data-coreui-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Admin') ?> — <?= View::e(Branding::siteTitle()) ?> Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/css/coreui.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.1.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.3.0/dist/simplebar.min.css">
    <link rel="stylesheet" href="<?= base_url('/assets/css/visi.css') ?>">
    <?= Branding::styleTag() ?>

    <script src="<?= base_url('/assets/js/color-modes.js') ?>"></script>
</head>
<body>
<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$navGroups = [
    [
        'title' => 'Operasional',
        'items' => [
            ['/admin/dashboard',  'cil-speedometer', 'Dashboard'],
            ['/admin/events',     'cil-calendar',    'Event'],
            ['/admin/venues',     'cil-room',        'Venue'],
            ['/admin/orders',     'cil-cart',        'Pesanan'],
            ['/admin/customers',  'cil-people',      'Customer'],
            ['/admin/scanner',    'cil-qr-code',     'Scanner Tiket'],
        ],
    ],
    [
        'title' => 'Insight',
        'items' => [
            ['/admin/reports',    'cil-chart',       'Laporan'],
            ['/admin/logs',       'cil-list',        'Log & Audit'],
        ],
    ],
    [
        'title' => 'Setup',
        'items' => [
            ['/admin/ticket-categories', 'cil-tag',  'Tiket & Harga'],
            ['/admin/promotions', 'cil-gift',        'Promosi'],
            ['/admin/settings',   'cil-settings',    'Pengaturan'],
        ],
    ],
];
$isActive = function (string $href) use ($currentPath): bool {
    $target = base_url($href);
    return $currentPath === $target || str_starts_with($currentPath, $target . '/');
};
?>

<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <?php $adminLogo = Branding::logoUrl(); $adminTenantName = Branding::tenantName(); ?>
        <a class="sidebar-brand d-flex align-items-center gap-2 px-3" href="<?= base_url('/admin/dashboard') ?>">
            <?php if ($adminLogo !== ''): ?>
                <img src="<?= base_url(View::e($adminLogo)) ?>" alt="Logo" class="sidebar-brand-full" style="max-height:32px;max-width:150px;object-fit:contain">
                <span class="sidebar-brand-narrow"><img src="<?= base_url(View::e($adminLogo)) ?>" alt="Logo" style="max-height:32px;max-width:32px;object-fit:contain"></span>
            <?php else: ?>
                <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle"
                      style="width:32px;height:32px;font-weight:700"><?= View::e(strtoupper(substr($adminTenantName !== '' ? $adminTenantName : 'V', 0, 1))) ?></span>
                <span class="sidebar-brand-full fs-5 fw-semibold"><?= View::e($adminTenantName !== '' ? $adminTenantName : 'Visi Admin') ?></span>
                <span class="sidebar-brand-narrow fs-5 fw-bold"><?= View::e(strtoupper(substr($adminTenantName !== '' ? $adminTenantName : 'V', 0, 1))) ?></span>
            <?php endif; ?>
        </a>
        <button class="btn-close d-lg-none ms-auto" type="button" aria-label="Tutup"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
                data-coreui-theme="dark"></button>
    </div>

    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
        <?php foreach ($navGroups as $group): ?>
            <li class="nav-title"><?= View::e($group['title']) ?></li>
            <?php foreach ($group['items'] as [$href, $icon, $label]): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive($href) ? 'active' : '' ?>" href="<?= base_url($href) ?>">
                        <i class="nav-icon <?= View::e($icon) ?>"></i>
                        <?= View::e($label) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable" aria-label="Lipat sidebar"></button>
    </div>
</div>

<div class="wrapper d-flex flex-column min-vh-100 bg-body-tertiary">
    <header class="header header-sticky p-0 mb-4">
        <div class="container-fluid border-bottom px-4">
            <button class="header-toggler" type="button"
                    onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
                    style="margin-inline-start:-14px" aria-label="Toggle sidebar">
                <i class="cil-menu icon-lg"></i>
            </button>

            <a href="<?= base_url('/') ?>" class="header-brand d-md-none">
                <span class="fw-semibold">Visi</span>
            </a>

            <ul class="header-nav d-none d-md-flex ms-3">
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('/') ?>" target="_blank" rel="noopener">
                        <i class="cil-globe-alt me-1"></i>Lihat situs
                    </a>
                </li>
            </ul>

            <ul class="header-nav ms-auto">
                <li class="nav-item py-1">
                    <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                </li>
                <li class="nav-item dropdown">
                    <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" type="button"
                            data-coreui-toggle="dropdown" aria-expanded="false" aria-label="Tema warna">
                        <i class="theme-icon-active cil-contrast icon-lg"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width:8rem">
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-coreui-theme-value="light" data-icon-class="cil-sun icon-lg">
                                <i class="cil-sun icon me-2"></i>Terang
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-coreui-theme-value="dark" data-icon-class="cil-moon icon-lg">
                                <i class="cil-moon icon me-2"></i>Gelap
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center active"
                                    data-coreui-theme-value="auto" data-icon-class="cil-contrast icon-lg">
                                <i class="cil-contrast icon me-2"></i>Auto
                            </button>
                        </li>
                    </ul>
                </li>
                <li class="nav-item py-1">
                    <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link py-0 d-flex align-items-center gap-2" data-coreui-toggle="dropdown" href="#" role="button" aria-expanded="false">
                        <?= View::avatarHtml($_SESSION['user_avatar'] ?? null, strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 2)), 36) ?>
                        <span class="d-none d-md-block fw-medium"><?= View::e($_SESSION['user_name'] ?? 'Admin') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pt-0">
                        <div class="dropdown-header bg-body-tertiary fw-semibold rounded-top mb-2">
                            <?= View::e($_SESSION['user_name'] ?? 'Admin') ?>
                        </div>
                        <a class="dropdown-item" href="<?= base_url('/admin/profile') ?>">
                            <i class="cil-user me-2"></i>Profil Saya
                        </a>
                        <a class="dropdown-item" href="<?= base_url('/admin/settings') ?>">
                            <i class="cil-settings me-2"></i>Pengaturan
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= base_url('/logout') ?>">
                            <i class="cil-account-logout me-2"></i>Keluar
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </header>

    <div class="body flex-grow-1">
        <div class="container-lg px-4">
            <?php $flash = Session::consumeFlash(); ?>
            <?php if ($flash): ?>
                <script>document.addEventListener('DOMContentLoaded', function(){ window.showToast && showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type']) ?>); });</script>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </div>
    </div>

    <footer class="footer px-4 border-top mt-4">
        <div class="container-lg py-3 d-flex flex-wrap justify-content-between align-items-center small text-medium-emphasis">
            <span>&copy; <?= date('Y') ?> <?= View::e(Branding::footerText()) ?></span>
            <span>Built with <a href="https://coreui.io/" class="link-secondary text-decoration-none">CoreUI</a></span>
        </div>
    </footer>
</div>

<div id="visiToastContainer" class="visi-toast-container"></div>

<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/js/coreui.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simplebar@6.3.0/dist/simplebar.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="<?= base_url('/assets/js/visi.js') ?>"></script>
</body>
</html>
