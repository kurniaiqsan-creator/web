<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Dashboard</h1>
            <p class="text-medium-emphasis mb-0">Selamat datang, <?= View::e($_SESSION['user_name'] ?? 'Admin') ?></p>
        </div>
        <a href="/admin/events/create" class="btn btn-primary"><i class="cil-plus me-1"></i> Buat Event</a>
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

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Pesanan Terbaru</h5>
            <a href="/admin/orders" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
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
                                    <a href="/admin/orders?detail=<?= $o['id'] ?>" class="btn btn-sm btn-ghost-primary"><i class="cil-search"></i></a>
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
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
