<?php ob_start(); ?>
<div>
    <div class="mb-4"><h1 class="fs-3 fw-bold mb-1">Pengaturan</h1><p class="text-medium-emphasis mb-0">Konfigurasi tenant & integrasi</p></div>

    <div class="row g-4">
        <!-- Branding -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="cil-brush me-2"></i>Branding</h5>
                    <button class="btn btn-primary btn-sm" onclick="showToast('Branding disimpan')"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Nama Tenant</label><input class="form-control" value="<?= View::e($tenant['name']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Warna Brand</label>
                        <div class="input-group"><input type="color" class="form-control form-control-color" value="<?= View::e($tenant['branding']['primary_color'] ?? '#FF5722') ?>"><input class="form-control" value="<?= View::e($tenant['branding']['primary_color'] ?? '#FF5722') ?>"></div></div>
                    <div class="mb-3"><label class="form-label">Email From</label><input class="form-control" value="<?= View::e($tenant['branding']['email_from'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="form-label">Reply To</label><input class="form-control" value="<?= View::e($tenant['branding']['reply_to'] ?? '') ?>"></div>
                </div>
            </div>
        </div>

        <!-- Payment Gateway -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="cil-credit-card me-2"></i>Payment Gateway</h5>
                    <button class="btn btn-primary btn-sm" onclick="showToast('PG disimpan')"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Provider</label>
                        <select class="form-select"><option>Midtrans</option><option>Xendit</option><option>DOKU</option></select></div>
                    <div class="mb-3"><label class="form-label">Server Key</label><input type="password" class="form-control" value="••••••••••"></div>
                    <div class="mb-3"><label class="form-label">Client Key</label><input type="password" class="form-control" value="••••••••••"></div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="cil-envelope-open me-2"></i>Notifikasi</h5>
                    <button class="btn btn-primary btn-sm" onclick="showToast('Notifikasi disimpan')"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">SendGrid API Key</label><input type="password" class="form-control" value="••••••••••"></div>
                    <div class="mb-3"><label class="form-label">Twilio Account SID</label><input type="password" class="form-control" value="••••••••••"></div>
                    <div class="mb-3"><label class="form-label">Twilio Auth Token</label><input type="password" class="form-control" value="••••••••••"></div>
                    <div class="mb-3"><label class="form-label">Twilio From Number</label><input class="form-control" value="+12025550123"></div>
                </div>
            </div>
        </div>

        <!-- Usage -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-chart-pie me-2"></i>Penggunaan (Pay‑as‑you‑go)</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-4"><div class="bg-light rounded p-3 text-center"><div class="fs-4 fw-bold">128</div><div class="text-medium-emphasis small">Email</div></div></div>
                        <div class="col-4"><div class="bg-light rounded p-3 text-center"><div class="fs-4 fw-bold">45</div><div class="text-medium-emphasis small">SMS</div></div></div>
                        <div class="col-4"><div class="bg-light rounded p-3 text-center"><div class="fs-4 fw-bold">128</div><div class="text-medium-emphasis small">QR</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
