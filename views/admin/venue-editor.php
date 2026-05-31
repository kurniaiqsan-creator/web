<?php ob_start(); ?>
<div>
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= base_url('/admin/venues') ?>" class="btn btn-outline-secondary btn-sm"><i class="cil-arrow-left me-1"></i></a>
        <h1 class="fs-3 fw-bold mb-0"><?= $venue ? 'Edit Venue' : 'Buat Venue Baru' ?></h1>
    </div>

    <form method="post" action="<?= base_url($venue ? '/admin/venues/' . $venue['id'] : '/admin/venues') ?>">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Informasi Venue</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Venue <span class="text-danger">*</span></label>
                                <input class="form-control" name="name" value="<?= View::e($venue['name'] ?? '') ?>" required maxlength="255" placeholder="Mis. Gedung Kesenian Jakarta">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Jl. Gedung Kesenian No. 1, Jakarta Pusat"><?= View::e($venue['address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kapasitas Maks</label>
                                <input type="number" min="0" class="form-control" name="capacity" value="<?= (int)($venue['capacity'] ?? 0) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Latitude</label>
                                <input type="number" step="0.000001" class="form-control" name="lat" value="<?= View::e($venue['lat'] ?? '') ?>" placeholder="-6.175">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Longitude</label>
                                <input type="number" step="0.000001" class="form-control" name="lng" value="<?= View::e($venue['lng'] ?? '') ?>" placeholder="106.825">
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL Gambar Denah</label>
                                <input type="url" class="form-control" name="map_image_url" value="<?= View::e($venue['map_image_url'] ?? '') ?>" placeholder="https://cdn.example.com/venue-map.png">
                                <div class="form-text">Opsional. Tampil di event editor sebagai referensi seat designer.</div>
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
                        <a href="<?= base_url('/admin/venues') ?>" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
