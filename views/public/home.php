<?php
// $cards sudah disiapkan controller (PublicController::decorateEvents).
$recommended = array_slice($cards, 0, 4);
$upcoming    = array_slice($cards, 4, 8);
$heroEvent   = $cards[0] ?? null;
$cats        = View::eventCategories();

// Render satu kartu event sebagai pemicu modal (klik = buka modal; href fallback no-JS).
$renderCard = function (array $c): void { ?>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= View::e($c['url']) ?>" @click.prevent="$store.ev.open(<?= $c['id'] ?>)"
           class="card h-100 event-card text-decoration-none text-body position-relative">
            <button type="button" class="event-save-btn" @click.prevent.stop="$store.ev.toggleSave(<?= $c['id'] ?>)"
                    :aria-label="$store.ev.isSaved(<?= $c['id'] ?>) ? 'Hapus dari tersimpan' : 'Simpan'"
                    x-text="$store.ev.isSaved(<?= $c['id'] ?>) ? '❤️' : '🤍'"></button>
            <div class="event-card-cover" style="<?= !empty($c['cover'])
                ? 'background-image:url(\'' . View::e($c['cover']) . '\');background-size:cover;background-position:center'
                : 'background: ' . View::e($c['color']) ?>">
                <?php if (empty($c['cover'])): ?><span class="event-card-emoji"><?= $c['emoji'] ?></span><?php endif; ?>
                <span class="badge event-card-badge"><?= View::e($c['label'] ?? $c['catLabel']) ?></span>
                <span class="badge visi-dist-badge"
                      x-show="$store.ev.distanceLabel($store.ev.find(<?= $c['id'] ?>))"
                      x-text="'📍 ' + $store.ev.distanceLabel($store.ev.find(<?= $c['id'] ?>))"></span>
            </div>
            <div class="card-body">
                <div class="h6 fw-semibold mb-1"><?= View::e($c['title']) ?></div>
                <?php if ($c['date'] !== ''): ?>
                    <div class="small text-medium-emphasis mb-1"><i class="cil-calendar me-1"></i><?= View::e($c['date']) ?></div>
                <?php endif; ?>
                <div class="small text-medium-emphasis"><i class="cil-location-pin me-1"></i><?= View::e($c['venue']) ?></div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                <span class="btn btn-primary btn-sm w-100">Beli Tiket</span>
            </div>
        </a>
    </div>
<?php };
?>
<?php ob_start(); ?>
<div class="container-lg py-4 py-md-5" x-data>

    <!-- Hero -->
    <section class="visi-hero text-white p-4 p-md-5 mb-5 position-relative overflow-hidden">
        <div class="visi-hero-decor">🎉</div>
        <div class="position-relative" style="max-width:40rem">
            <span class="badge bg-white bg-opacity-25 mb-3 px-3 py-2">📍 Event seru di sekitarmu</span>
            <h1 class="display-5 fw-bold mb-3">Temukan Event Terbaik di Kotamu</h1>
            <p class="fs-5 opacity-75 mb-4">Konser, workshop, festival, dan komunitas — semua dalam satu tempat. Jangan sampai ketinggalan momen seru!</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('/events') ?>" class="btn btn-light btn-lg fw-semibold px-4">Jelajahi Event</a>
                <?php if ($heroEvent): ?>
                    <a href="<?= View::e($heroEvent['url']) ?>" @click.prevent="$store.ev.open(<?= $heroEvent['id'] ?>)"
                       class="btn btn-outline-light btn-lg fw-semibold px-4">Event Unggulan</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Kategori -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Jelajahi Kategori</h2>
            <a href="<?= base_url('/events') ?>" class="text-decoration-none fw-semibold small">Lihat semua →</a>
        </div>
        <div class="row g-3">
            <?php foreach ($cats as $key => $meta): if ($key === 'lainnya') continue; ?>
                <div class="col-4 col-md">
                    <a href="<?= base_url('/events?cat=' . urlencode($key)) ?>"
                       class="card h-100 text-center text-decoration-none text-body event-cat-tile">
                        <div class="card-body">
                            <div class="event-cat-icon mx-auto mb-2" style="background: <?= View::e($meta['color']) ?>1F">
                                <span><?= $meta['emoji'] ?></span>
                            </div>
                            <div class="fw-semibold small"><?= View::e($meta['label']) ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($recommended)): ?>
        <!-- Rekomendasi -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold mb-0">Rekomendasi Untukmu ✨</h2>
                <a href="<?= base_url('/events') ?>" class="text-decoration-none fw-semibold small">Lihat semua →</a>
            </div>
            <div class="row g-3">
                <?php foreach ($recommended as $c) $renderCard($c); ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($upcoming)): ?>
        <!-- Event Mendatang -->
        <section class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold mb-0">Event Mendatang 📅</h2>
                <a href="<?= base_url('/events') ?>" class="text-decoration-none fw-semibold small">Lihat semua →</a>
            </div>
            <div class="row g-3">
                <?php foreach ($upcoming as $c) $renderCard($c); ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (empty($cards)): ?>
        <section class="text-center py-5 text-medium-emphasis">
            <i class="cil-calendar fs-1 d-block mb-2"></i>
            <div class="fw-semibold">Belum ada event yang dipublikasikan.</div>
            <a href="<?= base_url('/events') ?>" class="btn btn-outline-primary btn-sm mt-3">Ke halaman event</a>
        </section>
    <?php endif; ?>

</div>

<script>window.__PUBLIC_EVENTS__ = <?= json_encode($cards, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php require VIEW_PATH . '/public/_event_modal.php'; ?>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
