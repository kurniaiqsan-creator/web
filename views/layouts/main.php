<!DOCTYPE html>
<html lang="id" data-coreui-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Visi') ?> — Visi</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/css/coreui.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.1.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('/assets/css/visi.css') ?>">

    <script src="<?= base_url('/assets/js/color-modes.js') ?>"></script>
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
    <header class="border-bottom bg-body sticky-top">
        <div class="container-lg d-flex align-items-center justify-content-between" style="height:56px">
            <a href="<?= base_url('/') ?>" class="d-flex align-items-center gap-2 text-decoration-none text-body">
                <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold"
                      style="width:32px;height:32px">V</span>
                <span class="fs-5 fw-semibold">Visi</span>
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
                <?php else: ?>
                    <a href="<?= base_url('/login') ?>" class="btn btn-primary btn-sm">Masuk Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="flex-grow-1">
        <?= $content ?? '' ?>
    </main>

    <footer class="border-top bg-body py-3 mt-4">
        <div class="container-lg text-center small text-medium-emphasis">
            &copy; <?= date('Y') ?> Visi — Platform Tiket
        </div>
    </footer>

    <div id="visiToastContainer" class="visi-toast-container"></div>

    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/js/coreui.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= base_url('/assets/js/visi.js') ?>"></script>
</body>
</html>
