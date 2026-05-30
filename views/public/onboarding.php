<?php ob_start(); ?>
<div class="min-h-screen bg-gray-50 flex flex-col" x-data="onboarding()">
    <header class="border-b bg-white"><div class="mx-auto flex h-14 max-w-3xl items-center gap-2 px-4">
        <svg class="h-6 w-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        <span class="text-lg font-bold">Visi Onboarding</span>
    </div></header>

    <main class="flex-1 flex items-start justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-lg">
            <!-- Progress -->
            <div class="mb-8 flex items-center justify-between">
                <?php foreach (['Akun','Tenant','Branding','Payout','Event'] as $i => $l): ?>
                    <div class="flex items-center">
                        <div class="flex flex-col items-center">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium"
                                 :class="step > <?=$i?> ? 'bg-green-500 text-white' : step === <?=$i?> ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-500'"
                                 x-text="step > <?=$i?> ? '✓' : <?=$i+1?>"></div>
                            <span class="mt-1 text-[10px] hidden sm:block" :class="step >= <?=$i?> ? 'text-gray-900 font-medium' : 'text-gray-400'"><?=$l?></span>
                        </div>
                        <?php if ($i < 4): ?><div class="h-0.5 w-8 sm:w-12 mx-1" :class="step > <?=$i?> ? 'bg-green-500' : 'bg-gray-200'"></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="border-b px-6 py-4 font-semibold" x-text="['Akun','Tenant','Branding','Payout','Event Pertama','Selesai'][step]"></div>

                <div class="p-6 space-y-4">
                    <!-- Step 0 -->
                    <template x-if="step === 0">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label><input class="input" x-model="name" placeholder="Budi Santoso"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" class="input" x-model="email" placeholder="budi@email.com"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><input type="password" class="input" x-model="password" placeholder="Min. 6 karakter"></div>
                        </div>
                    </template>

                    <!-- Step 1 -->
                    <template x-if="step === 1">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Bisnis</label><input class="input" x-model="tenantName" placeholder="Acoustic Nights"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Slug URL</label><input class="input" x-model="tenantSlug" placeholder="acoustic-nights"><p class="text-xs text-gray-500 mt-1">visi.id/<span x-text="tenantSlug || 'acoustic-nights'"></span></p></div>
                        </div>
                    </template>

                    <!-- Step 2 -->
                    <template x-if="step === 2">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Warna Brand</label>
                                <div class="flex gap-3"><input type="color" x-model="primaryColor" class="h-10 w-10 rounded border"><input class="input flex-1" x-model="primaryColor"></div></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email Pengirim</label><input class="input" x-model="emailFrom" placeholder="no-reply@acousticnights.com"></div>
                            <div class="rounded-lg border p-4" :style="'border-color:'+primaryColor">
                                <div class="flex items-center gap-2"><div class="h-8 w-8 rounded flex items-center justify-center text-white text-xs font-bold" :style="'background-color:'+primaryColor" x-text="(tenantName||'AN').slice(0,2).toUpperCase()"></div><span class="font-semibold" x-text="tenantName||'Nama Tenant'"></span></div>
                                <p class="text-xs text-gray-500 mt-1">Preview header</p>
                            </div>
                        </div>
                    </template>

                    <!-- Step 3 -->
                    <template x-if="step === 3">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Payment Gateway</label>
                                <select class="input" x-model="provider">
                                    <option value="midtrans">Midtrans</option><option value="xendit">Xendit</option><option value="doku">DOKU</option>
                                </select></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nomor VA / ID Merchant</label><input class="input" x-model="vaNumber" placeholder="1234567890"></div>
                        </div>
                    </template>

                    <!-- Step 4 -->
                    <template x-if="step === 4">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Judul Event Pertama</label><input class="input" x-model="eventTitle" placeholder="Konser Akustik Malam Minggu"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label><input type="date" class="input" x-model="eventDate"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Tipe Tiket</label>
                                <div class="flex gap-3"><label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="seat_map" x-model="eventType"><span class="text-sm">Seat Map</span></label>
                                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" value="general_admission" x-model="eventType"><span class="text-sm">General Admission</span></label></div></div>
                        </div>
                    </template>

                    <!-- Step 5: Done -->
                    <template x-if="step === 5">
                        <div class="text-center py-4">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 mb-4"><svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg></div>
                            <h2 class="text-xl font-bold">Siap Jualan Tiket!</h2>
                            <p class="mt-2 text-sm text-gray-500">Tenant <span class="font-semibold" x-text="tenantName||'kamu'"></span> sudah siap.</p>
                            <a href="/login" class="btn btn-primary btn-md mt-6 no-underline inline-flex">Buka Dashboard</a>
                        </div>
                    </template>
                </div>

                <template x-if="step < 5">
                    <div class="border-t px-6 py-4 flex items-center justify-between">
                        <button class="btn btn-ghost btn-sm" @click="step = Math.max(0, step-1)" :disabled="step===0">Kembali</button>
                        <button class="btn btn-primary btn-md" @click="nextStep()" x-text="step===4?'Selesaikan':'Lanjut'"></button>
                    </div>
                </template>
            </div>

            <p class="mt-4 text-center text-xs text-gray-400" x-show="step < 5"><button @click="step=5" class="hover:text-gray-600">Lewati, atur nanti</button></p>
        </div>
    </main>
</div>

<script>
function onboarding() {
    return {
        step: 0,
        name: '', email: '', password: '',
        tenantName: '', tenantSlug: '',
        primaryColor: '#FF5722', emailFrom: '',
        provider: 'midtrans', vaNumber: '',
        eventTitle: '', eventDate: '', eventType: 'seat_map',
        nextStep() {
            if (this.step === 1 && this.tenantName && !this.tenantSlug) {
                this.tenantSlug = this.tenantName.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'');
            }
            if (this.step === 2 && !this.emailFrom && this.tenantSlug) {
                this.emailFrom = 'no-reply@'+this.tenantSlug+'.com';
            }
            if (this.step === 4) {
                const data = {
                    slug: this.tenantSlug, name: this.tenantName,
                    owner: {email: this.email, name: this.name},
                    branding: {primary_color: this.primaryColor, email_from: this.emailFrom},
                    payout: {provider: this.provider}
                };
                fetch('/api/v1/tenants', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(data)
                }).then(r => r.json()).then(d => {
                    if (d.tenant_id) { showToast('Tenant berhasil dibuat!', 'success'); this.step = 5; }
                    else { showToast('Gagal: ' + (d.error?.message||'Coba lagi'), 'error'); }
                }).catch(() => showToast('Gagal membuat tenant', 'error'));
                return;
            }
            this.step = Math.min(5, this.step + 1);
        }
    }
}
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
