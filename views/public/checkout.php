<?php ob_start(); ?>
<div class="mx-auto max-w-2xl px-4 py-6" x-data="checkout()" x-init="init()">
    <a href="<?= base_url('/' . $tenant['slug'] . '/events/' . $event['id']) ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">&larr; Kembali ke event</a>

    <h1 class="text-xl font-bold sm:text-2xl mb-6">Checkout</h1>

    <!-- Empty cart -->
    <template x-if="seats.length === 0">
        <div class="card p-6 text-center">
            <p class="text-sm text-gray-500">Keranjang kosong. Silakan pilih kursi terlebih dahulu.</p>
            <a href="<?= base_url('/' . $tenant['slug'] . '/events/' . $event['id']) ?>" class="btn btn-primary btn-md mt-4 inline-flex">Pilih Kursi</a>
        </div>
    </template>

    <template x-if="seats.length > 0">
    <div>
    <!-- Steps -->
    <div class="mb-8 flex items-center justify-center gap-2 text-sm">
        <template x-for="(s,i) in ['Detail','Bayar','Selesai']" :key="i">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium"
                     :class="step >= i ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500'"
                     x-text="step > i ? '✓' : i+1"></div>
                <span :class="step >= i ? 'text-gray-900' : 'text-gray-400'" x-text="s"></span>
                <template x-if="i < 2"><div class="h-px w-8 bg-gray-200"></div></template>
            </div>
        </template>
    </div>

    <!-- Order Summary -->
    <div class="card p-4 sm:p-5 mb-6">
        <h2 class="font-semibold"><span x-text="eventTitle"></span></h2>
        <p class="text-xs text-gray-500 mt-1"><span x-text="eventDate"></span> · <span x-text="venueName"></span></p>
        <div class="mt-3 space-y-2 text-sm">
            <template x-for="s in seats" :key="s.label">
                <div class="flex justify-between">
                    <span x-text="'Kursi ' + s.label + (s.category ? ' (' + s.category + ')' : '')"></span>
                    <span x-text="formatRupiah(s.price)"></span>
                </div>
            </template>
            <template x-if="discount > 0">
                <div class="flex justify-between text-emerald-600">
                    <span x-text="'Diskon' + (appliedCode ? ' (' + appliedCode + ')' : '')"></span>
                    <span x-text="'-' + formatRupiah(discount)"></span>
                </div>
            </template>
            <div class="border-t pt-2 flex justify-between font-semibold text-base">
                <span>Total</span><span class="text-brand-600" x-text="formatRupiah(grandTotal)"></span>
            </div>
        </div>

        <!-- Promo -->
        <div class="mt-4 flex gap-2" x-show="step < 2">
            <input type="text" placeholder="Kode promo" x-model="promoCode" class="input flex-1 uppercase" @keyup.enter="applyPromo()">
            <button class="btn btn-outline" @click="applyPromo()" :disabled="promoLoading">Pakai</button>
        </div>
        <p x-show="promoMsg" x-text="promoMsg" class="mt-1 text-xs" :class="discount > 0 ? 'text-green-600' : 'text-red-600'"></p>
    </div>

    <!-- Step 1: Details -->
    <div x-show="step === 0" class="card p-4 sm:p-5">
        <h2 class="font-semibold mb-4">Data Pemesan</h2>
        <div class="space-y-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" x-model="name" class="input" placeholder="Masukkan nama"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" x-model="email" class="input" placeholder="contoh@email.com"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP (opsional)</label>
                <input type="tel" x-model="phone" class="input" placeholder="+6281234567890"></div>
            <button class="btn btn-primary btn-lg w-full" @click="goToPayment()" :disabled="!name || !email">
                Lanjut ke Pembayaran
            </button>
        </div>
    </div>

    <!-- Step 2: Payment -->
    <div x-show="step === 1" class="card p-4 sm:p-5">
        <h2 class="font-semibold mb-4">Metode Pembayaran</h2>
        <div class="space-y-3">
            <?php foreach ([['va','Virtual Account','Transfer via ATM/m-banking'],['ewallet','E-Wallet (QRIS)','GoPay, OVO, Dana, ShopeePay'],['card','Kartu Debit/Kredit','Visa, Mastercard, JCB']] as [$id,$pname,$desc]): ?>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3" :class="paymentMethod === '<?=$id?>' ? 'border-brand-500 bg-brand-50' : 'border-gray-200'">
                    <input type="radio" name="pm" value="<?=$id?>" x-model="paymentMethod" class="mt-0.5">
                    <div><span class="font-medium"><?=$pname?></span><p class="text-xs text-gray-500"><?=$desc?></p></div>
                </label>
            <?php endforeach; ?>
            <button class="btn btn-primary btn-lg w-full" @click="processPayment()" :disabled="loading" x-text="loading ? 'Memproses...' : ('Bayar ' + formatRupiah(grandTotal))"></button>
        </div>
    </div>

    <!-- Step 3: Pending -->
    <div x-show="step === 2" class="card p-4 sm:p-5 text-center">
        <div class="animate-spin mx-auto mb-4 h-8 w-8 rounded-full border-2 border-gray-300 border-t-brand-500"></div>
        <h2 class="font-semibold">Menunggu Pembayaran</h2>
        <p class="mt-1 text-xs text-gray-500">Order: <span class="font-mono font-semibold" x-text="orderCode"></span></p>

        <div x-show="paymentMethod === 'va'" class="mt-4 rounded-lg bg-gray-50 border p-4">
            <p class="text-xs text-gray-500 mb-1">Nomor Virtual Account (BCA)</p>
            <div class="flex items-center justify-center gap-2">
                <span class="text-2xl font-mono font-bold tracking-wider" x-text="vaNumber"></span>
            </div>
            <p class="mt-2 text-xs text-gray-500">Total: <span class="font-bold" x-text="formatRupiah(grandTotal)"></span></p>
        </div>

        <button class="btn btn-secondary w-full mt-6" @click="simulatePayment()" :disabled="loading" x-text="loading ? 'Memproses...' : '(Dev) Simulasi Pembayaran Sukses'"></button>
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
