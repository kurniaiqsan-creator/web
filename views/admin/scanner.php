<?php ob_start(); ?>
<div x-data="scanner()">
    <div class="mb-4"><h1 class="fs-3 fw-bold mb-1">Scanner Tiket</h1><p class="text-medium-emphasis mb-0">Validasi tiket on‑site</p></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <!-- Mode toggle -->
            <div class="btn-group mb-3" role="group">
                <input type="radio" class="btn-check" id="modeManual" value="manual" x-model="mode">
                <label class="btn btn-outline-primary" for="modeManual"><i class="cil-keyboard me-1"></i>Manual</label>
                <input type="radio" class="btn-check" id="modeCamera" value="camera" x-model="mode">
                <label class="btn btn-outline-primary" for="modeCamera"><i class="cil-camera me-1"></i>Kamera</label>
            </div>

            <!-- Input -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <template x-if="mode==='manual'">
                        <div class="mx-auto" style="max-width:400px">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="cil-qr-code"></i></span>
                                <input type="text" class="form-control" x-model="token" placeholder="Masukkan token tiket..." @keyup.enter="scan()" autofocus>
                            </div>
                            <button class="btn btn-primary btn-lg w-100 mt-3" @click="scan()"><i class="cil-qr-code me-1"></i>Scan & Validasi</button>
                        </div>
                    </template>
                    <template x-if="mode==='camera'">
                        <div>
                            <div class="border border-2 border-dashed rounded mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:200px;height:140px;background:#f8f9fa">
                                <i class="cil-camera fs-1 text-medium-emphasis"></i>
                            </div>
                            <p class="text-medium-emphasis mb-0">Arahkan kamera ke QR code tiket</p>
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
                            <div><i class="cil-warning fs-1 text-warning mb-2 d-block"></i><h4 class="text-warning">Sudah Digunakan</h4><p class="mb-0 small text-warning" x-text="'Pada: '+result.used_at"></p></div>
                        </template>
                        <template x-if="result.result==='invalid'">
                            <div><i class="cil-x-circle fs-1 text-danger mb-2 d-block"></i><h4 class="text-danger">Tidak Valid</h4><p class="mb-0 text-danger">Token tidak ditemukan</p></div>
                        </template>
                    </div>
                    <div class="card-footer">
                        <div class="row small">
                            <div class="col-4"><span class="text-medium-emphasis">Kursi</span><br><strong x-text="result.seat_label||'-'"></strong></div>
                            <div class="col-4"><span class="text-medium-emphasis">Order</span><br><strong x-text="result.order_id||'-'"></strong></div>
                            <div class="col-4"><span class="text-medium-emphasis">Token</span><br><span class="font-monospace text-truncate d-block" x-text="token||'-'"></span></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- History -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center"><i class="cil-history me-2"></i><h5 class="card-title mb-0">Riwayat Scan</h5></div>
                <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
                    <template x-if="history.length === 0">
                        <p class="text-center text-medium-emphasis py-5 mb-0">Belum ada scan</p>
                    </template>
                    <ul class="list-group list-group-flush">
                        <template x-for="(h,i) in history" :key="i">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong x-text="h.seat_label||h.token"></strong><br><small class="text-medium-emphasis" x-text="h.order_id"></small></div>
                                <span class="badge" :class="{
                                    'bg-success':h.result==='validated',
                                    'bg-warning text-dark':h.result==='already_used',
                                    'bg-danger':h.result==='invalid'
                                }" x-text="{validated:'OK',already_used:'Used',invalid:'Invalid'}[h.result]"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function scanner() {
    return {
        mode: 'manual', token: '', result: null, history: [],
        async scan() {
            if (!this.token.trim()) return showToast('Masukkan token', 'warning');
            try {
                const res = await fetch('/api/v1/tickets/validate', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ticket_token: this.token, mark_used: true, scanner_id: 'web'})
                });
                this.result = await res.json();
                this.history.unshift(this.result);
                if (this.history.length > 30) this.history.pop();
                this.token = '';
            } catch { showToast('Gagal scan', 'error'); }
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
