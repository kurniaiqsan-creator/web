<?php ob_start(); ?>
<div class="container-lg py-4" style="max-width:42rem">
    <a href="<?= base_url('/' . $tenant['slug']) ?>"
       class="text-medium-emphasis text-decoration-none small mb-4 d-inline-flex align-items-center gap-1">
        <i class="cil-arrow-left"></i> Kembali ke event
    </a>

    <?php if (empty($order)): ?>
        <div class="card">
            <div class="card-body text-center p-4">
                <h1 class="h5 fw-bold mb-1">Order tidak ditemukan</h1>
                <p class="small text-medium-emphasis">Order <span class="font-monospace"><?= View::e($orderCode) ?></span> tidak ditemukan.</p>
                <a href="<?= base_url('/' . $tenant['slug']) ?>" class="btn btn-primary mt-2">Kembali</a>
            </div>
        </div>
    <?php else: ?>
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
                                <div class="qr-code" data-url="<?= View::e($ticketUrl) ?>"></div>
                            </div>
                        </div>

                        <div class="row g-2 small mb-3">
                            <div class="col-6">
                                <div class="rounded-3 bg-body-tertiary p-3">
                                    <div class="text-medium-emphasis small">Kursi</div>
                                    <div class="fw-semibold"><?= View::e($t['seat_label'] ?: 'GA') ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="rounded-3 bg-body-tertiary p-3">
                                    <div class="text-medium-emphasis small">Status</div>
                                    <span class="badge text-bg-success mt-1"><?= $t['status'] === 'valid' ? 'Valid' : View::e($t['status']) ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="rounded-3 bg-body-tertiary p-3">
                                    <div class="text-medium-emphasis small">Token</div>
                                    <div class="font-monospace small text-break"><?= View::e($t['ticket_token']) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= View::e($ticketUrl) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="cil-external-link me-1"></i>Buka E-Ticket
                            </a>
                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                    onclick="navigator.clipboard.writeText('<?= View::e($ticketUrl) ?>');showToast('Link disalin')">
                                <i class="cil-share me-1"></i>Bagikan
                            </button>
                        </div>
                    </div>
                    <div class="eticket-accent"></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.qr-code').forEach(function (el) {
            new QRCode(el, { text: el.dataset.url, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
        });
    });
</script>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
