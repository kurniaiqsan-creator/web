<?php ob_start(); ?>
<div class="container-lg py-4" style="max-width:42rem">
    <a href="<?= base_url('/events') ?>"
       class="text-medium-emphasis text-decoration-none small mb-4 d-inline-flex align-items-center gap-1">
        <i class="cil-arrow-left"></i> Kembali ke event
    </a>

    <?php if (empty($order)): ?>
        <div class="card">
            <div class="card-body text-center p-4">
                <h1 class="h5 fw-bold mb-1">Order tidak ditemukan</h1>
                <p class="small text-medium-emphasis">Order <span class="font-monospace"><?= View::e($orderCode) ?></span> tidak ditemukan.</p>
                <a href="<?= base_url('/events') ?>" class="btn btn-primary mt-2">Kembali</a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($order['status'] === 'paid'): ?>
        <div class="card text-center mb-4">
            <div class="card-body p-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3"
                     style="width:64px;height:64px">
                    <i class="cil-check-circle fs-1 text-success"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Pembayaran Berhasil!</h1>
                <p class="small text-medium-emphasis mb-3">
                    Order <span class="font-monospace fw-semibold"><?= View::e($orderCode) ?></span>
                </p>
                <div class="d-flex justify-content-center align-items-center gap-3">
                    <span class="badge text-bg-success">Lunas</span>
                    <span class="text-medium-emphasis"><?= View::formatRupiah($order['total_amount_cents']) ?></span>
                </div>
            </div>
        </div>

        <h2 class="h6 fw-semibold mb-3">
            <i class="cil-ticket me-1"></i>E-Ticket (<?= count($tickets) ?>)
        </h2>

        <div class="d-flex flex-column gap-3">
            <?php foreach ($tickets as $t): $ticketUrl = base_url('/t/' . $t['ticket_token']); ?>
                <div class="card overflow-hidden">
                    <div class="eticket-accent"></div>
                    <div class="card-body p-4">
                        <div class="text-center mb-3">
                            <h3 class="h5 fw-bold mb-1"><?= View::e($t['event_title']) ?></h3>
                            <div class="d-flex justify-content-center gap-3 small text-medium-emphasis">
                                <span><i class="cil-calendar me-1"></i><?= View::formatDate($t['start_time']) ?></span>
                                <span><i class="cil-location-pin me-1"></i><?= View::e($t['venue_name']) ?></span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mb-3">
                            <div class="rounded-3 border bg-white p-3">
                                <?php $qr = $qrByToken[$t['ticket_token']] ?? null; ?>
                                <?php if (!empty($qr)): ?>
                                    <img src="<?= View::e($qr) ?>" alt="QR Code Tiket" width="200" height="200" style="display:block">
                                <?php else: ?>
                                    <div class="qr-code" data-url="<?= View::e($ticketUrl) ?>"></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-2 small mb-3">
                            <div class="col-6">
                                <div class="rounded-3 bg-body-tertiary p-3 text-center">
                                    <div class="text-medium-emphasis small">Kursi</div>
                                    <div class="fs-5 fw-bold"><?= View::e($t['seat_label'] ?: 'GA') ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="rounded-3 bg-body-tertiary p-3 text-center">
                                    <div class="text-medium-emphasis small">Status</div>
                                    <span class="badge text-bg-success mt-1"><?= $t['status'] === 'valid' ? 'Valid' : View::e($t['status']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between small text-medium-emphasis px-1 mb-3">
                            <span>Order: <?= View::e($orderCode) ?></span>
                            <span class="font-monospace"><?= substr(View::e($t['ticket_token']), 0, 12) ?></span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= View::e($ticketUrl) ?>" target="_blank" class="btn btn-outline-primary btn-sm flex-grow-1">
                                <i class="cil-external-link me-1"></i>Buka E-Ticket
                            </a>
                            <button class="btn btn-outline-secondary btn-sm flex-grow-1" type="button"
                                    onclick="navigator.clipboard.writeText('<?= View::e($ticketUrl) ?>');showToast('Link disalin')">
                                <i class="cil-share me-1"></i>Bagikan
                            </button>
                        </div>
                    </div>
                    <div class="eticket-accent"></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($order['status'] === 'pending'): ?>
        <!-- PENDING: retry / cancel -->
        <div class="card text-center mb-4">
            <div class="card-body p-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mb-3"
                     style="width:64px;height:64px">
                    <i class="cil-clock fs-1 text-warning"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Menunggu Pembayaran</h1>
                <p class="small text-medium-emphasis mb-3">
                    Order <span class="font-monospace fw-semibold"><?= View::e($orderCode) ?></span>
                </p>
                <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                    <span class="badge text-bg-warning">Pending</span>
                    <span class="text-medium-emphasis"><?= View::formatRupiah($order['total_amount_cents']) ?></span>
                </div>

                <div class="d-flex flex-column gap-2" style="max-width:20rem;margin:0 auto">
                    <?php if (!empty($paymentUrl)): ?>
                        <a href="<?= View::e($paymentUrl) ?>" class="btn btn-primary"><i class="cil-credit-card me-1"></i>Lanjutkan Pembayaran</a>
                    <?php else: ?>
                        <div class="alert alert-warning small mb-0">Payment gateway belum dikonfigurasi. Hubungi penyelenggara.</div>
                    <?php endif; ?>

                    <?php if (!empty($sandbox)): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="simulatePay('<?= View::e($orderCode) ?>')">(Dev) Simulasi Bayar Sukses</button>
                    <?php endif; ?>

                    <form method="post" action="<?= base_url('/events/' . $order['event_id'] . '/cancel-order') ?>"
                          onsubmit="return confirm('Batalkan pesanan ini? Kursi akan dilepas.')">
                        <input type="hidden" name="order" value="<?= View::e($orderCode) ?>">
                        <button type="submit" class="btn btn-link text-danger btn-sm">Batalkan Pesanan</button>
                    </form>
                </div>
            </div>
        </div>
        <p class="text-center small text-medium-emphasis">Halaman ini akan menampilkan e-ticket otomatis setelah pembayaran dikonfirmasi.</p>

        <?php else: /* cancelled / failed / refunded */ ?>
        <div class="card text-center mb-4">
            <div class="card-body p-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 mb-3"
                     style="width:64px;height:64px">
                    <i class="cil-x-circle fs-1 text-secondary"></i>
                </div>
                <h1 class="h4 fw-bold mb-1">Pesanan <?= View::e(ucfirst($order['status'])) ?></h1>
                <p class="small text-medium-emphasis mb-3">
                    Order <span class="font-monospace fw-semibold"><?= View::e($orderCode) ?></span>
                </p>
                <a href="<?= base_url('/events/' . $order['event_id']) ?>" class="btn btn-primary mt-2">Pesan Lagi</a>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.qr-code').forEach(function (el) {
            new QRCode(el, { text: el.dataset.url, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
        });
    });
    async function simulatePay(code) {
        try {
            const res = await fetch(base_url('/api/v1/payments/simulate'), {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ order_code: code })
            });
            if (res.ok) { location.reload(); }
            else { const d = await res.json(); showToast((d.error && d.error.message) || 'Gagal simulasi', 'error'); }
        } catch (e) { showToast('Gagal simulasi', 'error'); }
    }
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
