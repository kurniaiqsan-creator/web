<?php
ob_start();
$branding = $tenant['branding'] ?? [];
$settings = $tenant['settings'] ?? [];
$payment  = $settings['payment'] ?? [];
$notif    = $settings['notifications'] ?? [];
$mask = fn(string $val): string => $val === '' ? '' : '••••••••••';
?>
<div>
    <div class="mb-4"><h1 class="fs-3 fw-bold mb-1">Pengaturan</h1><p class="text-medium-emphasis mb-0">Konfigurasi tenant & integrasi</p></div>

    <div class="row g-4">
        <!-- Branding -->
        <div class="col-lg-6">
            <form method="post" action="<?= base_url('/admin/settings') ?>" class="card" enctype="multipart/form-data">
                <input type="hidden" name="section" value="branding">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="cil-brush me-2"></i>Branding</h5>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Nama Tenant</label>
                        <input class="form-control" name="name" value="<?= View::e($tenant['name'] ?? '') ?>"></div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <?php if (!empty($branding['logo_url'])): ?>
                            <div class="mb-2">
                                <img src="<?= base_url(View::e($branding['logo_url'])) ?>" alt="Logo tenant"
                                     class="rounded border bg-body p-2" style="max-height:64px;max-width:200px;object-fit:contain">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
                        <div class="form-text">PNG, JPG, GIF, WEBP, atau SVG. Maks 2 MB.</div>
                    </div>
                    <div class="mb-3"><label class="form-label">Warna Brand</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" name="primary_color"
                                   value="<?= View::e($branding['primary_color'] ?? '#f97316') ?>"
                                   oninput="this.nextElementSibling.value=this.value">
                            <input class="form-control" name="primary_color_text"
                                   value="<?= View::e($branding['primary_color'] ?? '#f97316') ?>"
                                   oninput="this.previousElementSibling.value=this.value" pattern="^#[0-9a-fA-F]{6}$" readonly>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Email From</label>
                        <input class="form-control" name="email_from" type="email" value="<?= View::e($branding['email_from'] ?? '') ?>" placeholder="tickets@example.com"></div>
                    <div class="mb-3"><label class="form-label">Reply To</label>
                        <input class="form-control" name="reply_to" type="email" value="<?= View::e($branding['reply_to'] ?? '') ?>" placeholder="support@example.com"></div>
                </div>
            </form>
        </div>

        <!-- Payment Gateway -->
        <div class="col-lg-6">
            <form method="post" action="<?= base_url('/admin/settings') ?>" class="card">
                <input type="hidden" name="section" value="payment">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="cil-credit-card me-2"></i>Payment Gateway</h5>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Provider</label>
                        <select class="form-select" name="provider">
                            <?php foreach (['midtrans' => 'Midtrans', 'xendit' => 'Xendit', 'doku' => 'DOKU'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($payment['provider'] ?? 'midtrans') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Server Key</label>
                        <input type="password" class="form-control" name="server_key" value="<?= View::e($mask((string)($payment['server_key'] ?? ''))) ?>" placeholder="Kosongkan untuk pertahankan nilai yang ada"></div>
                    <div class="mb-3"><label class="form-label">Client Key</label>
                        <input type="password" class="form-control" name="client_key" value="<?= View::e($mask((string)($payment['client_key'] ?? ''))) ?>" placeholder="Kosongkan untuk pertahankan nilai yang ada"></div>
                </div>
            </form>
        </div>

        <!-- Notifications -->
        <div class="col-lg-6">
            <form method="post" action="<?= base_url('/admin/settings') ?>" class="card">
                <input type="hidden" name="section" value="notifications">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="cil-envelope-open me-2"></i>Notifikasi</h5>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">SendGrid API Key</label>
                        <input type="password" class="form-control" name="sendgrid_api_key" value="<?= View::e($mask((string)($notif['sendgrid_api_key'] ?? ''))) ?>" placeholder="SG.xxxx"></div>
                    <div class="mb-3"><label class="form-label">Twilio Account SID</label>
                        <input type="password" class="form-control" name="twilio_account_sid" value="<?= View::e($mask((string)($notif['twilio_account_sid'] ?? ''))) ?>"></div>
                    <div class="mb-3"><label class="form-label">Twilio Auth Token</label>
                        <input type="password" class="form-control" name="twilio_auth_token" value="<?= View::e($mask((string)($notif['twilio_auth_token'] ?? ''))) ?>"></div>
                    <div class="mb-3"><label class="form-label">Twilio From Number</label>
                        <input class="form-control" name="twilio_from" value="<?= View::e((string)($notif['twilio_from'] ?? '')) ?>" placeholder="+1XXXXXXXXXX"></div>
                </div>
            </form>
        </div>

        <!-- Usage (display only) -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="cil-chart-pie me-2"></i>Penggunaan (Pay‑as‑you‑go)</h5></div>
                <div class="card-body">
                    <p class="text-medium-emphasis small mb-3">Pelacakan usage belum diaktifkan. <a href="<?= base_url('/admin/reports') ?>">Lihat laporan</a>.</p>
                    <div class="row g-3">
                        <div class="col-4"><div class="bg-light rounded p-3 text-center"><div class="fs-4 fw-bold">—</div><div class="text-medium-emphasis small">Email</div></div></div>
                        <div class="col-4"><div class="bg-light rounded p-3 text-center"><div class="fs-4 fw-bold">—</div><div class="text-medium-emphasis small">SMS</div></div></div>
                        <div class="col-4"><div class="bg-light rounded p-3 text-center"><div class="fs-4 fw-bold">—</div><div class="text-medium-emphasis small">QR</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
