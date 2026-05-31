<?php ob_start(); ?>
<div class="container-lg d-flex align-items-center justify-content-center py-5" style="min-height:calc(100vh - 8rem)">
    <div style="width:100%;max-width:24rem">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold mb-3"
                          style="width:44px;height:44px"><?= View::e(strtoupper(substr($tenant['name'] ?? 'V', 0, 1))) ?></span>
                    <h1 class="h4 fw-bold mb-1">Masuk</h1>
                    <p class="small text-medium-emphasis mb-0">Akses tiket &amp; riwayat pesanan kamu di <?= View::e($tenant['name']) ?>.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= View::e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('/' . $tenant['slug'] . '/masuk') ?>">
                    <input type="hidden" name="next" value="<?= View::e($next ?? '') ?>">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= View::e($email ?? '') ?>"
                               placeholder="contoh@email.com" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>

                <p class="text-center small text-medium-emphasis mt-3 mb-0">
                    Belum punya akun?
                    <a href="<?= base_url('/' . $tenant['slug'] . '/daftar' . (!empty($next) ? '?next=' . urlencode($next) : '')) ?>">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
