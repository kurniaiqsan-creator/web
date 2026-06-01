<?php ob_start(); ?>
<?php $isGa = (($event['settings']['type'] ?? 'seat_map') === 'general_admission'); ?>
<?php if ($isGa): ?>
<!-- ===== GENERAL ADMISSION (tanpa kursi) ===== -->
<div class="container-lg py-4" x-data="gaPicker()" x-init="init()">
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="badge text-bg-warning">General Admission</span>
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

    <?php if (!empty($activePromos)): ?>
        <div class="visi-promo d-flex flex-wrap align-items-center gap-2 mb-4">
            <i class="cil-gift fs-5 visi-promo-title"></i>
            <span class="visi-promo-title me-2">Promo aktif:</span>
            <?php foreach ($activePromos as $p):
                $label = $p['type'] === 'percentage'
                    ? ('Diskon ' . rtrim(rtrim(number_format((float)$p['value'], 2, '.', ''), '0'), '.') . '%')
                    : ('Potongan ' . View::formatRupiah((int)round((float)$p['value'])));
            ?>
                <span class="badge visi-promo-code me-1" title="Berlaku s/d <?= View::e(date('d M Y', strtotime($p['valid_to']))) ?>">
                    <span class="font-monospace"><?= View::e($p['code']) ?></span> · <?= View::e($label) ?>
                </span>
            <?php endforeach; ?>
            <span class="small visi-promo-hint w-100 mt-1">Masukkan kode promo saat checkout.</span>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h2 class="h6 fw-semibold mb-0">Pilih Tiket</h2></div>
                <div class="card-body">
                    <?php if (empty($gaTiers)): ?>
                        <p class="text-medium-emphasis mb-0">Belum ada tiket yang dijual untuk event ini.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($gaTiers as $t): $cid = (int)$t['category_id']; $avail = (int)$t['available']; ?>
                                <div class="d-flex justify-content-between align-items-center rounded-3 border p-3">
                                    <div>
                                        <div class="fw-semibold"><?= View::e($t['name']) ?></div>
                                        <div class="text-primary fw-semibold small"><?= View::formatRupiah($t['price_cents']) ?></div>
                                        <?php if ($avail <= 0): ?>
                                            <div class="small text-danger mt-1"><i class="cil-x-circle me-1"></i>Habis</div>
                                        <?php elseif ($avail <= 20): ?>
                                            <div class="small text-warning mt-1">Tersisa <?= $avail ?> tiket</div>
                                        <?php else: ?>
                                            <div class="small text-medium-emphasis mt-1">Tersedia</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="dec(<?= $cid ?>)" :disabled="(qty[<?= $cid ?>]||0) <= 0">−</button>
                                        <span class="fw-semibold" style="min-width:1.5rem;text-align:center" x-text="qty[<?= $cid ?>]||0"></span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="inc(<?= $cid ?>, <?= $avail ?>)" :disabled="(qty[<?= $cid ?>]||0) >= <?= $avail ?>">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 fw-semibold mb-3">Pesanan Kamu</h3>
                    <template x-if="totalQty === 0">
                        <p class="small text-medium-emphasis mb-0">Pilih jumlah tiket di sebelah kiri.</p>
                    </template>
                    <template x-if="totalQty > 0">
                        <div>
                            <template x-for="line in lines" :key="line.categoryId">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-medium-emphasis" x-text="line.qty + '× ' + line.category"></span>
                                    <span class="fw-medium" x-text="formatRupiah(line.qty * line.price)"></span>
                                </div>
                            </template>
                            <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center fw-semibold">
                                <span>Total</span>
                                <span class="text-primary" x-text="formatRupiah(totalPrice)"></span>
                            </div>
                            <div class="d-grid mt-3">
                                <button class="btn btn-primary btn-lg" @click="goToCheckout()">Lanjut ke Checkout</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function gaPicker() {
    return {
        qty: {},
        meta: <?= json_encode(array_reduce($gaTiers, function($acc, $t){
            $acc[(int)$t['category_id']] = ['price' => (int)$t['price_cents'], 'category' => $t['name']];
            return $acc;
        }, []), JSON_UNESCAPED_UNICODE) ?: '{}' ?>,
        eventId: <?= (int)($event['id'] ?? 0) ?>,
        eventTitle: '<?= View::e($event['title'] ?? '') ?>',
        venueName: '<?= View::e($event['venue_name'] ?? '') ?>',
        eventDate: '<?= View::e(View::formatDate($event['start_time'] ?? 'now')) ?>',

        init() {},
        inc(cid, avail) {
            const cur = this.qty[cid] || 0;
            if (cur >= avail) return showToast('Stok tiket tidak mencukupi', 'error');
            this.qty[cid] = cur + 1;
        },
        dec(cid) {
            const cur = this.qty[cid] || 0;
            if (cur <= 0) return;
            this.qty[cid] = cur - 1;
        },
        get lines() {
            return Object.entries(this.qty)
                .filter(([cid, q]) => q > 0)
                .map(([cid, q]) => ({
                    categoryId: parseInt(cid),
                    qty: q,
                    price: this.meta[cid]?.price || 0,
                    category: this.meta[cid]?.category || '',
                }));
        },
        get totalQty() { return this.lines.reduce((a, l) => a + l.qty, 0); },
        get totalPrice() { return this.lines.reduce((a, l) => a + l.qty * l.price, 0); },
        goToCheckout() {
            if (this.totalQty === 0) return showToast('Pilih minimal 1 tiket', 'error');
            // Ekspansi qty → daftar item (1 baris per tiket) agar kompatibel dengan
            // format cart seat-map yang sudah dipakai checkout.
            const seats = [];
            this.lines.forEach(l => {
                for (let i = 0; i < l.qty; i++) {
                    seats.push({label: '', price: l.price, category: l.category, categoryId: l.categoryId});
                }
            });
            const cart = {
                eventId: this.eventId,
                eventTitle: this.eventTitle,
                venueName: this.venueName,
                eventDate: this.eventDate,
                seatHoldId: null,
                seats,
                totalPrice: this.totalPrice,
                ga: true,
            };
            sessionStorage.setItem('visi_cart', JSON.stringify(cart));
            window.location.href = base_url('/events/' + this.eventId + '/checkout');
        },
        formatRupiah(cents) {
            return 'Rp' + new Intl.NumberFormat('id-ID').format(cents);
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; return; ?>
<?php endif; ?>
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

    <?php if (!empty($activePromos)): ?>
        <div class="visi-promo d-flex flex-wrap align-items-center gap-2 mb-4">
            <i class="cil-gift fs-5 visi-promo-title"></i>
            <span class="visi-promo-title me-2">Promo aktif:</span>
            <?php foreach ($activePromos as $p):
                $label = $p['type'] === 'percentage'
                    ? ('Diskon ' . rtrim(rtrim(number_format((float)$p['value'], 2, '.', ''), '0'), '.') . '%')
                    : ('Potongan ' . View::formatRupiah((int)round((float)$p['value'])));
            ?>
                <span class="badge visi-promo-code me-1" title="Berlaku s/d <?= View::e(date('d M Y', strtotime($p['valid_to']))) ?>">
                    <span class="font-monospace"><?= View::e($p['code']) ?></span> · <?= View::e($label) ?>
                </span>
            <?php endforeach; ?>
            <span class="small visi-promo-hint w-100 mt-1">Masukkan kode promo saat checkout.</span>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <?php $availCount = count(array_filter($seats, fn($s) => ($s['status'] ?? '') === 'available')); ?>
                    <h2 class="h6 fw-semibold mb-0">
                        Pilih Kursi
                        <?php if ($availCount > 0): ?>
                            <span class="badge bg-success-subtle text-success-emphasis ms-1">Tersisa <?= $availCount ?> kursi</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger-emphasis ms-1">Habis</span>
                        <?php endif; ?>
                    </h2>
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
                eventId: this.eventId,
                eventTitle: this.eventTitle,
                venueName: this.venueName,
                eventDate: this.eventDate,
                seatHoldId: this.holdId,
                seats: this.selectedSeats,
                totalPrice: this.totalPrice,
            };
            sessionStorage.setItem('visi_cart', JSON.stringify(cart));
            window.location.href = base_url('/events/' + this.eventId + '/checkout');
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
