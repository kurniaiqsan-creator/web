<?php
ob_start();
$badge = ['paid'=>'success','pending'=>'warning','failed'=>'danger','cancelled'=>'secondary','refunded'=>'info'];
$label = ['paid'=>'Lunas','pending'=>'Pending','failed'=>'Gagal','cancelled'=>'Batal','refunded'=>'Refund'];
$ticketStatusLabel = ['valid'=>'Valid','used'=>'Terpakai','refunded'=>'Refund','cancelled'=>'Batal'];
$ticketStatusBadge = ['valid'=>'success','used'=>'info','refunded'=>'warning','cancelled'=>'secondary'];
?>
<div>
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <a href="<?= base_url('/admin/orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="cil-arrow-left"></i></a>
        <div class="me-auto">
            <h1 class="fs-3 fw-bold mb-1 font-monospace"><?= View::e($order['order_code']) ?></h1>
            <span class="badge bg-<?= $badge[$order['status']] ?? 'secondary' ?>"><?= $label[$order['status']] ?? $order['status'] ?></span>
            <span class="text-medium-emphasis small">· <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
        </div>
        <?php if ($order['status'] === 'paid'): ?>
            <button type="button" class="btn btn-outline-danger" data-coreui-toggle="modal" data-coreui-target="#refundModal"><i class="cil-action-undo me-1"></i>Refund</button>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Items -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-cart me-2"></i>Item Pesanan</h5></div>
                <div class="card-body p-0">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr><th class="ps-4">Seat</th><th>Kategori</th><th class="text-end pe-4">Harga</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= View::e($it['seat_label'] ?? '—') ?></td>
                                    <td><?= View::e($it['category_name'] ?? '—') ?></td>
                                    <td class="text-end pe-4 font-monospace"><?= View::formatRupiah($it['price_cents']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="3" class="text-center text-medium-emphasis py-4">Tidak ada item</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="ps-4" colspan="2">Total</td>
                                <td class="text-end pe-4 font-monospace"><?= View::formatRupiah($order['total_amount_cents']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Tickets -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-barcode me-2"></i>Tiket (<?= count($tickets) ?>)</h5></div>
                <div class="card-body p-0">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th class="ps-4">Ticket UID</th><th>Seat</th><th>Status</th><th class="d-none d-md-table-cell">Issued</th></tr></thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td class="ps-4 font-monospace small"><?= View::e($t['ticket_uid']) ?></td>
                                    <td><?= View::e($t['seat_label'] ?? '—') ?></td>
                                    <td><span class="badge bg-<?= $ticketStatusBadge[$t['status']] ?? 'secondary' ?>"><?= $ticketStatusLabel[$t['status']] ?? $t['status'] ?></span></td>
                                    <td class="d-none d-md-table-cell text-medium-emphasis small"><?= $t['issued_at'] ? date('d M Y H:i', strtotime($t['issued_at'])) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tickets)): ?>
                                <tr><td colspan="4" class="text-center text-medium-emphasis py-4">Belum ada tiket di-generate</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Refunds -->
            <?php if (!empty($refunds)): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-action-undo me-2"></i>Riwayat Refund</h5></div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead><tr><th class="ps-4">UID</th><th>Jumlah</th><th>Alasan</th><th>Status</th><th class="d-none d-md-table-cell">Tanggal</th></tr></thead>
                        <tbody>
                            <?php foreach ($refunds as $r): ?>
                                <tr>
                                    <td class="ps-4 font-monospace small"><?= View::e($r['refund_uid']) ?></td>
                                    <td class="font-monospace"><?= View::formatRupiah($r['amount_cents']) ?></td>
                                    <td class="small"><?= View::e($r['reason'] ?? '—') ?></td>
                                    <td><span class="badge bg-<?= $r['status']==='succeeded'?'success':($r['status']==='failed'?'danger':'secondary') ?>"><?= View::e($r['status']) ?></span></td>
                                    <td class="d-none d-md-table-cell text-medium-emphasis small"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Customer card -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-user me-2"></i>Pembeli</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-medium-emphasis">Nama</dt>
                        <dd class="col-8"><?= View::e($order['user_name'] ?? $order['customer_name'] ?? '—') ?></dd>
                        <dt class="col-4 text-medium-emphasis">Email</dt>
                        <dd class="col-8"><?= View::e($order['user_email'] ?? $order['customer_email'] ?? '—') ?></dd>
                        <dt class="col-4 text-medium-emphasis">Telepon</dt>
                        <dd class="col-8"><?= View::e($order['customer_phone'] ?? '—') ?></dd>
                    </dl>
                    <?php if (!empty($order['customer_email']) || !empty($order['user_id'])): ?>
                        <hr class="my-3">
                        <a class="btn btn-sm btn-outline-secondary w-100"
                           href="<?= base_url($order['user_id']
                               ? '/admin/customers/' . (int)$order['user_id']
                               : '/admin/customers/email:' . urlencode((string)$order['customer_email'])) ?>">
                            <i class="cil-arrow-right me-1"></i>Lihat profil customer
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Event card -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-calendar me-2"></i>Event</h5></div>
                <div class="card-body">
                    <p class="fw-semibold mb-1"><?= View::e($order['event_title'] ?? '—') ?></p>
                    <p class="small text-medium-emphasis mb-1"><i class="cil-room me-1"></i><?= View::e($order['venue_name'] ?? '—') ?></p>
                    <p class="small text-medium-emphasis mb-0"><i class="cil-clock me-1"></i><?= $order['event_start'] ? date('d M Y, H:i', strtotime($order['event_start'])) : '—' ?></p>
                </div>
            </div>

            <!-- Payment card -->
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-credit-card me-2"></i>Pembayaran</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-medium-emphasis">Provider</dt>
                        <dd class="col-7 text-uppercase"><?= View::e($order['payment_provider'] ?? '—') ?></dd>
                        <dt class="col-5 text-medium-emphasis">Reference</dt>
                        <dd class="col-7 font-monospace small"><?= View::e($order['payment_reference'] ?? '—') ?></dd>
                        <dt class="col-5 text-medium-emphasis">Total</dt>
                        <dd class="col-7 fw-bold font-monospace"><?= View::formatRupiah($order['total_amount_cents']) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($order['status'] === 'paid'): ?>
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= base_url('/admin/orders/' . $order['id'] . '/refund') ?>">
            <div class="modal-header"><h5 class="modal-title">Refund Pesanan <?= View::e($order['order_code']) ?></h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small"><i class="cil-warning me-1"></i>Refund stub: status order akan jadi <code>refunded</code> + seat dilepas, tapi saldo di PG <strong>belum</strong> di-trigger (perlu integrasi).</div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Refund (IDR)</label>
                    <input type="number" class="form-control" name="amount_cents" placeholder="<?= (int)$order['total_amount_cents'] ?>" min="0">
                    <div class="form-text">Kosongkan untuk refund total <?= View::formatRupiah($order['total_amount_cents']) ?>.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan</label>
                    <textarea class="form-control" name="reason" rows="2" placeholder="Mis. Event dibatalkan"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Batal</button>
                <button class="btn btn-danger" type="submit"><i class="cil-action-undo me-1"></i>Proses Refund</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
