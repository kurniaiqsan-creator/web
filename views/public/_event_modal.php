<?php
/**
 * Partial: modal quick-view event + Alpine store bersama ($store.ev).
 *
 * Dipakai oleh beranda (home.php) & /events (tenant-home.php). Halaman yang
 * menyertakan partial ini WAJIB sudah men-set window.__PUBLIC_EVENTS__ (array
 * kartu hasil PublicController::decorateEvents) SEBELUM require partial ini.
 *
 * Store menyimpan: data event, wishlist (localStorage), posisi user (geolokasi),
 * dan event yang sedang dibuka di modal. Jarak dihitung client-side (Haversine).
 */
?>
<!-- Modal quick-view -->
<div x-data x-cloak x-show="$store.ev.current" x-transition.opacity
     class="visi-modal-overlay" @keydown.escape.window="$store.ev.close()">
    <div class="visi-modal-backdrop" @click="$store.ev.close()"></div>
    <template x-if="$store.ev.current">
        <div class="visi-modal-card card border-0 shadow-lg overflow-hidden">
            <div class="row g-0 visi-modal-row">
                <!-- Cover -->
                <div class="col-md-5 visi-modal-cover"
                     :style="$store.ev.current.cover ? `background-image:url('${$store.ev.current.cover}');background-size:cover;background-position:center` : `background:${$store.ev.current.color}`">
                    <span class="badge event-card-badge" x-text="$store.ev.current.catLabel"></span>
                    <span class="visi-modal-emoji" x-show="!$store.ev.current.cover" x-text="$store.ev.current.emoji"></span>
                    <div class="visi-modal-organizer" x-show="$store.ev.current.organizer">
                        <div class="small opacity-75">Diselenggarakan oleh</div>
                        <div class="fw-bold text-truncate" x-text="$store.ev.current.organizer"></div>
                    </div>
                </div>
                <!-- Konten -->
                <div class="col-md-7 d-flex flex-column p-4">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h2 class="h4 fw-bold mb-0" x-text="$store.ev.current.title"></h2>
                        <button type="button" class="btn-close flex-shrink-0" @click="$store.ev.close()" aria-label="Tutup"></button>
                    </div>

                    <div class="d-flex flex-column gap-3 my-2">
                        <div class="d-flex gap-3 align-items-center" x-show="$store.ev.current.date">
                            <span class="visi-modal-ico">📅</span>
                            <div class="fw-semibold" x-text="$store.ev.current.date"></div>
                        </div>
                        <div class="d-flex gap-3 align-items-start">
                            <span class="visi-modal-ico">📍</span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" x-text="$store.ev.current.venue"></div>
                                <div class="small text-medium-emphasis" x-show="$store.ev.current.address" x-text="$store.ev.current.address"></div>
                            </div>
                            <span class="badge text-bg-light align-self-center"
                                  x-show="$store.ev.distanceLabel($store.ev.current)"
                                  x-text="'📍 ' + $store.ev.distanceLabel($store.ev.current)"></span>
                        </div>
                        <div class="d-flex gap-3 align-items-center" x-show="$store.ev.current.priceLabel">
                            <span class="visi-modal-ico">💰</span>
                            <div class="fw-semibold text-primary" x-text="$store.ev.current.priceLabel"></div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="fw-bold mb-1">Tentang Event</div>
                        <p class="text-medium-emphasis mb-0" x-text="$store.ev.current.desc || 'Belum ada deskripsi.'"></p>
                    </div>

                    <div class="d-flex gap-2 mt-auto pt-3">
                        <a :href="$store.ev.current.url" class="btn btn-primary flex-grow-1 fw-semibold">🎟️ Dapatkan Tiket</a>
                        <button type="button" class="btn btn-outline-secondary"
                                @click="$store.ev.toggleSave($store.ev.current.id)"
                                :aria-label="$store.ev.isSaved($store.ev.current.id) ? 'Hapus dari tersimpan' : 'Simpan'"
                                x-text="$store.ev.isSaved($store.ev.current.id) ? '❤️' : '🤍'"></button>
                        <button type="button" class="btn btn-outline-secondary" @click="$store.ev.share($store.ev.current)" aria-label="Bagikan">🔗</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('ev', {
        all: window.__PUBLIC_EVENTS__ || [],
        saved: [],
        userPos: null,
        current: null,
        storeKey: 'visi_saved_events',

        init() {
            try { this.saved = JSON.parse(localStorage.getItem(this.storeKey) || '[]'); } catch (e) { this.saved = []; }
            // Minta lokasi user (opsional) untuk hitung jarak. Diam saja bila ditolak.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    p => { this.userPos = { lat: p.coords.latitude, lng: p.coords.longitude }; },
                    () => {},
                    { timeout: 8000, maximumAge: 600000 }
                );
            }
        },
        find(id) { return this.all.find(e => e.id === id) || null; },
        open(id) {
            this.current = this.find(id);
            if (this.current) document.body.style.overflow = 'hidden';
        },
        close() { this.current = null; document.body.style.overflow = ''; },

        isSaved(id) { return this.saved.includes(id); },
        toggleSave(id) {
            this.saved = this.isSaved(id) ? this.saved.filter(x => x !== id) : [...this.saved, id];
            try { localStorage.setItem(this.storeKey, JSON.stringify(this.saved)); } catch (e) {}
        },

        distanceKm(ev) {
            if (!this.userPos || !ev || ev.lat == null || ev.lng == null) return null;
            const R = 6371, toRad = d => d * Math.PI / 180;
            const dLat = toRad(ev.lat - this.userPos.lat);
            const dLng = toRad(ev.lng - this.userPos.lng);
            const a = Math.sin(dLat / 2) ** 2
                + Math.cos(toRad(this.userPos.lat)) * Math.cos(toRad(ev.lat)) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },
        distanceLabel(ev) {
            const d = this.distanceKm(ev);
            if (d == null) return '';
            return d < 1 ? Math.round(d * 1000) + ' m' : d.toFixed(1) + ' km';
        },

        async share(ev) {
            if (!ev) return;
            const url = new URL(ev.url, window.location.origin).href;
            try {
                if (navigator.share) { await navigator.share({ title: ev.title, url }); return; }
                await navigator.clipboard.writeText(url);
                window.showToast && showToast('Link disalin ke clipboard', 'success');
            } catch (e) { /* dibatalkan user */ }
        },
    });
});
</script>
