<?php ob_start(); ?>
<div class="mx-auto max-w-4xl px-4 py-8">
    <div class="text-center mb-10">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-500 text-white text-2xl font-bold mb-4">
            <?= strtoupper(substr($tenant['name'] ?? 'AN', 0, 2)) ?>
        </div>
        <h1 class="text-2xl font-bold"><?= View::e($tenant['name']) ?></h1>
    </div>

    <h2 class="text-lg font-semibold mb-4">Event Mendatang</h2>
    <div class="space-y-4">
        <?php foreach ($events as $event): ?>
            <a href="<?= base_url("/{$tenant['slug']}/events/{$event['id']}") ?>" class="card block hover:shadow-md transition-shadow">
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex-shrink-0 text-sm font-semibold">
                        <?= View::formatDate($event['start_time']) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold truncate"><?= View::e($event['title']) ?></h3>
                        <p class="text-sm text-gray-500"><?= View::e($event['venue_name']) ?></p>
                    </div>
                    <span class="btn btn-primary btn-md">Beli Tiket</span>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (empty($events)): ?>
            <p class="text-center py-12 text-gray-500">Belum ada event mendatang</p>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
