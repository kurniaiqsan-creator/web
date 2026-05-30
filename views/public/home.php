<?php ob_start(); ?>
<div class="px-4 py-16 sm:py-24">
    <div class="mx-auto max-w-3xl text-center">
        <span class="badge badge-success mb-4">Phase 1 — Multi‑tenant tiket platform</span>
        <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
            Jual tiket event kamu <span class="text-brand-600">tanpa ribet</span>
        </h1>
        <p class="mt-6 text-lg leading-8 text-gray-600">
            Platform tiket B2B2C multi‑tenant. Setup 5 menit, mobile‑friendly, dukungan seat booking, e‑ticket QR, dan integrasi pembayaran lokal.
        </p>
        <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
            <a href="/onboarding" class="btn btn-primary btn-lg min-w-[200px]">Mulai Sekarang</a>
            <a href="/acoustic-nights" class="btn btn-outline btn-lg">Lihat Demo</a>
        </div>
    </div>
</div>

<section class="border-t bg-gray-50 px-4 py-16 sm:py-24">
    <div class="mx-auto max-w-6xl">
        <h2 class="text-center text-2xl font-bold sm:text-3xl">Semua yang kamu butuhkan untuk jual tiket</h2>
        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ([
                ['Multi‑tenant', 'Satu platform, banyak penyelenggara. Branding & data terisolasi per tenant.', 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                ['Seat Booking', 'Seat map interaktif, real‑time hold, dan anti double‑booking.', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['E‑Ticket QR', 'Tiket digital dengan QR code unik, validasi on‑site real‑time.', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zm0 9.75c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zm9.75-9.75c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z'],
                ['Pembayaran Lokal', 'Integrasi Midtrans, Xendit, DOKU — VA, e‑wallet, kartu.', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
            ] as [$featTitle, $featDesc, $featIcon]): ?>
                <div class="card p-6 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100">
                        <svg class="h-6 w-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $featIcon ?>"/></svg>
                    </div>
                    <h3 class="mt-4 font-semibold"><?= $featTitle ?></h3>
                    <p class="mt-2 text-sm text-gray-500"><?= $featDesc ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
