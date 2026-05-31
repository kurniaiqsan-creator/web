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
                    <div class="mb-3"><label class="form-label">Judul Situs</label>
                        <input class="form-control" name="site_title" value="<?= View::e($branding['site_title'] ?? '') ?>" placeholder="Visi">
                        <div class="form-text">Muncul di judul tab browser, mis. "Beranda — <em>Judul Situs</em>".</div></div>
                    <div class="mb-3"><label class="form-label">Teks Footer</label>
                        <input class="form-control" name="footer_text" value="<?= View::e($branding['footer_text'] ?? '') ?>" placeholder="Visi — Platform Tiket">
                        <div class="form-text">Tampil di footer setelah "© <?= date('Y') ?>".</div></div>
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
                    <h5 class="card-title mb-0"><i class="cil-credit-card me-2"></i>Payment Gateway (Pakasir)</h5>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="cil-save me-1"></i>Simpan</button>
                </div>
                <div class="card-body">
                    <?php $pakasir = $payment['pakasir'] ?? []; ?>
                    <p class="small text-medium-emphasis">Dapatkan <strong>Slug</strong> & <strong>API Key</strong> dari halaman detail Proyek di <a href="https://app.pakasir.com" target="_blank" rel="noopener">app.pakasir.com</a>. Set Webhook URL proyek ke <code><?= View::e(base_url('/webhooks/payment')) ?></code>.</p>
                    <div class="mb-3"><label class="form-label">Project Slug</label>
                        <input class="form-control" name="pakasir_slug" value="<?= View::e((string)($pakasir['slug'] ?? '')) ?>" placeholder="contoh: acoustic-nights"></div>
                    <div class="mb-3"><label class="form-label">API Key</label>
                        <input type="password" class="form-control" name="pakasir_api_key" value="<?= View::e($mask((string)($pakasir['api_key'] ?? ''))) ?>" placeholder="Kosongkan untuk pertahankan nilai yang ada"></div>
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
                    <div class="mb-3"><label class="form-label">SMTP Host</label>
                        <input class="form-control" name="mail_host" value="<?= View::e((string)($notif['mail_host'] ?? 'smtp.gmail.com')) ?>" placeholder="smtp.gmail.com"></div>
                    <div class="mb-3"><label class="form-label">SMTP Port</label>
                        <input class="form-control" name="mail_port" value="<?= View::e((string)($notif['mail_port'] ?? '587')) ?>" placeholder="587"></div>
                    <div class="mb-3"><label class="form-label">SMTP Username</label>
                        <input class="form-control" name="mail_username" value="<?= View::e((string)($notif['mail_username'] ?? '')) ?>" placeholder="kamu@gmail.com"></div>
                    <div class="mb-3"><label class="form-label">SMTP Password (App Password)</label>
                        <input type="password" class="form-control" name="mail_password" value="<?= View::e($mask((string)($notif['mail_password'] ?? ''))) ?>" placeholder="App Password 16 digit">
                        <div class="form-text">Gmail: aktifkan 2FA lalu buat <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App Password</a>.</div></div>
                    <div class="mb-3"><label class="form-label">Email From</label>
                        <input class="form-control" name="mail_from" type="email" value="<?= View::e((string)($notif['mail_from'] ?? '')) ?>" placeholder="kamu@gmail.com"></div>
                    <div class="mb-3"><label class="form-label">From Name</label>
                        <input class="form-control" name="mail_from_name" value="<?= View::e((string)($notif['mail_from_name'] ?? 'Visi Tickets')) ?>" placeholder="Visi Tickets"></div>
                    <div class="mb-3"><label class="form-label">Fonnte Token (WhatsApp)</label>
                        <input type="password" class="form-control" name="fonnte_token" value="<?= View::e($mask((string)($notif['fonnte_token'] ?? ''))) ?>" placeholder="Token device dari dashboard Fonnte">
                        <div class="form-text">Token per device. Lihat di <a href="https://fonnte.com" target="_blank" rel="noopener">dashboard Fonnte</a>.</div></div>
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
