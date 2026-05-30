<?php ob_start(); ?>
<div class="mx-auto max-w-2xl px-4 py-6" x-data="{activeTicket:0}">
    <a href="../" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">&larr; Kembali ke event</a>

    <div class="card p-4 sm:p-5 text-center mb-6">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 mb-4">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <h1 class="text-xl font-bold">Pembayaran Berhasil!</h1>
        <p class="mt-1 text-sm text-gray-500">Order <span class="font-mono font-semibold"><?= View::e($orderCode) ?></span></p>
        <div class="mt-4 flex items-center justify-center gap-3">
            <span class="badge badge-success">Lunas</span>
        </div>
    </div>

    <h2 class="font-semibold flex items-center gap-2 mb-4">E-Ticket (2)</h2>

    <div class="flex gap-2 mb-4">
        <button class="rounded-lg border px-4 py-2 text-sm font-medium" :class="activeTicket===0?'border-brand-500 bg-brand-50 text-brand-700':'border-gray-200 bg-white'" @click="activeTicket=0">Kursi A-1</button>
        <button class="rounded-lg border px-4 py-2 text-sm font-medium" :class="activeTicket===1?'border-brand-500 bg-brand-50 text-brand-700':'border-gray-200 bg-white'" @click="activeTicket=1">Kursi A-2</button>
    </div>

    <div class="card overflow-hidden">
        <div class="bg-brand-500 h-2"></div>
        <div class="p-5 sm:p-6">
            <div class="text-center mb-4">
                <h3 class="text-lg font-bold">Konser Akustik Malam Minggu</h3>
                <div class="mt-2 flex items-center justify-center gap-4 text-sm text-gray-500">
                    <span>15 Juni 2026, 19:00</span>
                    <span>Studio Kecil Kemang</span>
                </div>
            </div>

            <div class="flex justify-center mb-4">
                <div class="flex h-48 w-48 items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white">
                    <div class="text-center text-gray-400">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zm0 9.75c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zm9.75-9.75c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/></svg>
                        <p class="mt-1 text-[10px] font-mono">QR Code</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Kursi</p><p class="font-semibold" x-text="activeTicket===0?'A-1':'A-2'"></p></div>
                <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Status</p><span class="badge badge-success">Valid</span></div>
                <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Order</p><p class="font-semibold"><?= View::e($orderCode) ?></p></div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <button class="btn btn-outline btn-sm" onclick="showToast('Tiket diunduh (simulasi)')">PDF</button>
                <button class="btn btn-outline btn-sm" onclick="showToast('Link disalin')">Bagikan</button>
                <button class="btn btn-outline btn-sm" onclick="showToast('Email dikirim ulang')">Kirim Ulang</button>
            </div>
        </div>
        <div class="bg-brand-500 h-2"></div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
