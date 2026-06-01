<?php ob_start(); ?>
<form method="post" id="eventForm" action="<?= base_url($event ? '/admin/events/' . $event['id'] : '/admin/events') ?>" x-data="eventEditor()" @submit="prepareSubmit">
    <input type="hidden" name="status" x-model="form.status">
    <input type="hidden" name="layout" x-model="layoutJson">
    <input type="hidden" name="ga_tiers" x-model="gaTiersJson">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= base_url('/admin/events') ?>" class="btn btn-outline-secondary btn-sm"><i class="cil-arrow-left me-1"></i></a>
            <div>
                <h1 class="fs-3 fw-bold mb-1"><?= $event ? 'Edit Event' : 'Buat Event Baru' ?></h1>
                <?php if ($event): ?>
                    <span class="badge bg-<?= $event['status']==='published'?'success':'secondary' ?>"><?= $event['status']==='published'?'Published':'Draft' ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" type="button" @click="submitWith('draft')"><i class="cil-save me-1"></i>Simpan Draft</button>
            <button class="btn btn-primary" type="button" @click="submitWith('published')"><i class="cil-send me-1"></i>Publish</button>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="tab==='info'?'active':''" @click="tab='info'"><i class="cil-info me-1"></i>Info Event</button>
        </li>
        <li class="nav-item" role="presentation" x-show="form.type === 'seat_map'">
            <button type="button" class="nav-link" :class="tab==='seatmap'?'active':''" @click="tab='seatmap'"><i class="cil-grid me-1"></i>Seat Map Designer</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="tab==='tickets'?'active':''" @click="tab='tickets'"><i class="cil-tag me-1"></i>Tiket & Harga</button>
        </li>
    </ul>

    <!-- Tab: Info -->
    <div x-show="tab==='info'" class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Informasi Dasar</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Event <span class="text-danger">*</span></label>
                            <input class="form-control" name="title" x-model="form.title" placeholder="Konser Akustik Malam Minggu" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Venue <span class="text-danger">*</span></label>
                            <select class="form-select" name="venue_id" x-model="form.venue_id" required>
                                <option value="">Pilih venue</option>
                                <?php foreach ($venues as $v): ?><option value="<?= $v['id'] ?>"><?= View::e($v['name']) ?></option><?php endforeach; ?>
                            </select>
                            <?php if (empty($venues)): ?><div class="form-text text-warning">Belum ada venue. <a href="<?= base_url('/admin/venues/create') ?>">Buat venue dulu</a>.</div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe</label>
                            <select class="form-select" name="type" x-model="form.type">
                                <option value="seat_map">Seat Map (Denah Kursi)</option>
                                <option value="general_admission">General Admission</option>
                            </select>
                        </div>
                        <div class="col-md-6" x-show="form.type === 'general_admission'">
                            <label class="form-label">Kapasitas</label>
                            <input type="number" min="0" class="form-control" name="capacity" x-model="form.capacity">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" x-model="form.description" rows="3" placeholder="Deskripsi event..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal & Jam Mulai <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="start_time" x-model="form.start_time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal & Jam Selesai</label>
                            <input type="datetime-local" class="form-control" name="end_time" x-model="form.end_time">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Seat Map Designer -->
    <div x-show="tab==='seatmap'">
        <!-- Toolbar -->
        <div class="card mb-3">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="addRow()"><i class="cil-plus me-1"></i>Tambah Baris</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="addCol()"><i class="cil-plus me-1"></i>Tambah Kolom</button>
                <div class="vr mx-1 d-none d-sm-block"></div>
                <span class="text-medium-emphasis small" x-show="selectedSeats.length > 0" x-text="selectedSeats.length + ' kursi terpilih'"></span>
                <div class="vr mx-1 d-none d-sm-block" x-show="selectedSeats.length > 0"></div>
                <button type="button" class="btn btn-sm btn-success" @click="bulkStatus('available')" x-show="selectedSeats.length > 0"><i class="cil-check me-1"></i>Buka</button>
                <button type="button" class="btn btn-sm btn-danger" @click="bulkStatus('blocked')" x-show="selectedSeats.length > 0"><i class="cil-ban me-1"></i>Blokir</button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Canvas -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body d-flex justify-content-center overflow-auto p-4">
                        <div class="position-relative select-none" :style="'width:' + canvasWidth + 'px;height:' + canvasHeight + 'px'">
                            <template x-for="s in layoutSeats" :key="s.label">
                                <div class="seat-cell d-flex align-items-center justify-content-center position-absolute"
                                     :style="seatStyle(s)"
                                     :title="s.label"
                                     @click="toggleSeat(s.label)"
                                     x-text="s.col"></div>
                            </template>
                        </div>
                    </div>
                    <div class="text-center py-2 bg-body-tertiary rounded-bottom text-uppercase small fw-semibold text-medium-emphasis">Panggung</div>
                </div>
            </div>

            <!-- Properties -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Properti</h5></div>
                    <div class="card-body">
                        <template x-if="selectedSeats.length === 1">
                            <div>
                                <div class="text-center bg-body-tertiary rounded p-3 mb-3">
                                    <div class="text-medium-emphasis small">Kursi</div>
                                    <div class="fs-3 fw-bold" x-text="selectedSeats[0]"></div>
                                </div>
                                <label class="form-label">Kategori</label>
                                <select class="form-select" x-model="bulkCategory" @change="applyCategory()">
                                    <option value="">Pilih...</option>
                                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?> — <?= View::formatRupiah($c['price_cents']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </template>
                        <template x-if="selectedSeats.length > 1">
                            <div>
                                <p class="fw-semibold" x-text="selectedSeats.length + ' kursi terpilih'"></p>
                                <label class="form-label">Ubah Kategori</label>
                                <select class="form-select mb-3" x-model="bulkCategory" @change="applyCategory()">
                                    <option value="">Pilih...</option>
                                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= View::e($c['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </template>
                        <p x-show="selectedSeats.length===0" class="text-medium-emphasis mb-0">Klik kursi pada denah untuk memilih. Gunakan toolbar untuk bulk edit.</p>
                    </div>
                </div>

                <!-- Legend kategori -->
                <div class="card mt-3">
                    <div class="card-header"><h5 class="card-title mb-0">Kategori</h5></div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            <template x-for="c in catLegend" :key="c.id">
                                <div class="d-flex align-items-center gap-2 small">
                                    <span class="seat-legend-dot" :style="'background:' + c.color"></span>
                                    <span class="fw-medium" x-text="c.name"></span>
                                </div>
                            </template>
                            <div class="d-flex align-items-center gap-2 small">
                                <span class="seat-legend-dot" style="background:var(--visi-seat-available)"></span>
                                <span class="text-medium-emphasis">Tanpa kategori</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 small">
                                <span class="seat-legend-dot blocked"></span>
                                <span class="text-medium-emphasis">Diblokir</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 small">
                                <span class="seat-legend-dot sold"></span>
                                <span class="text-medium-emphasis">Terjual</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 small">
                                <span class="seat-legend-dot selected"></span>
                                <span class="text-medium-emphasis">Dipilih</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="card mt-3">
                    <div class="card-header"><h5 class="card-title mb-0">Ringkasan</h5></div>
                    <div class="card-body">
                        <div class="row g-2 text-center small">
                            <div class="col-4"><div class="bg-body-tertiary rounded p-2"><div class="fw-bold" x-text="layoutSeats.filter(s=>s.status==='available').length"></div><div class="text-medium-emphasis">Tersedia</div></div></div>
                            <div class="col-4"><div class="bg-body-tertiary rounded p-2"><div class="fw-bold text-danger" x-text="layoutSeats.filter(s=>s.status==='sold').length"></div><div class="text-medium-emphasis">Terjual</div></div></div>
                            <div class="col-4"><div class="bg-body-tertiary rounded p-2"><div class="fw-bold" x-text="layoutSeats.length"></div><div class="text-medium-emphasis">Total</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Tickets -->
    <div x-show="tab==='tickets'">
        <!-- GA: kuota per kategori (hanya untuk General Admission) -->
        <div class="card mb-4" x-show="form.type === 'general_admission'">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="cil-people me-1"></i>Kuota Tiket (General Admission)</h5>
            </div>
            <div class="card-body">
                <p class="text-medium-emphasis small">Atur jumlah tiket yang dijual per kategori. Sistem menolak penjualan melebihi kuota (anti-overbook). Kuota tidak bisa diturunkan di bawah jumlah yang sudah terjual/ditahan.</p>
                <?php if (empty($categories)): ?>
                    <div class="alert alert-warning mb-0">Belum ada kategori tiket. <a href="<?= base_url('/admin/ticket-categories') ?>">Buat kategori dulu</a> untuk menetapkan kuota.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th style="width:140px">Harga</th>
                                    <th style="width:160px">Terjual / Ditahan</th>
                                    <th style="width:160px">Kuota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= View::e($c['name']) ?></td>
                                        <td><?= View::formatRupiah($c['price_cents']) ?></td>
                                        <td>
                                            <span class="text-medium-emphasis"
                                                  x-text="(gaTiers[<?= (int)$c['id'] ?>]?.sold || 0) + ' / ' + (gaTiers[<?= (int)$c['id'] ?>]?.held || 0)"></span>
                                        </td>
                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm"
                                                   :value="gaTiers[<?= (int)$c['id'] ?>]?.quota || 0"
                                                   @input="setQuota(<?= (int)$c['id'] ?>, $event.target.value)">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Kategori Tiket</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" @click="showCatForm = !showCatForm">
                    <i class="cil-plus me-1"></i>Tambah Kategori
                </button>
            </div>
            <div class="card-body">
                <!-- Inline create (AJAX, tidak reload halaman / tidak ganggu form event) -->
                <div class="border rounded p-3 mb-3 bg-body-tertiary" x-show="showCatForm" x-cloak>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Nama Kategori</label>
                            <input type="text" class="form-control form-control-sm" x-model="newCat.name" placeholder="mis. VIP" @keydown.enter.prevent="createCategory()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Harga (Rp)</label>
                            <input type="number" min="0" class="form-control form-control-sm" x-model="newCat.price" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Kuota</label>
                            <input type="number" min="0" class="form-control form-control-sm" x-model="newCat.quota" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary btn-sm w-100" @click="createCategory()" :disabled="catSaving">
                                <span x-text="catSaving ? '...' : 'Simpan'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <?php foreach ($categories as $c): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <div class="row g-2">
                                    <div class="col-4"><div class="text-medium-emphasis small">Nama</div><div class="fw-semibold"><?= View::e($c['name']) ?></div></div>
                                    <div class="col-4"><div class="text-medium-emphasis small">Harga</div><div class="fw-semibold"><?= View::formatRupiah($c['price_cents']) ?></div></div>
                                    <div class="col-4"><div class="text-medium-emphasis small">Kuota</div><div class="fw-semibold"><?= (int)$c['quota'] ?></div></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- Kategori baru hasil inline-create muncul di sini -->
                    <template x-for="c in addedCats" :key="c.id">
                        <div class="col-md-6">
                            <div class="border rounded p-3 border-primary">
                                <div class="row g-2">
                                    <div class="col-4"><div class="text-medium-emphasis small">Nama</div><div class="fw-semibold" x-text="c.name"></div></div>
                                    <div class="col-4"><div class="text-medium-emphasis small">Harga</div><div class="fw-semibold" x-text="'Rp' + c.price_cents.toLocaleString('id-ID')"></div></div>
                                    <div class="col-4"><div class="text-medium-emphasis small">Kuota</div><div class="fw-semibold" x-text="c.quota"></div></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="small text-medium-emphasis mt-3 mb-0">Untuk edit/hapus kategori, buka <a href="<?= base_url('/admin/ticket-categories') ?>">Tiket &amp; Harga</a>. Kategori baru bisa langsung dipakai setelah simpan event.</p>
            </div>
        </div>
    </div>
</form>

<script>
window.__EVENT_SEATS__ = <?= json_encode(array_map(function($s){
    $meta = json_decode($s['metadata'] ?? '{}', true) ?: [];
    return [
        'label'       => $s['seat_label'],
        'row'         => $s['row_label'],
        'col'         => (int)$s['col_number'],
        'category_id' => (int)($s['category_id'] ?? 0),
        'status'      => $s['status'] ?: 'available',
        'x'           => $meta['x'] ?? null,
        'y'           => $meta['y'] ?? null,
    ];
}, $seats), JSON_UNESCAPED_UNICODE) ?>;

window.__EVENT_INVENTORY__ = <?= json_encode(array_map(function($inv){
    return [
        'category_id' => (int)$inv['category_id'],
        'quota'       => (int)$inv['quota'],
        'sold'        => (int)$inv['sold'],
        'held'        => (int)$inv['held'],
    ];
}, $inventory ?? []), JSON_UNESCAPED_UNICODE) ?>;

window.__EVENT_CATEGORIES__ = <?= json_encode(array_map(function($c){
    return ['id' => (int)$c['id'], 'name' => $c['name']];
}, $categories), JSON_UNESCAPED_UNICODE) ?>;

// Palet warna kategori (dipakai untuk membedakan kursi per kategori di denah).
window.__CAT_PALETTE__ = ['#6366f1','#ec4899','#0ea5e9','#f59e0b','#14b8a6','#a855f7','#84cc16','#ef4444','#06b6d4','#eab308'];

function eventEditor() {
    return {
        tab: 'info',
        form: {
            title: <?= json_encode($event['title'] ?? '') ?>,
            description: <?= json_encode($event['description'] ?? '') ?>,
            venue_id: <?= json_encode((string)($event['venue_id'] ?? '')) ?>,
            type: <?= json_encode($event ? ($event['settings']['type'] ?? 'seat_map') : 'seat_map') ?>,
            capacity: <?= json_encode((int)($event['settings']['capacity'] ?? 0)) ?>,
            start_time: <?= json_encode($event ? str_replace(' ', 'T', substr($event['start_time'] ?? '', 0, 16)) : '') ?>,
            end_time: <?= json_encode($event ? str_replace(' ', 'T', substr($event['end_time'] ?? '', 0, 16)) : '') ?>,
            status: <?= json_encode($event['status'] ?? 'draft') ?>,
        },
        layoutSeats: [], selectedSeats: [], bulkCategory: '', canvasWidth: 340, canvasHeight: 220,
        layoutJson: '[]',
        showCatForm: false,
        newCat: { name: '', price: '', quota: '' },
        catSaving: false,
        addedCats: [],
        gaTiers: {},      // { [categoryId]: {quota, sold, held} }
        gaTiersJson: '[]',
        catColors: {},    // { [categoryId]: '#hex' }
        catNames: {},     // { [categoryId]: 'VIP' }

        init() {
            // Warna per kategori (untuk membedakan kursi di denah).
            const palette = window.__CAT_PALETTE__ || [];
            const cats = window.__EVENT_CATEGORIES__ || [];
            cats.forEach((c, i) => {
                this.catColors[c.id] = palette[i % palette.length];
                this.catNames[c.id] = c.name;
            });
            // GA tiers dari server (edit mode).
            const inv = window.__EVENT_INVENTORY__ || [];
            const tiers = {};
            inv.forEach(r => { tiers[r.category_id] = {quota: r.quota, sold: r.sold, held: r.held}; });
            this.gaTiers = tiers;
            // GA tidak punya seat map → paksa tab ke info kalau kebetulan di seatmap.
            this.$watch('form.type', (v) => { if (v === 'general_admission' && this.tab === 'seatmap') this.tab = 'info'; });

            const seatW = 28, gap = 8;
            const existing = window.__EVENT_SEATS__ || [];
            if (existing.length > 0) {
                // Edit mode: pakai layout existing. (Posisi selalu dihitung ulang
                // dari row/col agar grid rapi, abaikan x/y lama yang mungkin tak konsisten.)
                this.layoutSeats = existing.map(s => ({
                    label: s.label, row: s.row, col: s.col,
                    x: (s.col-1)*(seatW+gap)+gap,
                    y: ('ABCDEFGHIJKLMNOPQRSTUVWXYZ'.indexOf(s.row))*(seatW+gap)+gap,
                    category_id: s.category_id || 0,
                    status: s.status || 'available',
                    selected: false
                }));
            } else {
                // Create mode: default grid 6x10.
                const seats = [];
                const rows = ['A','B','C','D','E','F'];
                rows.forEach((row, ri) => {
                    for (let col = 1; col <= 10; col++) {
                        seats.push({
                            label: row+'-'+col, row, col,
                            x: (col-1)*(seatW+gap)+gap,
                            y: ri*(seatW+gap)+gap,
                            category_id: 0,
                            status: 'available',
                            selected: false
                        });
                    }
                });
                this.layoutSeats = seats;
            }
            const maxCol = this.layoutSeats.reduce((m,s)=>Math.max(m,s.col),0);
            const rowCount = new Set(this.layoutSeats.map(s=>s.row)).size;
            this.canvasWidth  = maxCol  *(seatW+gap)+gap;
            this.canvasHeight = rowCount*(seatW+gap)+gap;
        },
        toggleSeat(label) {
            const s = this.layoutSeats.find(s => s.label === label);
            if (!s || s.status !== 'available') return;
            s.selected = !s.selected;
            this.selectedSeats = s.selected ? [...this.selectedSeats, label] : this.selectedSeats.filter(l => l !== label);
        },
        catColor(categoryId) {
            return this.catColors[categoryId] || null;
        },
        seatStyle(s) {
            // Posisi absolut + warna sesuai status/kategori.
            let bg, border;
            if (s.selected) {
                bg = 'var(--visi-seat-selected)'; border = 'var(--visi-seat-selected-stroke)';
            } else if (s.status === 'sold') {
                bg = 'var(--visi-seat-sold)'; border = 'var(--visi-seat-sold-stroke)';
            } else if (s.status === 'blocked') {
                bg = 'var(--visi-seat-blocked)'; border = 'var(--visi-seat-blocked-stroke)';
            } else {
                const cc = this.catColor(s.category_id);
                bg = cc || 'var(--visi-seat-available)';
                border = cc || 'var(--visi-seat-available-stroke)';
            }
            return `position:absolute;left:${s.x}px;top:${s.y}px;width:28px;height:28px;background:${bg};border-color:${border}`;
        },
        get catLegend() {
            return Object.keys(this.catNames).map(id => ({
                id: parseInt(id),
                name: this.catNames[id],
                color: this.catColors[id] || 'var(--visi-seat-available)'
            }));
        },
        addRow() {
            const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const nextIdx = [...new Set(this.layoutSeats.map(s=>s.row))].length;
            if (nextIdx >= 26) return showToast('Maksimal 26 baris', 'error');
            const next = letters[nextIdx];
            const seatW=28,gap=8,y=nextIdx*(seatW+gap)+gap;
            for(let col=1;col<=10;col++) {
                this.layoutSeats.push({label:next+'-'+col,row:next,col,x:(col-1)*(seatW+gap)+gap,y,category_id:0,status:'available',selected:false});
            }
            this.canvasHeight += (seatW+gap);
            showToast('Baris '+next+' ditambahkan');
        },
        addCol() {
            const maxCol = Math.max(...this.layoutSeats.map(s=>s.col));
            const rows = [...new Set(this.layoutSeats.map(s=>s.row))];
            const seatW=28,gap=8;
            rows.forEach((row,ri)=>{
                this.layoutSeats.push({label:row+'-'+(maxCol+1),row,col:maxCol+1,x:maxCol*(seatW+gap)+gap,y:ri*(seatW+gap)+gap,category_id:0,status:'available',selected:false});
            });
            this.canvasWidth += (seatW+gap);
            showToast('Kolom '+(maxCol+1)+' ditambahkan');
        },
        applyCategory() {
            if (!this.bulkCategory) return;
            this.layoutSeats.forEach(s => { if (this.selectedSeats.includes(s.label)) s.category_id = parseInt(this.bulkCategory); });
            showToast('Kategori diubah');
        },
        async createCategory() {
            const name = (this.newCat.name || '').trim();
            if (!name) return showToast('Nama kategori wajib diisi', 'warning');
            this.catSaving = true;
            try {
                const res = await fetch(base_url('/admin/ticket-categories/quick'), {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        name: name,
                        price_cents: parseInt(this.newCat.price) || 0,
                        quota: parseInt(this.newCat.quota) || 0,
                    })
                });
                const data = await res.json();
                if (!res.ok) { showToast(data.error || 'Gagal membuat kategori', 'error'); this.catSaving = false; return; }
                this.addedCats.push(data);
                // Tambahkan ke dropdown kategori (seat-map) & tier GA secara live.
                this.gaTiers[data.id] = { quota: data.quota, sold: 0, held: 0 };
                // Beri warna untuk kategori baru agar tampil beda di denah.
                const palette = window.__CAT_PALETTE__ || [];
                const usedCount = Object.keys(this.catColors).length;
                this.catColors[data.id] = palette[usedCount % palette.length];
                this.catNames[data.id] = data.name;
                this.newCat = { name: '', price: '', quota: '' };
                this.showCatForm = false;
                showToast('Kategori "' + data.name + '" dibuat', 'success');
            } catch (e) {
                showToast('Gagal membuat kategori', 'error');
            }
            this.catSaving = false;
        },
        bulkStatus(status) {
            this.layoutSeats.forEach(s => { if (this.selectedSeats.includes(s.label) && s.status !== 'sold') s.status = status; });
            this.selectedSeats = []; this.layoutSeats.forEach(s => s.selected = false);
            showToast('Status diubah');
        },
        setQuota(categoryId, value) {
            const q = Math.max(0, parseInt(value) || 0);
            const cur = this.gaTiers[categoryId] || {quota: 0, sold: 0, held: 0};
            this.gaTiers[categoryId] = {...cur, quota: q};
        },
        serializeGaTiers() {
            // Kirim hanya kategori dengan kuota > 0 (atau yang sudah punya penjualan).
            return JSON.stringify(
                Object.entries(this.gaTiers)
                    .filter(([id, t]) => (t.quota || 0) > 0 || (t.sold || 0) > 0 || (t.held || 0) > 0)
                    .map(([id, t]) => ({category_id: parseInt(id), quota: t.quota || 0}))
            );
        },
        submitWith(status) {
            if (!this.form.title) { showToast('Nama event wajib diisi', 'error'); return; }
            if (!this.form.venue_id) { showToast('Venue wajib dipilih', 'error'); return; }
            if (!this.form.start_time) { showToast('Tanggal mulai wajib diisi', 'error'); return; }
            this.form.status = status;
            // Serialize seats untuk dikirim sebagai 1 field JSON.
            this.layoutJson = JSON.stringify(
                this.layoutSeats.map(s => ({
                    label: s.label, row: s.row, col: s.col,
                    category_id: s.category_id, status: s.status,
                    x: s.x, y: s.y,
                }))
            );
            this.gaTiersJson = this.serializeGaTiers();
            // Tunggu Alpine flush model ke hidden input, baru submit.
            this.$nextTick(() => document.getElementById('eventForm').submit());
        },

        prepareSubmit() {
            // Fallback kalau user tekan Enter di field; tetap serialize layout.
            this.layoutJson = JSON.stringify(
                this.layoutSeats.map(s => ({
                    label: s.label, row: s.row, col: s.col,
                    category_id: s.category_id, status: s.status,
                    x: s.x, y: s.y,
                }))
            );
            this.gaTiersJson = this.serializeGaTiers();
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
