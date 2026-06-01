<?php
ob_start();

$statusMap = [
    'paid'      => ['Lunas', 'text-bg-success'],
    'pending'   => ['Menunggu', 'text-bg-warning'],
    'failed'    => ['Gagal', 'text-bg-danger'],
    'cancelled' => ['Dibatalkan', 'text-bg-secondary'],
    'refunded'  => ['Refund', 'text-bg-info'],
];

// Inisial avatar + handle dari email.
$nameParts = preg_split('/\s+/', trim((string)$customer['name'])) ?: [];
$initials  = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
$handle    = '@' . preg_replace('/[^a-z0-9_.]/', '', strtolower(explode('@', (string)$customer['email'])[0]));
?>
<div class="container-lg py-4" x-data>

    <!-- Header profil -->
    <div class="card mb-4">
        <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center gap-3">
            <div class="flex-shrink-0"><?= View::avatarHtml($customer['avatar_url'] ?? null, $initials, 64) ?></div>
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold mb-1"><?= View::e($customer['name']) ?></h1>
                <div class="text-medium-emphasis">
                    <?= View::e($handle) ?> · <?= View::e($customer['email']) ?>
                    <?php if (!empty($customer['phone'])): ?> · <?= View::e($customer['phone']) ?><?php endif; ?>
                </div>
            </div>
            <a href="<?= base_url('/akun/pengaturan') ?>" class="btn btn-outline-secondary">
                <i class="cil-settings me-1"></i>Edit Profil
            </a>
        </div>
    </div>

    <!-- Statistik (data nyata) -->
    <div class="row g-3 mb-4">
        <?php
        $statCards = [
            ['🎫', (string)$stats['orders'], 'Pesanan'],
            ['🎟️', (string)$stats['tickets'], 'Tiket'],
            ['✅', (string)$stats['paid'], 'Lunas'],
        ];
        foreach ($statCards as [$ic, $num, $lbl]): ?>
            <div class="col-6 col-lg-3">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="fs-3"><?= $ic ?></div>
                        <div class="h3 fw-bold text-primary mb-0"><?= View::e($num) ?></div>
                        <div class="small text-medium-emphasis"><?= View::e($lbl) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="fs-3">❤️</div>
                    <div class="h3 fw-bold text-primary mb-0" x-text="$store.ev.all.filter(e => $store.ev.isSaved(e.id)).length"></div>
                    <div class="small text-medium-emphasis">Tersimpan</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event yang Kamu Simpan (wishlist) -->
    <h2 class="h4 fw-bold mb-3">Event yang Kamu Simpan</h2>
    <div class="row g-3 mb-2" x-show="$store.ev.all.filter(e => $store.ev.isSaved(e.id)).length > 0">
        <template x-for="ev in $store.ev.all.filter(e => $store.ev.isSaved(e.id))" :key="ev.id">
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 event-card position-relative" role="button" @click="$store.ev.open(ev.id)" style="cursor:pointer">
                    <button type="button" class="event-save-btn" @click.stop="$store.ev.toggleSave(ev.id)" aria-label="Hapus dari tersimpan">
                        <span x-text="$store.ev.isSaved(ev.id) ? '❤️' : '🤍'"></span>
                    </button>
                    <div class="event-card-cover" :style="ev.cover ? `background-image:url('${ev.cover}');background-size:cover;background-position:center` : `background:${ev.color}`">
                        <span class="event-card-emoji" x-show="!ev.cover" x-text="ev.emoji"></span>
                        <span class="badge event-card-badge" x-text="ev.catLabel"></span>
                        <span class="badge visi-dist-badge" x-show="$store.ev.distanceLabel(ev)" x-text="'📍 ' + $store.ev.distanceLabel(ev)"></span>
                    </div>
                    <div class="card-body">
                        <h3 class="h6 fw-semibold mb-1" x-text="ev.title"></h3>
                        <div class="small text-medium-emphasis mb-1" x-show="ev.date"><i class="cil-calendar me-1"></i><span x-text="ev.date"></span></div>
                        <div class="small text-medium-emphasis"><i class="cil-location-pin me-1"></i><span x-text="ev.venue"></span></div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0 pb-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-primary" x-text="ev.priceShort || 'Lihat detail'"></span>
                        <span class="small fw-semibold text-primary">Detail →</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
    <div class="card mb-2" x-show="$store.ev.all.filter(e => $store.ev.isSaved(e.id)).length === 0">
        <div class="card-body text-center text-medium-emphasis p-4">
            <div class="fs-1 mb-2">🤍</div>
            <div class="fw-semibold">Belum ada event tersimpan</div>
            <div class="small mb-3">Simpan event favoritmu agar tidak terlewat!</div>
            <a href="<?= base_url('/events') ?>" class="btn btn-outline-primary btn-sm">Jelajahi Event</a>
        </div>
    </div>

    <!-- Riwayat Pesanan -->
    <h2 class="h4 fw-bold mt-5 mb-3">Riwayat Pesanan (<?= count($orders) ?>)</h2>
    <?php if (empty($orders)): ?>
        <div class="card">
            <div class="card-body text-center p-5">
                <i class="cil-bag fs-1 text-medium-emphasis mb-2 d-block"></i>
                <p class="text-medium-emphasis mb-3">Belum ada pesanan. Yuk cari event menarik!</p>
                <a href="<?= base_url('/events') ?>" class="btn btn-primary">Jelajahi Event</a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($orders as $o): ?>
                <?php
                    [$label, $badge] = $statusMap[$o['status']] ?? [ucfirst($o['status']), 'text-bg-secondary'];
                    $tickets = $ticketsByOrder[(int)$o['id']] ?? [];
                ?>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h3 class="h6 fw-semibold mb-1"><?= View::e($o['event_title'] ?? 'Event') ?></h3>
                                <div class="small text-medium-emphasis">
                                    <?php if (!empty($o['start_time'])): ?>
                                        <i class="cil-calendar me-1"></i><?= View::formatDate($o['start_time']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($o['venue_name'])): ?>
                                        · <i class="cil-location-pin me-1"></i><?= View::e($o['venue_name']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?= $badge ?>"><?= View::e($label) ?></span>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                            <span class="text-medium-emphasis">
                                Order <span class="font-monospace"><?= View::e($o['order_code']) ?></span>
                                · <?= date('d M Y', strtotime($o['created_at'])) ?>
                            </span>
                            <span class="fw-semibold"><?= View::formatRupiah((int)$o['total_amount_cents']) ?></span>
                        </div>

                        <?php if ($o['status'] === 'paid' && $tickets !== []): ?>
                            <div class="border-top mt-3 pt-3 d-flex flex-wrap gap-2">
                                <?php foreach ($tickets as $t): ?>
                                    <a href="<?= base_url('/t/' . $t['ticket_token']) ?>" target="_blank"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="cil-ticket me-1"></i>E-Ticket<?= !empty($t['seat_label']) ? ' ' . View::e($t['seat_label']) : '' ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($o['status'] === 'pending'): ?>
                            <div class="border-top mt-3 pt-3">
                                <span class="small text-medium-emphasis">
                                    <i class="cil-clock me-1"></i>Menunggu pembayaran. Tiket terbit otomatis setelah pembayaran dikonfirmasi.
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>window.__PUBLIC_EVENTS__ = <?= json_encode($cards, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php require VIEW_PATH . '/public/_event_modal.php'; ?>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
