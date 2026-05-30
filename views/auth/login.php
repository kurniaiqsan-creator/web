<?php ob_start(); ?>
<div class="container-lg d-flex align-items-center justify-content-center py-5" style="min-height:calc(100vh - 8rem)">
    <div style="width:100%;max-width:24rem">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-circle fw-bold mb-3"
                          style="width:44px;height:44px">V</span>
                    <h1 class="h4 fw-bold mb-1">Masuk Admin</h1>
                    <p class="small text-medium-emphasis mb-0">Akses dashboard untuk kelola event &amp; tiket.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= View::e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('/login') ?>">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="admin@acousticnights.com" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small mb-0">
            <strong>Demo:</strong> <code>admin@acousticnights.com</code> / <code>password</code>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
