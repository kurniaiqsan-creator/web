<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Promosi</h1>
            <p class="text-medium-emphasis mb-0">Kode diskon untuk dipakai customer saat checkout.</p>
        </div>
        <a href="<?= base_url('/admin/promotions/create') ?>" class="btn btn-primary"><i class="cil-plus me-1"></i>Buat Promo</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Kode</th>
                            <th>Tipe</th>
                            <th class="text-end">Nilai</th>
                            <th class="text-end">Pemakaian</th>
                            <th class="d-none d-md-table-cell">Periode</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $now = time();
                        foreach ($promotions as $p):
                            $from = strtotime($p['valid_from']);
                            $to   = strtotime($p['valid_to']);
                            $usageLimit = (int)($p['usage_limit'] ?? 0);
                            $used       = (int)($p['used_count'] ?? 0);
                            $exhausted  = $usageLimit > 0 && $used >= $usageLimit;
                            if ($now < $from) { $st='upcoming';   $bg='secondary'; $lbl='Akan datang'; }
                            elseif ($now > $to) { $st='expired';   $bg='secondary'; $lbl='Kedaluwarsa'; }
                            elseif ($exhausted) { $st='exhausted'; $bg='warning';   $lbl='Habis'; }
                            else { $st='active'; $bg='success'; $lbl='Aktif'; }
                        ?>
                            <tr>
                                <td class="ps-4 font-monospace fw-semibold"><?= View::e($p['code']) ?></td>
                                <td class="text-capitalize"><?= View::e($p['type']) ?></td>
                                <td class="text-end font-monospace">
                                    <?php if ($p['type'] === 'percentage'): ?>
                                        <?= rtrim(rtrim(number_format((float)$p['value'], 2, '.', ''), '0'), '.') ?>%
                                    <?php else: ?>
                                        <?= View::formatRupiah((int)$p['value']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <?= $used ?><?= $usageLimit > 0 ? ' / ' . $usageLimit : '' ?>
                                </td>
                                <td class="d-none d-md-table-cell small text-medium-emphasis">
                                    <?= date('d M Y', $from) ?> → <?= date('d M Y', $to) ?>
                                </td>
                                <td><span class="badge bg-<?= $bg ?>"><?= $lbl ?></span></td>
                                <td class="text-end pe-4">
                                    <a href="<?= base_url('/admin/promotions/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary me-1"><i class="cil-pencil"></i></a>
                                    <form method="post" action="<?= base_url('/admin/promotions/' . $p['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus promo <?= View::e($p['code']) ?>?')">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="cil-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($promotions)): ?>
                            <tr><td colspan="7" class="text-center text-medium-emphasis py-5"><i class="cil-gift fs-1 mb-2 d-block"></i>Belum ada promo.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
