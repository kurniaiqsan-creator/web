<!DOCTYPE html>
<html lang="id" data-coreui-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Visi') ?> — <?= View::e(Branding::siteTitle($tenant ?? null)) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/css/coreui.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.1.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('/assets/css/visi.css') ?>">
    <?= Branding::styleTag($tenant ?? null) ?>

    <script src="<?= base_url('/assets/js/color-modes.js') ?>"></script>
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
    <?php
        $isAdminUser = !empty($_SESSION['user_id'])
            && in_array($_SESSION['role'] ?? '', ['tenant_admin', 'staff'], true);
        $notif = Notifications::forViewer($tenant ?? null);
        $navPath = '/' . trim((string)($_GET['url'] ?? ''), '/');
        $isHome   = ($navPath === '/' || $navPath === '');
        $isEvents = str_starts_with($navPath, '/events');
    ?>
    <header class="border-bottom bg-body sticky-top">
        <div class="container-lg d-flex align-items-center gap-3" style="min-height:68px;padding-top:.5rem;padding-bottom:.5rem">
            <a href="<?= base_url('/') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-body">
                <?php $mainLogo = Branding::logoUrl($tenant ?? null); ?>
                <?php if ($mainLogo !== ''): ?>
                    <img src="<?= base_url(View::e($mainLogo)) ?>" alt="Logo" style="max-height:32px;max-width:150px;object-fit:contain">
                <?php else: ?>
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold"
                          style="width:32px;height:32px">V</span>
                    <span class="fs-5 fw-semibold"><?= View::e($tenant['name'] ?? 'Visi') ?></span>
                <?php endif; ?>
            </a>

            <?php if (!empty($tenant)): ?>
                <nav class="d-none d-lg-flex align-items-center gap-1 ms-2">
                    <a href="<?= base_url('/') ?>" class="visi-nav-pill<?= $isHome ? ' active' : '' ?>">Beranda</a>
                    <a href="<?= base_url('/events') ?>" class="visi-nav-pill<?= $isEvents ? ' active' : '' ?>">Event</a>
                    <a href="<?= base_url('/events?saved=1') ?>" class="visi-nav-pill">Tersimpan</a>
                </nav>

                <form class="d-none d-md-flex flex-grow-1 mx-2" action="<?= base_url('/events') ?>" method="get" style="max-width:380px">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body"><i class="cil-search"></i></span>
                        <input type="search" name="q" class="form-control" placeholder="Cari event, lokasi, kategori..." aria-label="Cari event">
                    </div>
                </form>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2 ms-auto">
                <?php if ($isAdminUser): ?>
                    <a href="<?= base_url('/admin/events/create') ?>" class="btn btn-primary btn-sm fw-semibold">
                        <i class="cil-plus me-1"></i><span class="d-none d-sm-inline">Buat Event</span>
                    </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-link nav-link p-2 d-flex align-items-center text-body position-relative" type="button"
                            data-coreui-toggle="dropdown" data-coreui-auto-close="outside" aria-expanded="false" aria-label="Notifikasi">
                        <i class="cil-bell icon-lg"></i>
                        <?php if (($notif['unread'] ?? 0) > 0): ?>
                            <span class="position-absolute translate-middle badge rounded-pill text-bg-danger"
                                  style="top:6px;left:calc(100% - 8px);font-size:.6rem">
                                <?= (int)$notif['unread'] > 9 ? '9+' : (int)$notif['unread'] ?>
                                <span class="visually-hidden">notifikasi belum dibaca</span>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow visi-notif-menu p-0">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                            <span class="fw-semibold">Notifikasi</span>
                            <?php if (($notif['mode'] ?? '') === 'customer'): ?>
                                <a href="<?= base_url('/akun') ?>" class="small text-decoration-none">Lihat semua</a>
                            <?php endif; ?>
                        </div>
                        <div class="visi-notif-list">
                            <?php if (!empty($notif['items'])): ?>
                                <?php foreach ($notif['items'] as $n): ?>
                                    <a href="<?= View::e($n['url']) ?>"
                                       class="d-flex gap-2 px-3 py-2 text-decoration-none text-body border-bottom visi-notif-item<?= !empty($n['unread']) ? ' is-unread' : '' ?>">
                                        <i class="<?= View::e($n['icon']) ?> mt-1 flex-shrink-0"></i>
                                        <span class="flex-grow-1">
                                            <span class="d-block fw-semibold small"><?= View::e($n['title']) ?></span>
                                            <span class="d-block small text-medium-emphasis"><?= View::e($n['text']) ?></span>
                                            <?php if (!empty($n['time'])): ?>
                                                <span class="d-block text-medium-emphasis" style="font-size:.7rem"><?= View::e($n['time']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-medium-emphasis px-3 py-4 small">
                                    <i class="cil-bell d-block fs-4 mb-2 opacity-50"></i>
                                    <?php if (($notif['mode'] ?? '') === 'guest'): ?>
                                        <a href="<?= base_url('/login') ?>" class="text-decoration-none">Masuk</a> untuk melihat update pesananmu.
                                    <?php else: ?>
                                        Belum ada notifikasi.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-link nav-link p-2 d-flex align-items-center text-body" type="button"
                            data-coreui-toggle="dropdown" aria-expanded="false" aria-label="Tema warna">
                        <i class="theme-icon-active cil-contrast icon-lg"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width:8rem">
                        <li><button type="button" class="dropdown-item d-flex align-items-center"
                            data-coreui-theme-value="light" data-icon-class="cil-sun icon-lg"><i class="cil-sun icon me-2"></i>Terang</button></li>
                        <li><button type="button" class="dropdown-item d-flex align-items-center"
                            data-coreui-theme-value="dark" data-icon-class="cil-moon icon-lg"><i class="cil-moon icon me-2"></i>Gelap</button></li>
                        <li><button type="button" class="dropdown-item d-flex align-items-center active"
                            data-coreui-theme-value="auto" data-icon-class="cil-contrast icon-lg"><i class="cil-contrast icon me-2"></i>Auto</button></li>
                    </ul>
                </div>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="<?= base_url('/admin/dashboard') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
                    <a href="<?= base_url('/logout') ?>" class="btn btn-link text-body-secondary btn-sm">Keluar</a>
                <?php elseif (!empty($tenant) && !empty($_SESSION['customer_id']) && (int)($_SESSION['customer_tenant_id'] ?? 0) === (int)$tenant['id']): ?>
                    <div class="dropdown">
                        <a class="d-flex align-items-center text-decoration-none" data-coreui-toggle="dropdown" href="#" role="button" aria-expanded="false" aria-label="Akun">
                            <?= View::avatarHtml($_SESSION['customer_avatar'] ?? null, strtoupper(substr($_SESSION['customer_name'] ?? 'U', 0, 2)), 36) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end pt-0">
                            <li><div class="dropdown-header bg-body-tertiary fw-semibold rounded-top mb-2"><?= View::e($_SESSION['customer_name'] ?? 'Akun') ?></div></li>
                            <li><a class="dropdown-item" href="<?= base_url('/akun') ?>"><i class="cil-user me-2"></i>Akun Saya</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('/akun/pengaturan') ?>"><i class="cil-settings me-2"></i>Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('/keluar') ?>"><i class="cil-account-logout me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                <?php elseif (!empty($tenant)): ?>
                    <a href="<?= base_url('/login') ?>" class="btn btn-outline-primary btn-sm">Masuk</a>
                    <a href="<?= base_url('/daftar') ?>" class="btn btn-primary btn-sm">Daftar</a>
                <?php else: ?>
                    <a href="<?= base_url('/login') ?>" class="btn btn-primary btn-sm">Masuk</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="flex-grow-1">
        <?= $content ?? '' ?>
    </main>

    <footer class="border-top bg-body-tertiary mt-5 pt-5 pb-4">
        <div class="container-lg">
            <div class="row g-4">
                <!-- Brand + tagline -->
                <div class="col-lg-4">
                    <a href="<?= base_url('/') ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none text-body mb-3">
                        <?php $footLogo = Branding::logoUrl($tenant ?? null); ?>
                        <?php if ($footLogo !== ''): ?>
                            <img src="<?= base_url(View::e($footLogo)) ?>" alt="Logo" style="max-height:32px;max-width:160px;object-fit:contain">
                        <?php else: ?>
                            <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold" style="width:32px;height:32px">V</span>
                            <span class="fs-5 fw-bold"><?= View::e($tenant['name'] ?? 'Visi') ?></span>
                        <?php endif; ?>
                    </a>
                    <p class="text-medium-emphasis small mb-0" style="max-width:22rem">
                        <?= View::e(Branding::footerTagline($tenant ?? null)) ?>
                    </p>
                </div>

                <!-- Jelajahi (kategori asli → filter /events) -->
                <div class="col-6 col-lg-2">
                    <h3 class="h6 fw-bold mb-3">Jelajahi</h3>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <?php foreach (View::eventCategories() as $fk => $fmeta): if ($fk === 'lainnya') continue; ?>
                            <li><a class="visi-footer-link" href="<?= base_url('/events?cat=' . urlencode($fk)) ?>"><?= View::e($fmeta['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Perusahaan -->
                <div class="col-6 col-lg-3">
                    <h3 class="h6 fw-bold mb-3">Perusahaan</h3>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a class="visi-footer-link" href="<?= base_url('/') ?>">Tentang Kami</a></li>
                        <li><a class="visi-footer-link" href="<?= base_url('/events') ?>">Semua Event</a></li>
                        <?php if (!empty($tenant['email'] ?? null)): ?>
                            <li><a class="visi-footer-link" href="mailto:<?= View::e($tenant['email']) ?>">Kontak</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Bantuan -->
                <div class="col-6 col-lg-3">
                    <h3 class="h6 fw-bold mb-3">Bantuan</h3>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a class="visi-footer-link" href="<?= base_url('/akun') ?>">Akun Saya</a></li>
                        <li><a class="visi-footer-link" href="<?= base_url('/login') ?>">Masuk / Daftar</a></li>
                    </ul>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-medium-emphasis">
                <span>&copy; <?= date('Y') ?> <?= View::e(Branding::footerText($tenant ?? null)) ?></span>
                <span class="d-flex gap-3">
                    <i class="cil-shield-alt"></i>Pembayaran aman &amp; e-ticket QR
                </span>
            </div>
        </div>
    </footer>

    <div id="visiToastContainer" class="visi-toast-container"></div>

    <?php $flash = Session::consumeFlash(); ?>
    <?php if ($flash): ?>
        <script>document.addEventListener('DOMContentLoaded', function(){ window.showToast && showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type']) ?>); });</script>
    <?php endif; ?>

    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/js/coreui.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= base_url('/assets/js/visi.js') ?>"></script>
</body>
</html>
