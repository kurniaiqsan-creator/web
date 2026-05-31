<?php
ob_start();
$selectedEvents = $promotion['applicable_event_ids'] ?? [];
?>
<div>
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= base_url('/admin/promotions') ?>" class="btn btn-outline-secondary btn-sm"><i class="cil-arrow-left"></i></a>
        <h1 class="fs-3 fw-bold mb-0"><?= $promotion ? 'Edit Promo ' . View::e($promotion['code']) : 'Buat Promo Baru' ?></h1>
    </div>

    <form method="post" action="<?= base_url($promotion ? '/admin/promotions/' . $promotion['id'] : '/admin/promotions') ?>">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Detail Promo</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input class="form-control text-uppercase font-monospace" name="code" value="<?= View::e($promotion['code'] ?? '') ?>" required maxlength="64" placeholder="MIS. PROMO10">
                                <div class="form-text">Akan di-uppercase otomatis. Harus unik per tenant.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipe</label>
                                <select class="form-select" name="type">
                                    <?php foreach (['percentage' => 'Persentase (%)', 'fixed' => 'Nominal (IDR)'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($promotion['type'] ?? 'percentage') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nilai <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control font-monospace" name="value" value="<?= View::e($promotion['value'] ?? '') ?>" required>
                                <div class="form-text">Persentase: 1–100. Nominal: IDR (mis. 50000).</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Maks Pemakaian</label>
                                <input type="number" min="0" class="form-control" name="usage_limit" value="<?= (int)($promotion['usage_limit'] ?? 0) ?>">
                                <div class="form-text">0 = unlimited.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Valid From <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="valid_from"
                                       value="<?= $promotion ? str_replace(' ', 'T', substr($promotion['valid_from'] ?? '', 0, 16)) : '' ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Valid To <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="valid_to"
                                       value="<?= $promotion ? str_replace(' ', 'T', substr($promotion['valid_to'] ?? '', 0, 16)) : '' ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Berlaku untuk Event</label>
                                <select class="form-select" name="applicable_event_ids[]" multiple size="6">
                                    <?php foreach ($events as $e): ?>
                                        <option value="<?= $e['id'] ?>" <?= in_array((int)$e['id'], array_map('intval', $selectedEvents), true) ? 'selected' : '' ?>>
                                            <?= View::e($e['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Tahan Ctrl/Cmd untuk pilih multiple. Kosong = berlaku untuk semua event.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Aksi</h5></div>
                    <div class="card-body d-grid gap-2">
                        <button class="btn btn-primary" type="submit"><i class="cil-save me-1"></i>Simpan</button>
                        <a href="<?= base_url('/admin/promotions') ?>" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
                <?php if ($promotion): ?>
                <div class="card mt-3">
                    <div class="card-header"><h5 class="card-title mb-0">Statistik</h5></div>
                    <div class="card-body small">
                        <p class="mb-2 d-flex justify-content-between">Sudah dipakai <span class="fw-semibold font-monospace"><?= (int)$promotion['used_count'] ?></span></p>
                        <p class="mb-2 d-flex justify-content-between">Maks <span class="fw-semibold font-monospace"><?= (int)$promotion['usage_limit'] ?: '∞' ?></span></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
