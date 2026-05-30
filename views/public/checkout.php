<?php ob_start(); ?>
<div class="mx-auto max-w-2xl px-4 py-6" x-data="checkout()">
    <a href="../" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">&larr; Kembali ke event</a>

    <h1 class="text-xl font-bold sm:text-2xl mb-6">Checkout</h1>

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
        <h2 class="font-semibold">Ringkasan Pesanan</h2>
        <div class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between"><span>Kursi A-1 (VIP)</span><span>Rp200.000</span></div>
            <div class="flex justify-between"><span>Kursi A-2 (VIP)</span><span>Rp200.000</span></div>
            <div class="border-t pt-2 flex justify-between font-semibold text-base">
                <span>Total</span><span class="text-brand-600">Rp400.000</span>
            </div>
        </div>

        <!-- Promo -->
        <div class="mt-4 flex gap-2">
            <input type="text" placeholder="Kode promo" x-model="promoCode" class="input flex-1" @keyup.enter="applyPromo()">
            <button class="btn btn-outline" @click="applyPromo()">Pakai</button>
        </div>
        <p x-show="promoMsg" x-text="promoMsg" class="mt-1 text-xs text-green-600"></p>
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
            <button class="btn btn-primary btn-lg w-full" @click="step = 1" :disabled="!name || !email">
                Lanjut ke Pembayaran
            </button>
        </div>
    </div>

    <!-- Step 2: Payment -->
    <div x-show="step === 1" class="card p-4 sm:p-5">
        <h2 class="font-semibold mb-4">Metode Pembayaran</h2>
        <div class="space-y-3">
            <?php foreach ([['va','Virtual Account','Transfer via ATM/m-banking'],['ewallet','E-Wallet (QRIS)','GoPay, OVO, Dana, ShopeePay'],['card','Kartu Debit/Kredit','Visa, Mastercard, JCB']] as [$id,$name,$desc]): ?>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3" :class="paymentMethod === '<?=$id?>' ? 'border-brand-500 bg-brand-50' : 'border-gray-200'">
                    <input type="radio" name="pm" value="<?=$id?>" x-model="paymentMethod" class="mt-0.5">
                    <div><span class="font-medium"><?=$name?></span><p class="text-xs text-gray-500"><?=$desc?></p></div>
                </label>
            <?php endforeach; ?>
            <button class="btn btn-primary btn-lg w-full" @click="processPayment()" :disabled="loading" x-text="loading ? 'Memproses...' : 'Bayar Rp400.000'"></button>
        </div>
    </div>

    <!-- Step 3: Pending -->
    <div x-show="step === 2" class="card p-4 sm:p-5 text-center">
        <div class="animate-spin mx-auto mb-4 h-8 w-8 rounded-full border-2 border-gray-300 border-t-brand-500"></div>
        <h2 class="font-semibold">Menunggu Pembayaran</h2>

        <div x-show="paymentMethod === 'va'" class="mt-4 rounded-lg bg-gray-50 border p-4">
            <p class="text-xs text-gray-500 mb-1">Nomor Virtual Account (BCA)</p>
            <div class="flex items-center justify-center gap-2">
                <span class="text-2xl font-mono font-bold tracking-wider">8234 5678 9012 3456</span>
            </div>
            <p class="mt-2 text-xs text-gray-500">Total: <span class="font-bold">Rp400.000</span></p>
        </div>

        <button class="btn btn-secondary w-full mt-6" @click="step = 3">(Dev) Simulasi Pembayaran Sukses</button>
    </div>

    <!-- Step 3: Success -->
    <div x-show="step === 3" class="card p-4 sm:p-5 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 mb-4">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <h2 class="text-xl font-bold">Pembayaran Berhasil!</h2>
        <p class="mt-2 text-sm text-gray-500">E-ticket telah dikirim ke email Anda.</p>
        <a href="confirmation" class="btn btn-primary btn-lg mt-6 w-full no-underline">Lihat E-Ticket</a>
    </div>
</div>

<script>
function checkout() {
    return {
        step: 0,
        name: '', email: '', phone: '',
        promoCode: '', promoMsg: '',
        paymentMethod: 'va',
        loading: false,
        applyPromo() {
            if (this.promoCode === 'PROMO10') {
                this.promoMsg = 'Diskon 10% berhasil diterapkan! (-Rp40.000)';
                showToast('Promo diterapkan', 'success');
            } else if (this.promoCode) {
                this.promoMsg = 'Kode promo tidak valid';
            }
        },
        async processPayment() {
            this.loading = true;
            this.step = 2;
            setTimeout(() => { this.loading = false; }, 1000);
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
