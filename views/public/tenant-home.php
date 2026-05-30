<?php ob_start(); ?>
<div class="container-lg py-5">
    <div class="text-center mb-5">
        <div class="brand-mark d-inline-flex align-items-center justify-content-center rounded-3 fs-3 fw-bold mb-3"
             style="width:72px;height:72px">
            <?= View::e(strtoupper(substr($tenant['name'] ?? 'AN', 0, 2))) ?>
        </div>
        <h1 class="h3 fw-bold mb-1"><?= View::e($tenant['name']) ?></h1>
        <p class="text-medium-emphasis mb-0">Daftar event yang sedang berlangsung dan akan datang.</p>
    </div>

    <h2 class="h5 fw-semibold mb-3">Event Mendatang</h2>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($events as $event): ?>
            <a href="<?= base_url("/{$tenant['slug']}/events/{$event['id']}") ?>"
               class="card text-decoration-none text-body hover-shadow">
                <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                    <div class="text-center text-sm-start" style="min-width:6rem">
                        <div class="small text-medium-emphasis text-uppercase fw-semibold">Tanggal</div>
                        <div class="fw-semibold"><?= View::formatDate($event['start_time']) ?></div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="h6 fw-semibold mb-1"><?= View::e($event['title']) ?></div>
                        <div class="small text-medium-emphasis">
                            <i class="cil-location-pin me-1"></i><?= View::e($event['venue_name']) ?>
                        </div>
                    </div>
                    <span class="btn btn-primary">Beli Tiket</span>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (empty($events)): ?>
            <div class="text-center py-5 text-medium-emphasis">
                <i class="cil-calendar fs-1 d-block mb-2"></i>
                Belum ada event mendatang.
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
