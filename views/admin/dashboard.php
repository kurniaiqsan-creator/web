<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Dashboard</h1>
            <p class="text-medium-emphasis mb-0">Selamat datang, <?= View::e($_SESSION['user_name'] ?? 'Admin') ?></p>
        </div>
        <a href="<?= base_url('/admin/events/create') ?>" class="btn btn-primary"><i class="cil-plus me-1"></i> Buat Event</a>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fs-4 fw-semibold"><?= View::formatRupiah($salesToday) ?></div>
                            <div class="text-medium-emphasis text-uppercase small">Penjualan Hari Ini</div>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded p-3 d-flex align-items-center">
                            <i class="cil-money fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fs-4 fw-semibold"><?= $ordersPending ?></div>
                            <div class="text-medium-emphasis text-uppercase small">Order Pending</div>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded p-3 d-flex align-items-center">
                            <i class="cil-clock fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fs-4 fw-semibold"><?= $ticketsSold ?></div>
                            <div class="text-medium-emphasis text-uppercase small">Tiket Terjual</div>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded p-3 d-flex align-items-center">
                            <i class="cil-ticket fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fs-4 fw-semibold"><?= count($recentOrders) ?></div>
                            <div class="text-medium-emphasis text-uppercase small">Total Order</div>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded p-3 d-flex align-items-center">
                            <i class="cil-cart fs-4 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">Penjualan</h5>
            <div class="btn-group btn-group-sm" role="group" aria-label="Periode">
                <a href="<?= base_url('/admin/dashboard?days=7') ?>"
                   class="btn btn-outline-primary <?= ($chartDays ?? 7) === 7 ? 'active' : '' ?>">7 Hari</a>
                <a href="<?= base_url('/admin/dashboard?days=30') ?>"
                   class="btn btn-outline-primary <?= ($chartDays ?? 7) === 30 ? 'active' : '' ?>">30 Hari</a>
            </div>
        </div>
        <div class="card-body">
            <?php $hasSales = array_sum($chartTotals ?? []) > 0; ?>
            <?php if ($hasSales): ?>
                <div style="position:relative;height:320px">
                    <canvas id="salesChart"
                            data-labels='<?= htmlspecialchars(json_encode($chartLabels ?? []), ENT_QUOTES) ?>'
                            data-totals='<?= htmlspecialchars(json_encode($chartTotals ?? []), ENT_QUOTES) ?>'
                            data-counts='<?= htmlspecialchars(json_encode($chartCounts ?? []), ENT_QUOTES) ?>'></canvas>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-medium-emphasis">
                    <i class="cil-chart-line fs-1 d-block mb-2"></i>
                    Belum ada penjualan lunas dalam <?= (int)($chartDays ?? 7) ?> hari terakhir.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Pesanan Terbaru</h5>
            <a href="<?= base_url('/admin/orders') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><span class="font-monospace small"><?= View::e($o['order_code']) ?></span></td>
                                <td class="fw-semibold"><?= View::formatRupiah($o['total_amount_cents']) ?></td>
                                <td>
                                    <?php
                                    $statusBadge = [
                                        'paid' => 'success', 'pending' => 'warning',
                                        'failed' => 'danger', 'cancelled' => 'secondary', 'refunded' => 'info'
                                    ];
                                    $statusLabel = [
                                        'paid' => 'Lunas', 'pending' => 'Pending', 'failed' => 'Gagal',
                                        'cancelled' => 'Batal', 'refunded' => 'Refund'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $statusBadge[$o['status']] ?? 'secondary' ?>"><?= $statusLabel[$o['status']] ?? $o['status'] ?></span>
                                </td>
                                <td class="d-none d-md-table-cell text-medium-emphasis small"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                                <td class="text-end">
                                    <a href="<?= base_url('/admin/orders?detail=' . $o['id']) ?>" class="btn btn-sm btn-ghost-primary"><i class="cil-search"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-medium-emphasis">Belum ada pesanan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php ob_start(); ?>
<?php if (($hasSales ?? false)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('salesChart');
    if (!canvas || typeof Chart === 'undefined') return;

    var labels = JSON.parse(canvas.dataset.labels || '[]');
    var totals = JSON.parse(canvas.dataset.totals || '[]');
    var counts = JSON.parse(canvas.dataset.counts || '[]');

    var styles = getComputedStyle(document.documentElement);
    var primary = (styles.getPropertyValue('--cui-primary') || '#f97316').trim();
    var borderColor = (styles.getPropertyValue('--cui-border-color') || 'rgba(0,0,0,.1)').trim();
    var bodyColor = (styles.getPropertyValue('--cui-body-color') || '#333').trim();

    var rupiah = function (v) { return 'Rp' + Number(v).toLocaleString('id-ID'); };

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Penjualan',
                data: totals,
                borderColor: primary,
                backgroundColor: primary + '33',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: primary,
                yAxisID: 'y'
            }, {
                label: 'Jumlah Order',
                data: counts,
                borderColor: bodyColor,
                backgroundColor: 'transparent',
                borderDash: [5, 4],
                tension: 0.35,
                pointRadius: 2,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: bodyColor } },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            if (ctx.dataset.yAxisID === 'y') {
                                return ' ' + ctx.dataset.label + ': ' + rupiah(ctx.parsed.y);
                            }
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { color: borderColor }, ticks: { color: bodyColor } },
                y: {
                    position: 'left',
                    grid: { color: borderColor },
                    ticks: { color: bodyColor, callback: function (v) { return rupiah(v); } }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    ticks: { color: bodyColor, precision: 0 }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
<?php $scripts = ob_get_clean(); $content .= $scripts; ?>
<?php require VIEW_PATH . '/layouts/admin.php'; ?>
