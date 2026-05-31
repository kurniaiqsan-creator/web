<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <a href="<?= base_url('/admin/customers') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="cil-arrow-left me-1"></i>Kembali
        </a>
        <div>
            <h1 class="fs-3 fw-bold mb-1"><?= View::e($customer['name'] ?? 'Customer') ?></h1>
            <p class="text-medium-emphasis mb-0"><?= View::e($customer['email']) ?></p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-medium-emphasis text-uppercase small mb-2">Info Kontak</div>
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-medium-emphasis">Nama</dt>
                        <dd class="col-8 mb-1"><?= View::e($customer['name'] ?? '—') ?></dd>
                        <dt class="col-4 text-medium-emphasis">Email</dt>
                        <dd class="col-8 mb-1"><?= View::e($customer['email'] ?? '—') ?></dd>
                        <dt class="col-4 text-medium-emphasis">Telepon</dt>
                        <dd class="col-8 mb-0"><?= View::e($customer['phone'] ?? '—') ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-medium-emphasis text-uppercase small">Total Order</div>
                    <div class="fs-2 fw-semibold"><?= (int)($customer['order_count'] ?? 0) ?></div>
                    <div class="small text-medium-emphasis">Termasuk pending & cancelled</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-medium-emphasis text-uppercase small">Total Belanja</div>
                    <div class="fs-2 fw-semibold"><?= View::formatRupiah((int)($customer['total_spent_cents'] ?? 0)) ?></div>
                    <div class="small text-medium-emphasis">Hanya order yang lunas</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Riwayat Pesanan</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Order</th>
                            <th>Event</th>
                            <th class="text-end">Jumlah</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Provider</th>
                            <th class="d-none d-md-table-cell pe-4">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="ps-4"><span class="font-monospace small fw-semibold"><?= View::e($o['order_code']) ?></span></td>
                                <td><?= View::e($o['event_title'] ?? '—') ?></td>
                                <td class="text-end fw-semibold"><?= View::formatRupiah((int)$o['total_amount_cents']) ?></td>
                                <td>
                                    <?php
                                    $badge = ['paid'=>'success','pending'=>'warning','failed'=>'danger','cancelled'=>'secondary','refunded'=>'info'];
                                    $label = ['paid'=>'Lunas','pending'=>'Pending','failed'=>'Gagal','cancelled'=>'Batal','refunded'=>'Refund'];
                                    ?>
                                    <span class="badge bg-<?= $badge[$o['status']] ?? 'secondary' ?>"><?= $label[$o['status']] ?? $o['status'] ?></span>
                                </td>
                                <td class="d-none d-md-table-cell text-uppercase small text-medium-emphasis"><?= View::e($o['payment_provider']) ?></td>
                                <td class="d-none d-md-table-cell text-medium-emphasis small pe-4"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-medium-emphasis">
                                <i class="cil-cart fs-1 mb-2 d-block"></i>
                                Belum ada pesanan.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
