<?php
ob_start();
$nameParts = preg_split('/\s+/', trim((string)$me['name'])) ?: [];
$initials  = strtoupper(substr($nameParts[0] ?? 'A', 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
?>
<div style="max-width:52rem">
    <div class="mb-4">
        <h1 class="fs-3 fw-bold mb-1">Profil Saya</h1>
        <p class="text-medium-emphasis mb-0">Kelola informasi akun & keamanan kamu.</p>
    </div>

    <form method="post" action="<?= base_url('/admin/profile') ?>" enctype="multipart/form-data">
        <!-- Informasi Profil -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0"><i class="cil-user me-2"></i>Informasi Profil</h5></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="flex-shrink-0"><?= View::avatarHtml($me['avatar_url'] ?? null, $initials, 72) ?></div>
                    <div>
                        <label class="form-label mb-1">Foto Profil</label>
                        <input type="file" name="avatar" class="form-control form-control-sm" accept="image/png,image/jpeg,image/gif,image/webp">
                        <div class="form-text">PNG/JPG/GIF/WEBP, maks 2 MB.</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" value="<?= View::e($me['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= View::e($me['email']) ?>" disabled>
                        <div class="form-text">Email tidak bisa diubah.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keamanan -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0"><i class="cil-lock-locked me-2"></i>Keamanan</h5></div>
            <div class="card-body">
                <p class="small text-medium-emphasis mb-3">Ganti password (kosongkan jika tidak ingin mengubah).</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Konfirmasi</label>
                        <input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('/admin/dashboard') ?>" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="cil-save me-1"></i>Simpan Perubahan</button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
