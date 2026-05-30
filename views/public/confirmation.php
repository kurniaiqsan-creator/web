<?php ob_start(); ?>
<div class="mx-auto max-w-2xl px-4 py-6">
    <a href="<?= base_url('/' . $tenant['slug']) ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">&larr; Kembali ke event</a>

    <?php if (empty($order)): ?>
        <div class="card p-6 text-center">
            <h1 class="text-lg font-bold">Order tidak ditemukan</h1>
            <p class="mt-2 text-sm text-gray-500">Order <span class="font-mono"><?= View::e($orderCode) ?></span> tidak ditemukan.</p>
            <a href="<?= base_url('/' . $tenant['slug']) ?>" class="btn btn-primary btn-md mt-4 inline-flex">Kembali</a>
        </div>
    <?php else: ?>
        <div class="card p-4 sm:p-5 text-center mb-6">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h1 class="text-xl font-bold">Pembayaran Berhasil!</h1>
            <p class="mt-1 text-sm text-gray-500">Order <span class="font-mono font-semibold"><?= View::e($orderCode) ?></span></p>
            <div class="mt-4 flex items-center justify-center gap-3">
                <span class="badge badge-success">Lunas</span>
                <span class="text-sm text-gray-600"><?= View::formatRupiah($order['total_amount_cents']) ?></span>
            </div>
        </div>

        <h2 class="font-semibold flex items-center gap-2 mb-4">E-Ticket (<?= count($tickets) ?>)</h2>

        <div class="space-y-5">
            <?php foreach ($tickets as $t): $ticketUrl = base_url('/t/' . $t['ticket_token']); ?>
                <div class="card overflow-hidden">
                    <div class="bg-brand-500 h-2"></div>
                    <div class="p-5 sm:p-6">
                        <div class="text-center mb-4">
                            <h3 class="text-lg font-bold"><?= View::e($t['event_title']) ?></h3>
                            <div class="mt-2 flex items-center justify-center gap-4 text-sm text-gray-500">
                                <span><?= View::formatDate($t['start_time']) ?></span>
                                <span><?= View::e($t['venue_name']) ?></span>
                            </div>
                        </div>

                        <div class="flex justify-center mb-4">
                            <div class="rounded-xl border bg-white p-3">
                                <div class="qr-code" data-url="<?= View::e($ticketUrl) ?>"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Kursi</p><p class="font-semibold"><?= View::e($t['seat_label'] ?: 'GA') ?></p></div>
                            <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Status</p><span class="badge badge-success"><?= $t['status'] === 'valid' ? 'Valid' : View::e($t['status']) ?></span></div>
                            <div class="rounded-lg bg-gray-50 p-3 col-span-2"><p class="text-xs text-gray-500">Token</p><p class="font-mono text-xs break-all"><?= View::e($t['ticket_token']) ?></p></div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <a href="<?= View::e($ticketUrl) ?>" target="_blank" class="btn btn-outline btn-sm no-underline">Buka E-Ticket</a>
                            <button class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('<?= View::e($ticketUrl) ?>');showToast('Link disalin')">Bagikan</button>
                        </div>
                    </div>
                    <div class="bg-brand-500 h-2"></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.qr-code').forEach(function (el) {
            new QRCode(el, { text: el.dataset.url, width: 176, height: 176, correctLevel: QRCode.CorrectLevel.M });
        });
    });
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
