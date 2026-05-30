<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h1 class="fs-3 fw-bold mb-1">Laporan</h1><p class="text-medium-emphasis mb-0">Ringkasan penjualan & pendapatan</p></div>
        <button class="btn btn-outline-secondary" onclick="showToast('CSV diekspor (simulasi)')"><i class="cil-data-transfer-down me-1"></i>Export CSV</button>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2"><label class="form-label mb-0 small">Dari</label><input type="date" class="form-control form-control-sm" style="width:150px"></div>
            <div class="d-flex align-items-center gap-2"><label class="form-label mb-0 small">Sampai</label><input type="date" class="form-control form-control-sm" style="width:150px"></div>
            <button class="btn btn-primary btn-sm">Terapkan</button>
        </div>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <?php
        $totalSales = array_sum(array_column($byEvent, 'total_sales'));
        $totalOrders = array_sum(array_column($byEvent, 'order_count'));
        ?>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= View::formatRupiah($totalSales) ?></div><div class="text-medium-emphasis small">Total Penjualan</div></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= $totalOrders ?></div><div class="text-medium-emphasis small">Total Order</div></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= count($byEvent) ?></div><div class="text-medium-emphasis small">Event</div></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= $totalOrders > 0 ? View::formatRupiah((int)($totalSales / $totalOrders)) : 'Rp0' ?></div><div class="text-medium-emphasis small">Rata-rata Order</div></div></div>
        </div>
    </div>

    <!-- By Event -->
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Penjualan per Event</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr><th class="ps-4">Event</th><th class="text-end">Penjualan</th><th class="text-end">Order</th><th class="text-end pe-4">Rata-rata</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byEvent as $e): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= View::e($e['title']) ?></td>
                                <td class="text-end fw-semibold"><?= View::formatRupiah($e['total_sales']) ?></td>
                                <td class="text-end"><?= $e['order_count'] ?></td>
                                <td class="text-end pe-4"><?= $e['order_count'] > 0 ? View::formatRupiah((int)($e['total_sales'] / $e['order_count'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
