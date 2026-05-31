<?php ob_start(); ?>
<div class="container-lg d-flex align-items-center justify-content-center py-5" style="min-height:calc(100vh - 8rem)">
    <div style="width:100%;max-width:24rem">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold mb-3"
                          style="width:44px;height:44px"><?= View::e(strtoupper(substr($tenant['name'] ?? 'V', 0, 1))) ?></span>
                    <h1 class="h4 fw-bold mb-1">Lupa Password</h1>
                    <p class="small text-medium-emphasis mb-0">Masukkan email akunmu, kami kirim link reset.</p>
                </div>

                <?php if (!empty($done)): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        Jika email terdaftar, link reset password sudah dikirim. Cek inbox (dan folder spam) kamu.
                    </div>
                    <a href="<?= base_url('/login') ?>" class="btn btn-outline-primary w-100">Kembali ke Masuk</a>
                <?php else: ?>
                    <form method="POST" action="<?= base_url('/lupa-password') ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="contoh@email.com" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
                    </form>
                    <p class="text-center small text-medium-emphasis mt-3 mb-0">
                        <a href="<?= base_url('/login') ?>">Kembali ke Masuk</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
