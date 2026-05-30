<?php ob_start(); ?>
<section class="py-5 py-md-6">
    <div class="container-lg text-center py-4">
        <span class="badge text-bg-primary bg-opacity-10 text-primary mb-3 px-3 py-2 fw-semibold">
            Platform Tiket Event — Phase 1 MVP
        </span>
        <h1 class="display-5 fw-bold mb-3">
            Jual tiket event kamu <span class="text-primary">tanpa ribet</span>
        </h1>
        <p class="lead text-medium-emphasis mx-auto" style="max-width:42rem">
            Seat booking real-time, e-ticket QR, dan integrasi pembayaran lokal — siap dipakai untuk konser, theatre, &amp; gathering.
        </p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-4">
            <a href="<?= base_url('/acoustic-nights') ?>" class="btn btn-primary btn-lg px-4">Lihat Event Live</a>
            <a href="<?= base_url('/login') ?>" class="btn btn-outline-secondary btn-lg px-4">Masuk Admin</a>
        </div>
    </div>
</section>

<section class="bg-body border-top py-5">
    <div class="container-lg">
        <h2 class="h3 text-center fw-bold mb-2">Semua yang kamu butuhkan untuk jual tiket</h2>
        <p class="text-center text-medium-emphasis mb-5">Workflow lengkap dari seat map ke validasi tiket pada hari-H.</p>
        <div class="row g-4">
            <?php foreach ([
                ['Seat Booking',      'Seat map interaktif, real-time hold, dan anti double-booking.', 'cil-grid'],
                ['E-Ticket QR',       'Tiket digital dengan QR unik, validasi on-site secara live.',    'cil-qr-code'],
                ['Pembayaran Lokal',  'Integrasi Midtrans, Xendit, DOKU — VA, e-wallet, kartu.',       'cil-credit-card'],
                ['Dashboard Admin',   'KPI penjualan, laporan event, kelola pesanan & customer.',      'cil-chart'],
            ] as [$featTitle, $featDesc, $featIcon]): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div class="brand-mark d-inline-flex align-items-center justify-content-center rounded-3 mb-3"
                                 style="width:48px;height:48px">
                                <i class="<?= View::e($featIcon) ?> fs-3"></i>
                            </div>
                            <h3 class="h6 fw-semibold mb-2"><?= View::e($featTitle) ?></h3>
                            <p class="small text-medium-emphasis mb-0"><?= View::e($featDesc) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
