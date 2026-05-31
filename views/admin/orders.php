<?php ob_start(); ?>
<?php $f = $filters ?? ['status' => '', 'from' => '', 'to' => '', 'q' => '']; ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h1 class="fs-3 fw-bold mb-1">Pesanan</h1><p class="text-medium-emphasis mb-0">Kelola semua pesanan tiket</p></div>
        <a class="btn btn-outline-secondary" href="<?= base_url('/admin/orders/export?' . http_build_query($f)) ?>"><i class="cil-data-transfer-down me-1"></i>Export CSV</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= base_url('/admin/orders') ?>" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="search" class="form-control" name="q" value="<?= View::e($f['q']) ?>" placeholder="Kode / nama / email">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        <?php foreach (['pending'=>'Pending','paid'=>'Lunas','failed'=>'Gagal','cancelled'=>'Batal','refunded'=>'Refund'] as $val=>$lbl): ?>
                            <option value="<?= $val ?>" <?= $f['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Dari</label>
                    <input type="date" class="form-control" name="from" value="<?= View::e($f['from']) ?>">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Sampai</label>
                    <input type="date" class="form-control" name="to" value="<?= View::e($f['to']) ?>">
                </div>
                <div class="col-6 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="cil-filter me-1"></i>Filter</button>
                    <?php if ($f['q'] !== '' || $f['status'] !== '' || $f['from'] !== '' || $f['to'] !== ''): ?>
                        <a href="<?= base_url('/admin/orders') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Order</th>
                            <th>Customer</th>
                            <th class="text-end">Jumlah</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Provider</th>
                            <th class="d-none d-md-table-cell">Tanggal</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="ps-4"><span class="font-monospace small fw-semibold"><?= View::e($o['order_code']) ?></span></td>
                                <td>
                                    <?php if (!empty($o['customer_email'])): ?>
                                        <a href="<?= base_url('/admin/customers/email:' . urlencode($o['customer_email'])) ?>" class="text-decoration-none">
                                            <div class="fw-medium"><?= View::e($o['customer_name'] ?? $o['customer_email']) ?></div>
                                            <div class="small text-medium-emphasis"><?= View::e($o['customer_email']) ?></div>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-medium-emphasis small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold"><?= View::formatRupiah($o['total_amount_cents']) ?></td>
                                <td>
                                    <?php
                                    $badge = ['paid'=>'success','pending'=>'warning','failed'=>'danger','cancelled'=>'secondary','refunded'=>'info'];
                                    $label = ['paid'=>'Lunas','pending'=>'Pending','failed'=>'Gagal','cancelled'=>'Batal','refunded'=>'Refund'];
                                    ?>
                                    <span class="badge bg-<?= $badge[$o['status']] ?? 'secondary' ?>"><?= $label[$o['status']] ?? $o['status'] ?></span>
                                </td>
                                <td class="d-none d-md-table-cell text-uppercase small text-medium-emphasis"><?= View::e($o['payment_provider']) ?></td>
                                <td class="d-none d-md-table-cell text-medium-emphasis small"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                                <td class="text-end pe-4">
                                    <a href="<?= base_url('/admin/orders/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="cil-search me-1"></i>Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-medium-emphasis"><i class="cil-cart fs-1 mb-2 d-block"></i>Belum ada pesanan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
