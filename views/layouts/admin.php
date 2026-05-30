<!DOCTYPE html>
<html lang="id" data-coreui-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Admin') ?> — Visi Admin</title>

    <!-- CoreUI CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/css/coreui.min.css" rel="stylesheet">
    <!-- CoreUI Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons@3.2.0/css/all.min.css" rel="stylesheet">
    <!-- SimpleBar (scrollbar) -->
    <link href="https://cdn.jsdelivr.net/npm/simplebar@6.3.0/dist/simplebar.min.css" rel="stylesheet">

    <style>
        :root {
            --cui-primary: #f97316;
            --cui-primary-rgb: 249, 115, 22;
        }
        .btn-primary { --cui-btn-bg: #f97316; --cui-btn-border-color: #f97316; --cui-btn-hover-bg: #ea580c; --cui-btn-hover-border-color: #ea580c; --cui-btn-active-bg: #c2410c; }
        .sidebar { --cui-sidebar-bg: #1e1e2d; --cui-sidebar-color: #a2a3b7; --cui-sidebar-nav-link-active-bg: rgba(249,115,22,0.15); --cui-sidebar-nav-link-active-color: #f97316; }
        .sidebar-nav .nav-link:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar-brand { background: rgba(0,0,0,0.2); }
        .wrapper { min-height: 100vh; }
        .table > thead > tr > th { background: #f8f9fa; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; }
        .card { box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-color: #e5e7eb; }
        .seat-available { fill: #10b981; stroke: #059669; cursor: pointer; }
        .seat-available:hover { fill: #34d399; }
        .seat-sold { fill: #ef4444; stroke: #dc2626; cursor: not-allowed; }
        .seat-blocked { fill: #d1d5db; stroke: #9ca3af; cursor: not-allowed; }
        .seat-selected { fill: #f97316; stroke: #ea580c; }

        /* Toast */
        .toast-custom { position: fixed; top: 1rem; right: 1rem; z-index: 9999; }
    </style>
</head>
<body>
<div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">
    <div class="sidebar-brand d-none d-md-flex">
        <img src="https://cdn.jsdelivr.net/npm/@coreui/icons@3.2.0/svg/cil-ticket.svg" width="24" class="sidebar-brand-full me-2" style="filter:invert(1)">
        <span class="sidebar-brand-full">Visi Admin</span>
        <span class="sidebar-brand-narrow">V</span>
    </div>
    <div class="sidebar-nav" data-coreui="navigation" data-simplebar>
        <ul class="nav">
            <?php
            $navs = [
                ['/admin/dashboard', 'cil-speedometer', 'Dashboard'],
                ['/admin/events', 'cil-calendar', 'Event'],
                ['/admin/orders', 'cil-cart', 'Pesanan'],
                ['/admin/reports', 'cil-chart', 'Laporan'],
                ['/admin/scanner', 'cil-qr-code', 'Scanner'],
                ['/admin/settings', 'cil-settings', 'Pengaturan'],
            ];
            $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            foreach ($navs as [$href, $icon, $label]):
            $active = str_starts_with($current, base_url($href));
            ?>
                <li class="nav-item">
                    <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= base_url($href) ?>">
                        <i class="nav-icon <?= $icon ?>"></i>
                        <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="sidebar-footer border-top border-secondary border-opacity-25 mt-auto p-3">
            <a href="<?= base_url('/logout') ?>" class="btn btn-ghost-dark btn-sm w-100 text-start">
            <i class="cil-account-logout me-2"></i>Keluar
        </a>
    </div>
</div>

<div class="wrapper d-flex flex-column min-vh-100">
    <header class="header header-sticky mb-4">
        <div class="container-fluid">
            <button class="header-toggler px-md-0 me-md-3" type="button" onclick="document.body.classList.toggle('sidebar-hidden')">
                <i class="cil-menu icon-lg"></i>
            </button>
            <a href="<?= base_url('/') ?>" class="header-brand d-md-none">
                <i class="cil-ticket me-1"></i> Visi
            </a>
            <ul class="header-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link py-0 d-flex align-items-center" data-coreui-toggle="dropdown" href="#" role="button" aria-expanded="false">
                        <div class="avatar avatar-md me-2 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width:36px;height:36px">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 2)) ?>
                        </div>
                        <span class="d-none d-md-block"><?= View::e($_SESSION['user_name'] ?? 'Admin') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pt-0">
                        <div class="dropdown-header bg-light py-2"><strong><?= View::e($_SESSION['user_name'] ?? 'Admin') ?></strong></div>
                        <a class="dropdown-item" href="<?= base_url('/admin/settings') ?>"><i class="cil-settings me-2"></i>Pengaturan</a>
                        <a class="dropdown-item" href="<?= base_url('/logout') ?>"><i class="cil-account-logout me-2"></i>Keluar</a>
                    </div>
                </li>
            </ul>
        </div>
    </header>

    <div class="body flex-grow-1 px-3">
        <div class="container-lg">
            <?= $content ?? '' ?>
        </div>
    </div>

    <footer class="footer px-3">
        <div class="container-lg">
            <div class="row">
                <div class="col text-center text-sm-start">
                    <span class="text-medium-emphasis small">&copy; <?= date('Y') ?> Visi — Platform Tiket</span>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- CoreUI JS -->
<script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@5.4.1/dist/js/coreui.bundle.min.js"></script>
<!-- SimpleBar -->
<script src="https://cdn.jsdelivr.net/npm/simplebar@6.3.0/dist/simplebar.min.js"></script>
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Toast helper -->
<div id="toastContainer" class="toast-container toast-custom"></div>
<script>
function showToast(msg, type='success') {
    const container = document.getElementById('toastContainer');
    const colors = {success:'bg-success text-white',error:'bg-danger text-white',warning:'bg-warning text-dark'};
    const icons = {success:'cil-check-circle',error:'cil-x-circle',warning:'cil-warning'};
    const toast = document.createElement('div');
    toast.className = 'toast show align-items-center ' + (colors[type]||colors.success) + ' border-0';
    toast.setAttribute('role','alert');
    toast.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="${icons[type]||icons.success} me-2"></i>${msg}</div><button class="btn-close btn-close-white me-2 m-auto" data-coreui-dismiss="toast"></button></div>`;
    container.appendChild(toast);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}
</script>
</body>
</html>
