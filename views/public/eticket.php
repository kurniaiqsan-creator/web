<?php ob_start(); ?>
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card overflow-hidden">
            <div class="bg-brand-500 h-2"></div>
            <div class="p-5">
                <div class="text-center mb-4">
                    <h1 class="text-lg font-bold"><?= View::e($ticket['event_title']) ?></h1>
                    <div class="mt-2 flex items-center justify-center gap-3 text-xs text-gray-500">
                        <span><?= View::formatDate($ticket['issued_at'] ?? '') ?></span>
                        <span><?= View::e($ticket['venue_name']) ?></span>
                    </div>
                </div>

                <div class="flex justify-center mb-4">
                    <div class="flex h-44 w-44 items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white">
                        <div class="text-center text-gray-400">
                            <svg class="mx-auto h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zm0 9.75c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zm9.75-9.75c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                            <p class="mt-1 text-[10px] font-mono"><?= substr(View::e($ticket['ticket_token']), 0, 12) ?>...</p>
                        </div>
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
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
