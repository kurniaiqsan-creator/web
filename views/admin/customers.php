<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Customer</h1>
            <p class="text-medium-emphasis mb-0">Daftar pembeli yang pernah memesan tiket di event kamu.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <input type="search" id="customerSearch" class="form-control form-control-sm" style="min-width:240px"
                   placeholder="Cari nama atau email…" oninput="filterCustomers(this.value)">
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="customerTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Customer</th>
                            <th class="d-none d-md-table-cell">Telepon</th>
                            <th class="text-end">Order</th>
                            <th class="text-end">Lunas</th>
                            <th class="text-end">Total Belanja</th>
                            <th class="d-none d-lg-table-cell">Terakhir Order</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c):
                            $idPart = !empty($c['user_id']) && (int)$c['user_id'] > 0
                                ? (int)$c['user_id']
                                : 'email:' . $c['email'];
                            $href = base_url('/admin/customers/' . (is_string($idPart) ? urlencode($idPart) : $idPart));
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="avatar avatar-md d-inline-flex align-items-center justify-content-center text-white rounded-circle brand-mark fw-semibold"
                                              style="width:36px;height:36px"><?= View::e(strtoupper(substr($c['name'] ?: '?', 0, 2))) ?></span>
                                        <div>
                                            <div class="fw-medium"><?= View::e($c['name']) ?></div>
                                            <div class="small text-medium-emphasis"><?= View::e($c['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell text-medium-emphasis small"><?= View::e($c['phone'] ?: '—') ?></td>
                                <td class="text-end"><?= (int)$c['order_count'] ?></td>
                                <td class="text-end"><span class="badge bg-success-subtle text-success-emphasis"><?= (int)$c['paid_orders'] ?></span></td>
                                <td class="text-end fw-semibold"><?= View::formatRupiah((int)$c['total_spent_cents']) ?></td>
                                <td class="d-none d-lg-table-cell text-medium-emphasis small">
                                    <?= $c['last_order_at'] ? date('d M Y, H:i', strtotime($c['last_order_at'])) : '—' ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= View::e($href) ?>">
                                        <i class="cil-search me-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customers)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-medium-emphasis">
                                <i class="cil-people fs-1 mb-2 d-block"></i>
                                Belum ada pesanan customer.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterCustomers(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#customerTable tbody tr');
    rows.forEach(function (r) {
        var t = r.textContent.toLowerCase();
        r.style.display = (q === '' || t.indexOf(q) !== -1) ? '' : 'none';
    });
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
