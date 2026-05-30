<?php ob_start(); ?>
<div class="mx-auto max-w-6xl px-4 py-6" x-data="seatMap()" x-init="init()">
    <!-- Hero -->
    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-2 mb-2">
            <span class="badge badge-info">Seat Map</span>
            <span class="badge badge-success">Published</span>
        </div>
        <h1 class="text-2xl font-bold sm:text-3xl"><?= View::e($event['title']) ?></h1>
        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-600">
            <span><?= View::formatDate($event['start_time']) ?></span>
            <span><?= View::e($event['venue_name']) ?></span>
        </div>
        <?php if (!empty($event['description'])): ?>
            <p class="mt-3 text-sm text-gray-500 max-w-2xl"><?= View::e($event['description']) ?></p>
        <?php endif; ?>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
        <!-- Seat Map -->
        <div class="card overflow-hidden">
            <div class="border-b px-4 py-3 flex items-center justify-between">
                <h2 class="font-semibold">Pilih Kursi</h2>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-emerald-400 inline-block"></span> Tersedia</span>
                    <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-red-400 inline-block"></span> Terjual</span>
                    <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-gray-300 inline-block"></span> Diblokir</span>
                </div>
            </div>
            <div class="p-4 flex justify-center overflow-auto">
                <!-- SVG Seat Map -->
                <div class="relative" style="min-width:320px">
                    <svg id="seatmap-svg" width="340" height="220" class="cursor-pointer select-none">
                        <?php
                        $rows = [];
                        $maxCol = 0;
                        foreach ($seats as $s) {
                            $rows[$s['row_label']] = true;
                            $maxCol = max($maxCol, $s['col_number']);
                        }
                        $rowLabels = array_keys($rows);
                        sort($rowLabels);
                        $seatW = 26; $seatH = 26; $gap = 3;
                        foreach ($seats as $s):
                            $x = ($s['col_number'] - 1) * ($seatW + $gap) + $gap + 25;
                            $y = (array_search($s['row_label'], $rowLabels)) * ($seatH + $gap) + $gap + 10;
                            $colors = [
                                'available' => 'fill-emerald-400 stroke-emerald-500',
                                'blocked'   => 'fill-gray-300 stroke-gray-400',
                                'sold'      => 'fill-red-400 stroke-red-500',
                            ];
                            $color = $colors[$s['status']] ?? 'fill-gray-200';
                            $clickable = $s['status'] === 'available' ? 'cursor-pointer' : 'cursor-not-allowed';
                        ?>
                            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $seatW ?>" height="<?= $seatH ?>" rx="3"
                                  class="<?= $color ?> transition-colors seat-rect <?= $clickable ?>"
                                  data-seat="<?= View::e($s['seat_label']) ?>"
                                  data-status="<?= $s['status'] ?>"
                                  data-price="<?= $s['price_cents'] ?>"
                                  onclick="seatMap().toggleSeat('<?= View::e($s['seat_label']) ?>', <?= $s['price_cents'] ?>, '<?= $s['status'] ?>')"
                            />
                            <text x="<?= $x + $seatW/2 ?>" y="<?= $y + $seatH/2 ?>" text-anchor="middle" dominant-baseline="central"
                                  class="text-[7px] font-medium pointer-events-none <?= $s['status'] === 'available' ? 'fill-gray-600' : 'fill-white' ?>">
                                <?= $s['col_number'] ?>
                            </text>
                        <?php endforeach; ?>
                    </svg>
                </div>
            </div>
            <div class="mx-4 mb-4 rounded-lg bg-gray-100 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                Panggung
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Ticket Types -->
            <div class="card p-4 sm:p-5">
                <h3 class="font-semibold">Tipe Tiket</h3>
                <div class="mt-3 space-y-2">
                    <?php foreach ($categories as $cat): ?>
                        <div class="flex items-center justify-between rounded-lg border bg-gray-50 px-3 py-2 text-sm">
                            <span class="font-medium"><?= View::e($cat['name']) ?></span>
                            <span class="text-brand-600 font-semibold"><?= View::formatRupiah($cat['price_cents']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Panel -->
            <div class="card p-4 sm:p-5" x-data>
                <h3 class="font-semibold">Pesanan Kamu</h3>

                <template x-if="selectedSeats.length === 0 && !holdActive">
                    <p class="mt-3 text-sm text-gray-500">Klik kursi yang tersedia di denah untuk memilih.</p>
                </template>

                <template x-if="selectedSeats.length > 0">
                    <div class="mt-3 space-y-3">
                        <template x-for="s in selectedSeats" :key="s.label">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600" x-text="'Kursi ' + s.label"></span>
                                <span class="font-medium" x-text="formatRupiah(s.price)"></span>
                            </div>
                        </template>
                        <div class="border-t pt-2 flex items-center justify-between font-semibold">
                            <span>Total</span>
                            <span class="text-brand-600" x-text="formatRupiah(totalPrice)"></span>
                        </div>

                        <template x-if="holdActive">
                            <div class="flex items-center justify-between rounded-lg bg-brand-50 px-3 py-2">
                                <span class="text-sm font-medium text-brand-700">Kursi dipesan</span>
                                <span class="text-xs font-mono font-bold text-brand-700" x-text="timerDisplay"></span>
                            </div>
                        </template>

                        <button x-show="!holdActive" class="btn btn-primary btn-lg w-full" @click="reserveSeats()" :disabled="loading">
                            Pesan Kursi
                        </button>

                        <template x-if="holdActive">
                            <div class="space-y-2">
                                <a href="checkout" class="btn btn-primary btn-lg w-full no-underline">Lanjut ke Checkout</a>
                                <button class="w-full text-center text-xs text-gray-500 hover:text-gray-700" @click="releaseHold()">
                                    Batalkan pesanan
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function seatMap() {
    return {
        selectedSeats: [],
        totalPrice: 0,
        holdActive: false,
        holdTimer: 300,
        timerDisplay: '05:00',
        loading: false,
        timerInterval: null,
        tenantSlug: '<?= View::e($tenant['slug'] ?? '') ?>',
        eventId: <?= (int)($event['id'] ?? 0) ?>,

        init() {
            window.seatMapInstance = this;
        },
        toggleSeat(label, price, status) {
            if (status !== 'available' || this.holdActive) return;
            const idx = this.selectedSeats.findIndex(s => s.label === label);
            if (idx >= 0) {
                this.selectedSeats.splice(idx, 1);
                this.totalPrice -= price;
                document.querySelectorAll('.seat-rect[data-seat="'+label+'"]').forEach(r => r.classList.remove('fill-brand-500','stroke-brand-600'));
            } else {
                this.selectedSeats.push({label, price});
                this.totalPrice += price;
                document.querySelectorAll('.seat-rect[data-seat="'+label+'"]').forEach(r => {r.classList.add('fill-brand-500','stroke-brand-600'); r.classList.remove('fill-emerald-400','stroke-emerald-500');});
            }
        },
        async reserveSeats() {
            if (this.selectedSeats.length === 0) return showToast('Pilih kursi terlebih dahulu', 'error');
            this.loading = true;
            try {
                const res = await fetch('/api/v1/seat-holds', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        event_id: this.eventId,
                        seats: this.selectedSeats.map(s => s.label),
                        expires_in_seconds: 300
                    })
                });
                const data = await res.json();
                if (res.status === 409) {
                    showToast('Beberapa kursi sudah diambil orang lain', 'error');
                    this.selectedSeats = [];
                    this.totalPrice = 0;
                    location.reload();
                    return;
                }
                if (data.seat_hold_id) {
                    this.holdActive = true;
                    this.startTimer(300);
                    showToast('Kursi berhasil dipesan! Selesaikan checkout dalam 5 menit.', 'success');
                }
            } catch(e) {
                showToast('Gagal memesan kursi', 'error');
            }
            this.loading = false;
        },
        startTimer(seconds) {
            this.holdTimer = seconds;
            this.updateTimerDisplay();
            this.timerInterval = setInterval(() => {
                this.holdTimer--;
                this.updateTimerDisplay();
                if (this.holdTimer <= 0) {
                    clearInterval(this.timerInterval);
                    this.holdActive = false;
                    this.selectedSeats = [];
                    this.totalPrice = 0;
                    showToast('Waktu pemesanan habis', 'error');
                    location.reload();
                }
            }, 1000);
        },
        updateTimerDisplay() {
            const m = Math.floor(this.holdTimer / 60);
            const s = this.holdTimer % 60;
            this.timerDisplay = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        },
        async releaseHold() {
            clearInterval(this.timerInterval);
            this.holdActive = false;
            this.selectedSeats = [];
            this.totalPrice = 0;
            location.reload();
        },
        formatRupiah(cents) {
            return 'Rp' + new Intl.NumberFormat('id-ID').format(cents);
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
