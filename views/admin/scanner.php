<?php ob_start(); ?>
<div x-data="scanner()" x-init="init()">
    <div class="mb-4"><h1 class="fs-3 fw-bold mb-1">Scanner Tiket</h1><p class="text-medium-emphasis mb-0">Validasi tiket on‑site</p></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <!-- Mode toggle -->
            <div class="btn-group mb-3" role="group">
                <input type="radio" class="btn-check" id="modeManual" value="manual" x-model="mode" @change="onModeChange()">
                <label class="btn btn-outline-primary" for="modeManual"><i class="cil-keyboard me-1"></i>Manual</label>
                <input type="radio" class="btn-check" id="modeCamera" value="camera" x-model="mode" @change="onModeChange()">
                <label class="btn btn-outline-primary" for="modeCamera"><i class="cil-camera me-1"></i>Kamera</label>
            </div>

            <!-- Input -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <template x-if="mode==='manual'">
                        <div class="mx-auto" style="max-width:400px">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="cil-qr-code"></i></span>
                                <input type="text" class="form-control" x-model="token" placeholder="Masukkan token / scan QR..." @keyup.enter="scan()" x-ref="manualInput">
                            </div>
                            <button class="btn btn-primary btn-lg w-100 mt-3" @click="scan()" :disabled="loading">
                                <i class="cil-qr-code me-1"></i><span x-text="loading ? 'Memvalidasi...' : 'Scan & Validasi'"></span>
                            </button>
                        </div>
                    </template>
                    <template x-if="mode==='camera'">
                        <div>
                            <div id="qr-reader" class="mx-auto" style="max-width:360px"></div>
                            <p class="text-medium-emphasis mb-0 mt-3" x-show="!cameraError">Arahkan kamera ke QR code tiket</p>
                            <p class="text-danger mb-0 mt-3" x-show="cameraError" x-text="cameraError"></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Result -->
            <template x-if="result">
                <div class="card mt-3 border-2" :class="{
                    'border-success':result.result==='validated',
                    'border-warning':result.result==='already_used',
                    'border-danger':result.result==='invalid'
                }">
                    <div class="card-body text-center py-4" :class="{
                        'bg-success bg-opacity-10':result.result==='validated',
                        'bg-warning bg-opacity-10':result.result==='already_used',
                        'bg-danger bg-opacity-10':result.result==='invalid'
                    }">
                        <template x-if="result.result==='validated'">
                            <div><i class="cil-check-circle fs-1 text-success mb-2 d-block"></i><h4 class="text-success">Tiket Valid</h4><p class="mb-0 text-success">Diizinkan masuk</p></div>
                        </template>
                        <template x-if="result.result==='already_used'">
                            <div><i class="cil-warning fs-1 text-warning mb-2 d-block"></i><h4 class="text-warning">Sudah Digunakan</h4><p class="mb-0 small text-warning" x-text="'Pada: '+(result.used_at||'-')"></p></div>
                        </template>
                        <template x-if="result.result==='invalid'">
                            <div><i class="cil-x-circle fs-1 text-danger mb-2 d-block"></i><h4 class="text-danger">Tidak Valid</h4><p class="mb-0 text-danger">Token tidak ditemukan</p></div>
                        </template>
                    </div>
                    <div class="card-footer">
                        <div class="row small">
                            <div class="col-4"><span class="text-medium-emphasis">Kursi</span><br><strong x-text="result.seat_label||'-'"></strong></div>
                            <div class="col-4"><span class="text-medium-emphasis">Order</span><br><strong x-text="result.order_id||'-'"></strong></div>
                            <div class="col-4"><span class="text-medium-emphasis">Token</span><br><span class="font-monospace text-truncate d-block" x-text="lastToken||'-'"></span></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- History -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center"><i class="cil-history me-2"></i><h5 class="card-title mb-0">Riwayat Scan</h5></span>
                    <span class="badge bg-secondary" x-text="history.length"></span>
                </div>
                <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
                    <template x-if="history.length === 0">
                        <p class="text-center text-medium-emphasis py-5 mb-0">Belum ada scan</p>
                    </template>
                    <ul class="list-group list-group-flush">
                        <template x-for="(h,i) in history" :key="i">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong x-text="h.seat_label||h.token"></strong><br><small class="text-medium-emphasis" x-text="h.time"></small></div>
                                <span class="badge" :class="{
                                    'bg-success':h.result==='validated',
                                    'bg-warning text-dark':h.result==='already_used',
                                    'bg-danger':h.result==='invalid'
                                }" x-text="{validated:'OK',already_used:'Used',invalid:'Invalid'}[h.result]||h.result"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function scanner() {
    return {
        mode: 'manual', token: '', lastToken: '', result: null, history: [],
        loading: false, cameraError: '', html5qr: null, busy: false,

        init() {
            this.$nextTick(() => { if (this.$refs.manualInput) this.$refs.manualInput.focus(); });
        },

        // Ekstrak token dari hasil scan: bisa berupa URL penuh (/t/{token}) atau token mentah.
        extractToken(text) {
            text = (text || '').trim();
            const m = text.match(/\/t\/([^/?#]+)/);
            return m ? m[1] : text;
        },

        onModeChange() {
            if (this.mode === 'camera') {
                this.startCamera();
            } else {
                this.stopCamera();
                this.$nextTick(() => { if (this.$refs.manualInput) this.$refs.manualInput.focus(); });
            }
        },

        async startCamera() {
            this.cameraError = '';
            if (typeof Html5Qrcode === 'undefined') {
                this.cameraError = 'Library kamera gagal dimuat. Gunakan mode Manual.';
                return;
            }
            await this.$nextTick();
            try {
                this.html5qr = new Html5Qrcode('qr-reader');
                await this.html5qr.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 220, height: 220 } },
                    (decodedText) => { this.onCameraScan(decodedText); },
                    () => { /* abaikan frame tanpa QR */ }
                );
            } catch (e) {
                this.cameraError = 'Tidak bisa mengakses kamera: ' + (e && e.message ? e.message : e);
            }
        },

        async stopCamera() {
            if (this.html5qr) {
                try { await this.html5qr.stop(); await this.html5qr.clear(); } catch (e) { /* ignore */ }
                this.html5qr = null;
            }
        },

        onCameraScan(decodedText) {
            // Debounce: cegah submit berulang dari frame beruntun.
            if (this.busy) return;
            const tok = this.extractToken(decodedText);
            if (!tok || tok === this.lastToken) return;
            this.token = tok;
            this.scan();
        },

        async scan() {
            const tok = this.extractToken(this.token);
            if (!tok) return showToast('Masukkan token', 'warning');
            if (this.busy) return;
            this.busy = true; this.loading = true;
            try {
                const res = await fetch(base_url('/api/v1/tickets/validate'), {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ticket_token: tok, mark_used: true, scanner_id: 'web'})
                });
                const data = await res.json();
                this.result = data;
                this.lastToken = tok;
                const labels = {validated:'Valid ✓', already_used:'Sudah dipakai', invalid:'Tidak valid'};
                showToast(labels[data.result] || data.result, data.result === 'validated' ? 'success' : (data.result === 'invalid' ? 'error' : 'warning'));
                this.history.unshift({
                    result: data.result, seat_label: data.seat_label, token: tok.substring(0,10),
                    time: new Date().toLocaleTimeString('id-ID')
                });
                if (this.history.length > 50) this.history.pop();
                this.token = '';
                if (this.$refs.manualInput) this.$refs.manualInput.focus();
            } catch (e) {
                showToast('Gagal scan', 'error');
            } finally {
                this.loading = false;
                // Beri jeda agar tidak men-submit QR yang sama berkali-kali dari kamera.
                setTimeout(() => { this.busy = false; }, 1500);
            }
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
