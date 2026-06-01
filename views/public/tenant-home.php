<?php
// $cards disiapkan controller (decorateEvents). $cats untuk daftar kategori sidebar.
$cats = View::eventCategories();
?>
<?php ob_start(); ?>
<div class="container-lg py-4 py-md-5" x-data="eventsExplorer()" x-cloak>
    <h1 class="h3 fw-bold mb-4">Jelajahi Semua Event</h1>

    <div class="row g-4">
        <!-- Sidebar filter -->
        <div class="col-lg-3">
            <div class="card visi-filter-sidebar">
                <div class="card-body">
                    <div class="fw-bold mb-3">Filter</div>
                    <div class="input-group input-group-sm mb-4">
                        <span class="input-group-text bg-body"><i class="cil-search"></i></span>
                        <input type="search" class="form-control" placeholder="Cari event..." x-model="search" aria-label="Cari event">
                    </div>

                    <div class="text-uppercase small fw-bold text-medium-emphasis mb-2" style="letter-spacing:.04em">Kategori</div>
                    <div class="d-flex flex-column gap-1 mb-4">
                        <button type="button" class="btn btn-sm text-start visi-side-item" :class="cat==='' ? 'active' : ''" @click="cat=''">🌐 Semua</button>
                        <?php foreach ($cats as $catKey => $catMeta): if ($catKey === 'lainnya') continue; ?>
                            <button type="button" class="btn btn-sm text-start visi-side-item"
                                    :class="cat==='<?= View::e($catKey) ?>' ? 'active' : ''"
                                    @click="cat='<?= View::e($catKey) ?>'">
                                <?= View::e($catMeta['emoji'] . ' ' . $catMeta['label']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-uppercase small fw-bold text-medium-emphasis mb-2" style="letter-spacing:.04em">Urutkan</div>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach (['terdekat' => 'Terdekat', 'terbaru' => 'Terbaru', 'termurah' => 'Termurah'] as $sKey => $sLabel): ?>
                            <button type="button" class="btn btn-sm text-start visi-side-item"
                                    :class="sortBy==='<?= $sKey ?>' ? 'active-soft' : ''"
                                    @click="sortBy='<?= $sKey ?>'"><?= $sLabel ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Konten -->
        <div class="col-lg-9">
            <div class="text-medium-emphasis fw-semibold mb-3">
                <span x-text="filtered.length"></span> event ditemukan
            </div>

            <div class="row g-3">
                <template x-for="ev in filtered" :key="ev.id">
                    <div class="col-sm-6 col-xl-4">
                        <div class="card h-100 event-card position-relative" role="button" @click="$store.ev.open(ev.id)" style="cursor:pointer">
                            <button type="button" class="event-save-btn" @click.stop="$store.ev.toggleSave(ev.id)"
                                    :aria-label="$store.ev.isSaved(ev.id) ? 'Hapus dari tersimpan' : 'Simpan event'">
                                <span x-text="$store.ev.isSaved(ev.id) ? '❤️' : '🤍'"></span>
                            </button>
                            <div class="event-card-cover" :style="ev.cover ? `background-image:url('${ev.cover}');background-size:cover;background-position:center` : `background:${ev.color}`">
                                <span class="event-card-emoji" x-show="!ev.cover" x-text="ev.emoji"></span>
                                <span class="badge event-card-badge" x-text="ev.catLabel"></span>
                                <span class="badge visi-dist-badge" x-show="$store.ev.distanceLabel(ev)"
                                      x-text="'📍 ' + $store.ev.distanceLabel(ev)"></span>
                            </div>
                            <div class="card-body">
                                <h2 class="h6 fw-semibold mb-1" x-text="ev.title"></h2>
                                <div class="small text-medium-emphasis mb-1" x-show="ev.date">
                                    <i class="cil-calendar me-1"></i><span x-text="ev.date"></span>
                                </div>
                                <div class="small text-medium-emphasis">
                                    <i class="cil-location-pin me-1"></i><span x-text="ev.venue"></span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0 pb-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary" x-text="ev.priceShort || 'Lihat detail'"></span>
                                <span class="small fw-semibold text-primary">Detail →</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty states -->
            <div class="text-center py-5 text-medium-emphasis" x-show="filtered.length === 0 && events.length > 0">
                <i class="cil-search fs-1 d-block mb-2"></i>
                <div class="fw-semibold">Event tidak ditemukan</div>
                <div class="small">Coba ubah kata kunci atau filter.</div>
            </div>
            <div class="text-center py-5 text-medium-emphasis" x-show="events.length === 0">
                <i class="cil-calendar fs-1 d-block mb-2"></i>
                Belum ada event mendatang.
            </div>
        </div>
    </div>
</div>

<script>
window.__PUBLIC_EVENTS__ = <?= json_encode($cards, JSON_UNESCAPED_UNICODE) ?>;

function eventsExplorer() {
    return {
        search: '',
        cat: '',
        sortBy: 'terdekat',

        get events() { return this.$store.ev.all; },

        init() {
            // Deep-link dari beranda/navbar: ?cat=konser dan ?saved=1.
            const params = new URLSearchParams(window.location.search);
            const cat = params.get('cat');
            if (cat && this.events.some(ev => ev.category === cat)) this.cat = cat;
            if (params.get('saved') === '1') this.savedOnly = true;
            const q = params.get('q');
            if (q) this.search = q;
        },
        savedOnly: false,
        get filtered() {
            const q = this.search.trim().toLowerCase();
            let list = this.events.filter(ev => {
                if (this.savedOnly && !this.$store.ev.isSaved(ev.id)) return false;
                if (this.cat && ev.category !== this.cat) return false;
                if (!q) return true;
                return ev.title.toLowerCase().includes(q)
                    || ev.venue.toLowerCase().includes(q)
                    || ev.catLabel.toLowerCase().includes(q);
            });
            const nullsLast = (v) => (v == null ? Infinity : v);
            list = [...list];
            if (this.sortBy === 'terbaru') {
                list.sort((a, b) => b.startTs - a.startTs);
            } else if (this.sortBy === 'termurah') {
                list.sort((a, b) => nullsLast(a.price) - nullsLast(b.price));
            } else if (this.sortBy === 'terdekat') {
                list.sort((a, b) => nullsLast(this.$store.ev.distanceKm(a)) - nullsLast(this.$store.ev.distanceKm(b)));
            }
            return list;
        },
    };
}
</script>
<?php require VIEW_PATH . '/public/_event_modal.php'; ?>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
