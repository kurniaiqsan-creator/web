<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h1 class="fs-3 fw-bold mb-1">Pesanan</h1><p class="text-medium-emphasis mb-0">Kelola semua pesanan tiket</p></div>
        <button class="btn btn-outline-secondary" onclick="showToast('CSV diekspor (simulasi)')"><i class="cil-data-transfer-down me-1"></i>Export CSV</button>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
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
                                <td class="text-medium-emphasis small">-</td>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/orders?detail=<?= $o['id'] ?>" class="btn btn-outline-primary"><i class="cil-search"></i></a>
                                        <?php if ($o['status'] === 'paid'): ?>
                                            <button class="btn btn-outline-danger" onclick="showToast('Refund diproses')"><i class="cil-action-undo"></i></button>
                                        <?php endif; ?>
                                    </div>
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
