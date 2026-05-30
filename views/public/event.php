<?php ob_start(); ?>
<div class="container-lg py-4" x-data="seatMap()" x-init="init()">

    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="badge text-bg-info">Seat Map</span>
            <span class="badge text-bg-success">Published</span>
        </div>
        <h1 class="h3 fw-bold mb-2"><?= View::e($event['title']) ?></h1>
        <div class="d-flex flex-wrap gap-3 small text-medium-emphasis">
            <span><i class="cil-calendar me-1"></i><?= View::formatDate($event['start_time']) ?></span>
            <span><i class="cil-location-pin me-1"></i><?= View::e($event['venue_name']) ?></span>
        </div>
        <?php if (!empty($event['description'])): ?>
            <p class="mt-3 text-medium-emphasis" style="max-width:48rem"><?= View::e($event['description']) ?></p>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="h6 fw-semibold mb-0">Pilih Kursi</h2>
                    <div class="d-flex gap-3 small text-medium-emphasis">
                        <span><span class="seat-legend-dot available"></span> Tersedia</span>
                        <span><span class="seat-legend-dot sold"></span> Terjual</span>
                        <span><span class="seat-legend-dot blocked"></span> Diblokir</span>
                        <span><span class="seat-legend-dot selected"></span> Dipilih</span>
                    </div>
                </div>
                <div class="card-body d-flex justify-content-center overflow-auto">
                    <div style="min-width:320px">
                        <svg id="seatmap-svg" width="340" height="220" class="user-select-none">
                            <?php
                            $rows = [];
                            $maxCol = 0;
                            foreach ($seats as $s) {
                                $rows[$s['row_label']] = true;
                                $maxCol = max($maxCol, $s['col_number']);
                            }
                            $rowLabels = array_keys($rows);
                            sort($rowLabels);
                            $catNames = [];
                            foreach ($categories as $cat) {
                                $catNames[(int)$cat['id']] = $cat['name'];
                            }
                            $seatW = 26; $seatH = 26; $gap = 3;
                            foreach ($seats as $s):
                                $catName = $catNames[(int)($s['category_id'] ?? 0)] ?? '';
                                $x = ($s['col_number'] - 1) * ($seatW + $gap) + $gap + 25;
                                $y = (array_search($s['row_label'], $rowLabels)) * ($seatH + $gap) + $gap + 10;
                                $statusClass = 'seat-' . ($s['status'] ?? 'blocked');
                            ?>
                                <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $seatW ?>" height="<?= $seatH ?>" rx="3"
                                      class="seat-rect <?= $statusClass ?>"
                                      data-seat="<?= View::e($s['seat_label']) ?>"
                                      data-status="<?= $s['status'] ?>"
                                      data-price="<?= $s['price_cents'] ?>"
                                      data-category="<?= View::e($catName) ?>"
                                      onclick="window.seatMapInstance && window.seatMapInstance.toggleSeat('<?= View::e($s['seat_label']) ?>', <?= $s['price_cents'] ?>, '<?= $s['status'] ?>', '<?= View::e($catName) ?>', <?= (int)($s['category_id'] ?? 0) ?>)"
                                />
                                <text x="<?= $x + $seatW/2 ?>" y="<?= $y + $seatH/2 ?>" text-anchor="middle" dominant-baseline="central"
                                      class="pointer-events-none" style="font-size:7px;font-weight:500;fill:#fff">
                                    <?= $s['col_number'] ?>
                                </text>
                            <?php endforeach; ?>
                        </svg>
                    </div>
                </div>
                <div class="card-footer text-center small text-uppercase text-medium-emphasis fw-semibold" style="letter-spacing:.08em">
                    Panggung
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="h6 fw-semibold mb-3">Tipe Tiket</h3>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($categories as $cat): ?>
                            <div class="d-flex justify-content-between align-items-center rounded-3 border bg-body-tertiary px-3 py-2 small">
                                <span class="fw-medium"><?= View::e($cat['name']) ?></span>
                                <span class="text-primary fw-semibold"><?= View::formatRupiah($cat['price_cents']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 fw-semibold mb-3">Pesanan Kamu</h3>

                    <template x-if="selectedSeats.length === 0 && !holdActive">
                        <p class="small text-medium-emphasis mb-0">Klik kursi yang tersedia di denah untuk memilih.</p>
                    </template>

                    <template x-if="selectedSeats.length > 0">
                        <div>
                            <template x-for="s in selectedSeats" :key="s.label">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-medium-emphasis" x-text="'Kursi ' + s.label"></span>
                                    <span class="fw-medium" x-text="formatRupiah(s.price)"></span>
                                </div>
                            </template>
                            <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center fw-semibold">
                                <span>Total</span>
                                <span class="text-primary" x-text="formatRupiah(totalPrice)"></span>
                            </div>

                            <template x-if="holdActive">
                                <div class="alert alert-primary py-2 mt-3 mb-0 d-flex justify-content-between align-items-center">
                                    <span class="small fw-medium">Kursi dipesan</span>
                                    <span class="font-monospace fw-bold" x-text="timerDisplay"></span>
                                </div>
                            </template>

                            <div class="d-grid gap-2 mt-3">
                                <button x-show="!holdActive" class="btn btn-primary btn-lg" @click="reserveSeats()" :disabled="loading">
                                    Pesan Kursi
                                </button>
                                <template x-if="holdActive">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-lg" @click="goToCheckout()">Lanjut ke Checkout</button>
                                        <button class="btn btn-link btn-sm text-medium-emphasis" @click="releaseHold()">
                                            Batalkan pesanan
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
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
        holdId: null,
        tenantSlug: '<?= View::e($tenant['slug'] ?? '') ?>',
        eventId: <?= (int)($event['id'] ?? 0) ?>,
        eventTitle: '<?= View::e($event['title'] ?? '') ?>',
        venueName: '<?= View::e($event['venue_name'] ?? '') ?>',
        eventDate: '<?= View::e(View::formatDate($event['start_time'] ?? 'now')) ?>',

        init() {
            window.seatMapInstance = this;
        },
        toggleSeat(label, price, status, category, categoryId) {
            if (status !== 'available' || this.holdActive) return;
            const idx = this.selectedSeats.findIndex(s => s.label === label);
            if (idx >= 0) {
                this.selectedSeats.splice(idx, 1);
                this.totalPrice -= price;
                document.querySelectorAll('.seat-rect[data-seat="'+label+'"]').forEach(r => {
                    r.classList.remove('seat-selected');
                    r.classList.add('seat-available');
                });
            } else {
                this.selectedSeats.push({label, price, category: category || '', categoryId: categoryId || null});
                this.totalPrice += price;
                document.querySelectorAll('.seat-rect[data-seat="'+label+'"]').forEach(r => {
                    r.classList.add('seat-selected');
                    r.classList.remove('seat-available');
                });
            }
        },
        goToCheckout() {
            const cart = {
                tenantSlug: this.tenantSlug,
                eventId: this.eventId,
                eventTitle: this.eventTitle,
                venueName: this.venueName,
                eventDate: this.eventDate,
                seatHoldId: this.holdId,
                seats: this.selectedSeats,
                totalPrice: this.totalPrice,
            };
            sessionStorage.setItem('visi_cart', JSON.stringify(cart));
            window.location.href = base_url('/' + this.tenantSlug + '/events/' + this.eventId + '/checkout');
        },
        async reserveSeats() {
            if (this.selectedSeats.length === 0) return showToast('Pilih kursi terlebih dahulu', 'error');
            this.loading = true;
            try {
                const res = await fetch(base_url('/api/v1/seat-holds'), {
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
                    this.holdId = data.seat_hold_id;
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
