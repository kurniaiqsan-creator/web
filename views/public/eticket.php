<?php ob_start(); ?>
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card overflow-hidden">
            <div class="bg-brand-500 h-2"></div>
            <div class="p-5">
                <div class="text-center mb-4">
                    <h1 class="text-lg font-bold"><?= View::e($ticket['event_title']) ?></h1>
                    <div class="mt-2 flex items-center justify-center gap-3 text-xs text-gray-500">
                        <span><?= View::formatDate($ticket['start_time'] ?? $ticket['issued_at'] ?? '') ?></span>
                        <span><?= View::e($ticket['venue_name']) ?></span>
                    </div>
                </div>

                <div class="flex justify-center mb-4">
                    <div class="rounded-xl border bg-white p-3">
                        <div id="qr-code" data-url="<?= View::e(base_url('/t/' . $ticket['ticket_token'])) ?>"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                    <div class="rounded-lg bg-gray-50 p-3 text-center">
                        <p class="text-xs text-gray-500">Kursi</p>
                        <p class="font-bold text-lg"><?= View::e($ticket['seat_label']) ?></p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 text-center">
                        <p class="text-xs text-gray-500">Status</p>
                        <span class="badge badge-success mt-1"><?= $ticket['status'] === 'valid' ? 'Valid' : View::e($ticket['status']) ?></span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                    <span>Order: <?= View::e($ticket['order_code']) ?></span>
                    <span class="font-mono"><?= substr(View::e($ticket['ticket_token']), 0, 12) ?></span>
                </div>

                <div class="mt-4 flex gap-2">
                    <button class="btn btn-outline btn-sm w-full" onclick="showToast('PDF diunduh')">PDF</button>
                    <button class="btn btn-outline btn-sm w-full" onclick="navigator.clipboard.writeText(window.location.href);showToast('Link disalin')">Bagikan</button>
                </div>
            </div>
            <div class="bg-brand-500 h-2"></div>
        </div>
        <p class="mt-4 text-center text-xs text-gray-400">Tunjukkan QR code ini saat masuk venue</p>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('qr-code');
        if (el) new QRCode(el, { text: el.dataset.url, width: 176, height: 176, correctLevel: QRCode.CorrectLevel.M });
    });
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
