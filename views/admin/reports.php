<?php ob_start(); ?>
<?php $f = $filters ?? ['from' => '', 'to' => '']; ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h1 class="fs-3 fw-bold mb-1">Laporan</h1><p class="text-medium-emphasis mb-0">Ringkasan penjualan, pendapatan &amp; kehadiran</p></div>
        <a class="btn btn-outline-secondary" href="<?= base_url('/admin/reports/export?' . http_build_query($f)) ?>"><i class="cil-data-transfer-down me-1"></i>Export CSV</a>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('/admin/reports') ?>" class="d-flex flex-wrap align-items-end gap-3">
                <div class="d-flex flex-column">
                    <label class="form-label mb-1 small">Dari (tanggal lunas)</label>
                    <input type="date" name="from" class="form-control form-control-sm" style="width:160px" value="<?= View::e($f['from']) ?>">
                </div>
                <div class="d-flex flex-column">
                    <label class="form-label mb-1 small">Sampai</label>
                    <input type="date" name="to" class="form-control form-control-sm" style="width:160px" value="<?= View::e($f['to']) ?>">
                </div>
                <button class="btn btn-primary btn-sm" type="submit"><i class="cil-filter me-1"></i>Terapkan</button>
                <?php if ($f['from'] !== '' || $f['to'] !== ''): ?>
                    <a href="<?= base_url('/admin/reports') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <?php
        $totalSales = array_sum(array_column($byEvent, 'total_sales'));
        $totalOrders = array_sum(array_column($byEvent, 'order_count'));
        $totalTickets = array_sum(array_column($byEvent, 'tickets_total'));
        $totalUsed = array_sum(array_column($byEvent, 'tickets_used'));
        $attendancePct = $totalTickets > 0 ? round($totalUsed / $totalTickets * 100, 1) : 0;
        ?>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= View::formatRupiah($totalSales) ?></div><div class="text-medium-emphasis small">Total Penjualan</div></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= $totalOrders ?></div><div class="text-medium-emphasis small">Total Order</div></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= (int)$totalTickets ?></div><div class="text-medium-emphasis small">Tiket Terjual</div></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body text-center"><div class="fs-4 fw-bold"><?= $attendancePct ?>%</div><div class="text-medium-emphasis small">Kehadiran (<?= (int)$totalUsed ?>/<?= (int)$totalTickets ?>)</div></div></div>
        </div>
    </div>

    <!-- By Event -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Penjualan &amp; Kehadiran per Event</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Event</th>
                            <th class="text-end">Penjualan</th>
                            <th class="text-end">Order</th>
                            <th class="text-end">Tiket</th>
                            <th class="text-end pe-4">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byEvent as $e):
                            $tt = (int)$e['tickets_total']; $tu = (int)$e['tickets_used'];
                            $pct = $tt > 0 ? round($tu / $tt * 100, 1) : 0;
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= View::e($e['title']) ?></td>
                                <td class="text-end fw-semibold"><?= View::formatRupiah($e['total_sales']) ?></td>
                                <td class="text-end"><?= (int)$e['order_count'] ?></td>
                                <td class="text-end"><?= $tt ?></td>
                                <td class="text-end pe-4">
                                    <?php if ($tt > 0): ?>
                                        <span class="badge bg-<?= $pct >= 70 ? 'success' : ($pct >= 30 ? 'warning' : 'secondary') ?>-subtle text-<?= $pct >= 70 ? 'success' : ($pct >= 30 ? 'warning' : 'secondary') ?>-emphasis">
                                            <?= $tu ?>/<?= $tt ?> (<?= $pct ?>%)
                                        </span>
                                    <?php else: ?><span class="text-medium-emphasis">—</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byEvent)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-medium-emphasis">Belum ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daily breakdown -->
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Breakdown Harian (penjualan lunas)</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th class="ps-4">Tanggal</th><th class="text-end">Order</th><th class="text-end pe-4">Penjualan</th></tr></thead>
                    <tbody>
                        <?php foreach (($daily ?? []) as $d): ?>
                            <tr>
                                <td class="ps-4"><?= date('d M Y', strtotime($d['d'])) ?></td>
                                <td class="text-end"><?= (int)$d['order_count'] ?></td>
                                <td class="text-end pe-4 fw-semibold"><?= View::formatRupiah((int)$d['total_sales']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($daily)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-medium-emphasis">Belum ada penjualan lunas pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
