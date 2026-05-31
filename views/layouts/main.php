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
    <header class="border-bottom bg-body sticky-top">
        <div class="container-lg d-flex align-items-center justify-content-between" style="height:56px">
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

            <div class="d-flex align-items-center gap-2">
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
                    <a href="<?= base_url('/akun') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="cil-user me-1"></i><?= View::e($_SESSION['customer_name'] ?? 'Akun') ?>
                    </a>
                    <a href="<?= base_url('/keluar') ?>" class="btn btn-link text-body-secondary btn-sm">Keluar</a>
                <?php elseif (!empty($tenant)): ?>
                    <a href="<?= base_url('/masuk') ?>" class="btn btn-outline-primary btn-sm">Masuk</a>
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

    <footer class="border-top bg-body py-3 mt-4">
        <div class="container-lg text-center small text-medium-emphasis">
            &copy; <?= date('Y') ?> <?= View::e(Branding::footerText($tenant ?? null)) ?>
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
