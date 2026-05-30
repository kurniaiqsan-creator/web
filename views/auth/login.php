<?php ob_start(); ?>
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="card p-6">
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold">Masuk</h1>
                <p class="mt-1 text-sm text-gray-500">Masuk ke dashboard tenant kamu</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700"><?= View::e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= base_url('/login') ?>" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" class="input" placeholder="admin@acousticnights.com" required></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" class="input" placeholder="••••••••" required></div>
                <button type="submit" class="btn btn-primary btn-lg w-full">Masuk</button>
            </form>

            <div class="mt-4 text-center text-sm">
                <span class="text-gray-500">Belum punya akun? </span>
                <a href="<?= base_url('/onboarding') ?>" class="text-brand-600 hover:text-brand-700 font-medium">Buat tenant</a>
            </div>
        </div>

        <div class="mt-4 rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-700">
            <strong>Demo:</strong> Email: admin@acousticnights.com / Password: password
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
