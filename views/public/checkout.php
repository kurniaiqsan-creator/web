<?php ob_start(); ?>
<div class="container-lg py-4" style="max-width:42rem" x-data="checkout()" x-init="init()">
    <a href="<?= base_url('/' . $tenant['slug'] . '/events/' . $event['id']) ?>"
       class="text-medium-emphasis text-decoration-none small mb-4 d-inline-flex align-items-center gap-1">
        <i class="cil-arrow-left"></i> Kembali ke event
    </a>

    <h1 class="h3 fw-bold mb-4">Checkout</h1>

    <template x-if="seats.length === 0">
        <div class="card">
            <div class="card-body text-center p-4">
                <p class="text-medium-emphasis small mb-3">Keranjang kosong. Silakan pilih kursi terlebih dahulu.</p>
                <a href="<?= base_url('/' . $tenant['slug'] . '/events/' . $event['id']) ?>" class="btn btn-primary">Pilih Kursi</a>
            </div>
        </div>
    </template>

    <template x-if="seats.length > 0">
        <div>
            <div class="d-flex justify-content-center gap-2 small mb-4 flex-wrap">
                <template x-for="(s,i) in ['Detail','Bayar','Selesai']" :key="i">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-medium"
                              style="width:28px;height:28px;font-size:12px"
                              :class="step >= i ? 'bg-primary text-white' : 'bg-body-secondary text-medium-emphasis'"
                              x-text="step > i ? '✓' : i+1"></span>
                        <span :class="step >= i ? 'fw-medium' : 'text-medium-emphasis'" x-text="s"></span>
                        <template x-if="i < 2">
                            <span class="bg-body-secondary" style="height:1px;width:24px;display:inline-block"></span>
                        </template>
                    </div>
                </template>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-1" x-text="eventTitle"></h2>
                    <p class="small text-medium-emphasis mb-3">
                        <span x-text="eventDate"></span> · <span x-text="venueName"></span>
                    </p>
                    <div class="d-flex flex-column gap-1 small">
                        <template x-for="s in seats" :key="s.label">
                            <div class="d-flex justify-content-between">
                                <span class="text-medium-emphasis" x-text="'Kursi ' + s.label + (s.category ? ' (' + s.category + ')' : '')"></span>
                                <span x-text="formatRupiah(s.price)"></span>
                            </div>
                        </template>
                        <template x-if="discount > 0">
                            <div class="d-flex justify-content-between text-success">
                                <span x-text="'Diskon' + (appliedCode ? ' (' + appliedCode + ')' : '')"></span>
                                <span x-text="'-' + formatRupiah(discount)"></span>
                            </div>
                        </template>
                        <div class="border-top pt-2 mt-1 d-flex justify-content-between fw-semibold">
                            <span>Total</span>
                            <span class="text-primary" x-text="formatRupiah(grandTotal)"></span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3" x-show="step < 2">
                        <input type="text" placeholder="Kode promo" x-model="promoCode" class="form-control text-uppercase" @keyup.enter="applyPromo()">
                        <button class="btn btn-outline-primary" @click="applyPromo()" :disabled="promoLoading">Pakai</button>
                    </div>
                    <p x-show="promoMsg" x-text="promoMsg" class="small mt-2 mb-0"
                       :class="discount > 0 ? 'text-success' : 'text-danger'"></p>
                </div>
            </div>

            <div x-show="step === 0" class="card">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Data Pemesan</h2>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" x-model="name" class="form-control" placeholder="Masukkan nama">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" x-model="email" class="form-control" placeholder="contoh@email.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor HP (opsional)</label>
                        <input type="tel" x-model="phone" class="form-control" placeholder="+6281234567890">
                    </div>
                    <button class="btn btn-primary btn-lg w-100" @click="goToPayment()" :disabled="!name || !email">
                        Lanjut ke Pembayaran
                    </button>
                </div>
            </div>

            <div x-show="step === 1" class="card">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Metode Pembayaran</h2>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php foreach ([['va','Virtual Account','Transfer via ATM/m-banking','cil-bank'],['ewallet','E-Wallet (QRIS)','GoPay, OVO, Dana, ShopeePay','cil-mobile'],['card','Kartu Debit/Kredit','Visa, Mastercard, JCB','cil-credit-card']] as [$id,$pname,$desc,$icon]): ?>
                            <label class="d-flex align-items-start gap-3 rounded-3 border p-3"
                                   style="cursor:pointer"
                                   :class="paymentMethod === '<?= $id ?>' ? 'border-primary bg-primary bg-opacity-10' : ''">
                                <input type="radio" name="pm" value="<?= $id ?>" x-model="paymentMethod" class="form-check-input mt-1">
                                <i class="<?= $icon ?> fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-medium"><?= $pname ?></div>
                                    <div class="small text-medium-emphasis"><?= $desc ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-primary btn-lg w-100" @click="processPayment()" :disabled="loading"
                            x-text="loading ? 'Memproses...' : ('Bayar ' + formatRupiah(grandTotal))"></button>
                </div>
            </div>

            <div x-show="step === 2" class="card">
                <div class="card-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
                    <h2 class="h6 fw-semibold mb-1">Menunggu Pembayaran</h2>
                    <p class="small text-medium-emphasis mb-3">
                        Order: <span class="font-monospace fw-semibold" x-text="orderCode"></span>
                    </p>

                    <div x-show="paymentMethod === 'va'" class="rounded-3 border bg-body-tertiary p-3 mb-3">
                        <div class="small text-medium-emphasis mb-1">Nomor Virtual Account (BCA)</div>
                        <div class="fs-3 font-monospace fw-bold" x-text="vaNumber"></div>
                        <div class="small text-medium-emphasis mt-2">
                            Total: <span class="fw-bold" x-text="formatRupiah(grandTotal)"></span>
                        </div>
                    </div>

                    <button class="btn btn-outline-primary w-100" @click="simulatePayment()" :disabled="loading"
                            x-text="loading ? 'Memproses...' : '(Dev) Simulasi Pembayaran Sukses'"></button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function checkout() {
    return {
        tenantId: <?= (int)($tenant['id'] ?? 0) ?>,
        tenantSlug: '<?= View::e($tenant['slug'] ?? '') ?>',
        eventId: <?= (int)($event['id'] ?? 0) ?>,
        eventTitle: '<?= View::e($event['title'] ?? '') ?>',
        venueName: '<?= View::e($event['venue_name'] ?? '') ?>',
        eventDate: '',
        step: 0,
        name: '', email: '', phone: '',
        seats: [],
        seatHoldId: null,
        subtotal: 0,
        discount: 0,
        appliedCode: '',
        promoCode: '', promoMsg: '', promoLoading: false,
        paymentMethod: 'va',
        loading: false,
        orderId: null,
        orderCode: '',
        vaNumber: '8234 5678 9012 3456',

        get grandTotal() { return Math.max(0, this.subtotal - this.discount); },

        init() {
            const raw = sessionStorage.getItem('visi_cart');
            if (!raw) return;
            try {
                const cart = JSON.parse(raw);
                if (parseInt(cart.eventId) !== this.eventId) return;
                this.seats = cart.seats || [];
                this.seatHoldId = cart.seatHoldId || null;
                this.subtotal = cart.totalPrice || this.seats.reduce((a, s) => a + s.price, 0);
                this.eventDate = cart.eventDate || '';
            } catch (e) { /* ignore */ }
        },

        async applyPromo() {
            if (!this.promoCode) return;
            this.promoLoading = true;
            try {
                const res = await fetch(base_url('/api/v1/promotions/evaluate'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        promo_codes: [this.promoCode.toUpperCase()],
                        cart: this.seats.map(s => ({seat_label: s.label, price_cents: s.price})),
                    })
                });
                const data = await res.json();
                if (data.total_discount_cents > 0) {
                    this.discount = data.total_discount_cents;
                    this.appliedCode = this.promoCode.toUpperCase();
                    this.promoMsg = 'Diskon ' + this.formatRupiah(this.discount) + ' berhasil diterapkan!';
                    showToast('Promo diterapkan', 'success');
                } else {
                    this.discount = 0;
                    this.appliedCode = '';
                    this.promoMsg = 'Kode promo tidak valid atau sudah habis.';
                }
            } catch (e) {
                this.promoMsg = 'Gagal memeriksa promo.';
            }
            this.promoLoading = false;
        },

        goToPayment() {
            if (!this.name || !this.email) return showToast('Lengkapi nama dan email', 'error');
            this.step = 1;
        },

        async processPayment() {
            this.loading = true;
            try {
                const res = await fetch(base_url('/api/v1/orders'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        tenant_id: this.tenantId,
                        event_id: this.eventId,
                        seat_hold_id: this.seatHoldId,
                        currency: 'idr',
                        customer: {name: this.name, email: this.email, phone: this.phone},
                        promo_code: this.appliedCode || null,
                        discount_cents: this.discount,
                        items: this.seats.map(s => ({
                            event_id: this.eventId,
                            seat_label: s.label,
                            category_id: s.categoryId || null,
                            price_cents: s.price,
                        })),
                    })
                });
                const data = await res.json();
                if (data.order_id) {
                    this.orderId = data.order_id;
                    this.orderCode = data.order_code;
                    this.step = 2;
                } else {
                    showToast('Gagal membuat order', 'error');
                }
            } catch (e) {
                showToast('Gagal memproses pembayaran', 'error');
            }
            this.loading = false;
        },

        async simulatePayment() {
            this.loading = true;
            try {
                await fetch(base_url('/webhooks/payment'), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        id: 'evt_' + Date.now(),
                        type: 'payment_intent.succeeded',
                        data: {object: {
                            id: 'pi_' + Date.now(),
                            amount: this.grandTotal,
                            currency: 'idr',
                            status: 'succeeded',
                            provider: 'midtrans',
                            metadata: {tenant_id: String(this.tenantId), order_id: String(this.orderId), event_id: String(this.eventId)},
                        }}
                    })
                });
                sessionStorage.removeItem('visi_cart');
                window.location.href = base_url('/' + this.tenantSlug + '/events/' + this.eventId + '/confirmation?order=' + encodeURIComponent(this.orderCode));
            } catch (e) {
                showToast('Gagal mengkonfirmasi pembayaran', 'error');
                this.loading = false;
            }
        },

        formatRupiah(cents) {
            return 'Rp' + new Intl.NumberFormat('id-ID').format(cents);
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
