<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Venue</h1>
            <p class="text-medium-emphasis mb-0">Tempat penyelenggaraan event</p>
        </div>
        <a href="<?= base_url('/admin/venues/create') ?>" class="btn btn-primary"><i class="cil-plus me-1"></i> Buat Venue</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th class="d-none d-md-table-cell">Alamat</th>
                            <th class="text-end">Kapasitas</th>
                            <th class="text-end">Event</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($venues as $v): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= View::e($v['name']) ?></td>
                                <td class="d-none d-md-table-cell text-medium-emphasis small">
                                    <?= View::e($v['address'] ?? '—') ?>
                                </td>
                                <td class="text-end"><?= number_format((int)($v['capacity'] ?? 0), 0, ',', '.') ?></td>
                                <td class="text-end"><?= (int)($v['event_count'] ?? 0) ?></td>
                                <td class="text-end pe-4">
                                    <a href="<?= base_url('/admin/venues/' . $v['id']) ?>" class="btn btn-sm btn-outline-primary me-1"><i class="cil-pencil me-1"></i>Edit</a>
                                    <?php if ((int)($v['event_count'] ?? 0) === 0): ?>
                                        <form method="post" action="<?= base_url('/admin/venues/' . $v['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus venue ini?')">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="cil-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($venues)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-medium-emphasis">
                                <i class="cil-room fs-1 mb-2 d-block"></i> Belum ada venue. Klik <strong>Buat Venue</strong> untuk mulai.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
