<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Tiket & Harga</h1>
            <p class="text-medium-emphasis mb-0">Kategori tiket per tenant (mis. VIP, Reguler). Dipakai oleh event editor saat assign kursi.</p>
        </div>
        <button class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#newCategoryModal"><i class="cil-plus me-1"></i>Buat Kategori</button>
    </div>

    <!-- Each row has its own form declared here, referenced via the HTML5 `form` attribute. -->
    <?php foreach ($categories as $c): ?>
        <form method="post" action="<?= base_url('/admin/ticket-categories/' . $c['id']) ?>" id="catEdit<?= $c['id'] ?>"></form>
        <form method="post" action="<?= base_url('/admin/ticket-categories/' . $c['id'] . '/delete') ?>" id="catDel<?= $c['id'] ?>" onsubmit="return confirm('Hapus kategori ini?')"></form>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th class="text-end">Harga (IDR)</th>
                            <th class="text-end">Kuota</th>
                            <th class="text-end">Dipakai (seats)</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): $fid = 'catEdit' . $c['id']; ?>
                            <tr>
                                <td class="ps-4"><input class="form-control form-control-sm" form="<?= $fid ?>" name="name" value="<?= View::e($c['name']) ?>" required></td>
                                <td class="text-end" style="max-width:180px">
                                    <input type="number" min="0" class="form-control form-control-sm text-end font-monospace" form="<?= $fid ?>" name="price_cents" value="<?= (int)$c['price_cents'] ?>">
                                </td>
                                <td class="text-end" style="max-width:140px">
                                    <input type="number" min="0" class="form-control form-control-sm text-end font-monospace" form="<?= $fid ?>" name="quota" value="<?= (int)($c['quota'] ?? 0) ?>">
                                </td>
                                <td class="text-end font-monospace"><?= (int)($c['used_seats'] ?? 0) ?></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-primary me-1" type="submit" form="<?= $fid ?>"><i class="cil-save"></i></button>
                                    <?php if ((int)($c['used_seats'] ?? 0) === 0): ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" form="catDel<?= $c['id'] ?>"><i class="cil-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="5" class="text-center text-medium-emphasis py-5"><i class="cil-tag fs-1 mb-2 d-block"></i>Belum ada kategori. Klik <strong>Buat Kategori</strong>.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer small text-medium-emphasis">
            Harga ditulis langsung dalam rupiah (mis. 200000 = Rp200.000). Field kuota opsional untuk general admission.
        </div>
    </div>
</div>

<div class="modal fade" id="newCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= base_url('/admin/ticket-categories') ?>">
            <div class="modal-header"><h5 class="modal-title">Buat Kategori Baru</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input class="form-control" name="name" placeholder="VIP / Regular / Festival" required>
                </div>
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label">Harga (IDR)</label>
                        <input type="number" min="0" class="form-control font-monospace" name="price_cents" value="0" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Kuota</label>
                        <input type="number" min="0" class="form-control font-monospace" name="quota" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Batal</button>
                <button class="btn btn-primary" type="submit"><i class="cil-save me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
