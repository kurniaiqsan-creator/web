<?php
ob_start();

$statusMap = [
    'paid'      => ['Lunas', 'text-bg-success'],
    'pending'   => ['Menunggu', 'text-bg-warning'],
    'failed'    => ['Gagal', 'text-bg-danger'],
    'cancelled' => ['Dibatalkan', 'text-bg-secondary'],
    'refunded'  => ['Refund', 'text-bg-info'],
];
?>
<div class="container-lg py-4" style="max-width:48rem">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Akun Saya</h1>
            <p class="small text-medium-emphasis mb-0">
                <?= View::e($customer['name']) ?> · <?= View::e($customer['email']) ?>
                <?php if (!empty($customer['phone'])): ?> · <?= View::e($customer['phone']) ?><?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('/' . $tenant['slug']) ?>" class="btn btn-outline-primary btn-sm">
                <i class="cil-loop me-1"></i>Lihat Event
            </a>
            <a href="<?= base_url('/' . $tenant['slug'] . '/keluar') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="cil-account-logout me-1"></i>Keluar
            </a>
        </div>
    </div>

    <!-- Edit Profil -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-semibold mb-0"><i class="cil-user me-2"></i>Profil Saya</h2>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-coreui-toggle="collapse" data-coreui-target="#profileEdit" aria-expanded="false">
                <i class="cil-pencil me-1"></i>Edit
            </button>
        </div>
        <div class="collapse" id="profileEdit">
            <div class="card-body">
                <form method="post" action="<?= base_url('/' . $tenant['slug'] . '/akun/profil') ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" class="form-control" value="<?= View::e($customer['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= View::e($customer['email']) ?>" disabled>
                            <div class="form-text">Email tidak bisa diubah.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="tel" name="phone" class="form-control" value="<?= View::e($customer['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <hr class="my-4">
                    <p class="small text-medium-emphasis mb-3">Ganti password (kosongkan jika tidak ingin mengubah).</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Konfirmasi</label>
                            <input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="cil-save me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <h2 class="h6 fw-semibold mb-3">Riwayat Pesanan (<?= count($orders) ?>)</h2>

    <?php if (empty($orders)): ?>
        <div class="card">
            <div class="card-body text-center p-5">
                <i class="cil-bag fs-1 text-medium-emphasis mb-2 d-block"></i>
                <p class="text-medium-emphasis mb-3">Belum ada pesanan. Yuk cari event menarik!</p>
                <a href="<?= base_url('/' . $tenant['slug']) ?>" class="btn btn-primary">Jelajahi Event</a>
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
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
