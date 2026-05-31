<?php ob_start(); ?>
<div class="container-lg d-flex align-items-center justify-content-center py-5" style="min-height:calc(100vh - 8rem)">
    <div style="width:100%;max-width:24rem">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold mb-3"
                          style="width:44px;height:44px"><?= View::e(strtoupper(substr($tenant['name'] ?? 'V', 0, 1))) ?></span>
                    <h1 class="h4 fw-bold mb-1">Reset Password</h1>
                    <p class="small text-medium-emphasis mb-0">Buat password baru untuk akunmu.</p>
                </div>

                <?php if (!empty($invalid)): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        Link reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.
                    </div>
                    <a href="<?= base_url('/lupa-password') ?>" class="btn btn-outline-primary w-100">Minta Link Baru</a>
                <?php else: ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 small mb-3"><?= View::e($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="<?= base_url('/reset-password/' . urlencode($token)) ?>">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Password Baru</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
