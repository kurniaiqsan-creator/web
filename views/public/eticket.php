<?php ob_start(); ?>
<div class="container-lg d-flex align-items-center justify-content-center py-5" style="min-height:calc(100vh - 8rem)">
    <div style="width:100%;max-width:28rem">
        <div class="card overflow-hidden shadow-sm">
            <div class="eticket-accent"></div>
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <h1 class="h5 fw-bold mb-1"><?= View::e($ticket['event_title']) ?></h1>
                    <div class="d-flex justify-content-center gap-3 small text-medium-emphasis">
                        <span><i class="cil-calendar me-1"></i><?= View::formatDate($ticket['start_time'] ?? $ticket['issued_at'] ?? '') ?></span>
                        <span><i class="cil-location-pin me-1"></i><?= View::e($ticket['venue_name']) ?></span>
                    </div>
                </div>

                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-3 border bg-white p-3">
                        <?php if (!empty($qrDataUri)): ?>
                            <img src="<?= View::e($qrDataUri) ?>" alt="QR Code Tiket" width="200" height="200" style="display:block">
                        <?php else: ?>
                            <div id="qr-code" data-url="<?= View::e(base_url('/t/' . $ticket['ticket_token'])) ?>"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-2 small mb-3">
                    <div class="col-6">
                        <div class="rounded-3 bg-body-tertiary p-3 text-center">
                            <div class="text-medium-emphasis small">Kursi</div>
                            <div class="fs-5 fw-bold"><?= View::e($ticket['seat_label']) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-3 bg-body-tertiary p-3 text-center">
                            <div class="text-medium-emphasis small">Status</div>
                            <span class="badge text-bg-success mt-1"><?= $ticket['status'] === 'valid' ? 'Valid' : View::e($ticket['status']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between small text-medium-emphasis px-1 mb-3">
                    <span>Order: <?= View::e($ticket['order_code']) ?></span>
                    <span class="font-monospace"><?= substr(View::e($ticket['ticket_token']), 0, 12) ?></span>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm flex-grow-1" type="button" onclick="window.print()">
                        <i class="cil-print me-1"></i>Cetak / PDF
                    </button>
                    <button class="btn btn-outline-secondary btn-sm flex-grow-1" type="button"
                            onclick="navigator.clipboard.writeText(window.location.href);showToast('Link disalin')">
                        <i class="cil-share me-1"></i>Bagikan
                    </button>
                </div>
            </div>
            <div class="eticket-accent"></div>
        </div>
        <p class="mt-3 text-center small text-medium-emphasis">Tunjukkan QR code ini saat masuk venue</p>
    </div>
</div>
<?php if (empty($qrDataUri)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('qr-code');
        if (el) new QRCode(el, { text: el.dataset.url, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
    });
</script>
<?php endif; ?>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/main.php'; ?>
