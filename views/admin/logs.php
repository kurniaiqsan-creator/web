<?php ob_start(); ?>
<?php
$tabs = [
    'notifications' => ['Notifikasi', 'cil-envelope-closed'],
    'webhooks'      => ['Webhook Pembayaran', 'cil-link'],
    'scans'         => ['Scan Tiket', 'cil-qr-code'],
];
$statusBadge = [
    'sent' => 'success', 'pending' => 'warning', 'failed' => 'danger',
];
$scanBadge = [
    'validated' => 'success', 'already_used' => 'warning', 'invalid' => 'danger',
];
$fmt = fn(?string $d) => $d ? date('d M Y, H:i:s', strtotime($d)) : '—';
?>
<div>
    <div class="mb-4">
        <h1 class="fs-3 fw-bold mb-1">Log &amp; Audit</h1>
        <p class="text-medium-emphasis mb-0">Riwayat notifikasi, webhook pembayaran, dan scan tiket.</p>
    </div>

    <ul class="nav nav-tabs mb-3">
        <?php foreach ($tabs as $key => [$label, $icon]): ?>
            <li class="nav-item">
                <a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="<?= base_url('/admin/logs?tab=' . $key) ?>">
                    <i class="<?= $icon ?> me-1"></i><?= $label ?>
                    <?php if ($key === 'notifications' && ($counts['notif_failed'] ?? 0) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= (int)$counts['notif_failed'] ?></span>
                    <?php endif; ?>
                    <?php if ($key === 'webhooks' && ($counts['webhook_failed'] ?? 0) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= (int)$counts['webhook_failed'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">

            <?php if ($tab === 'notifications'): ?>
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th class="ps-4">Waktu</th><th>Channel</th><th>Penerima</th>
                        <th>Order</th><th>Status</th><th>Keterangan</th><th class="text-end pe-4">Aksi</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($notifications as $n): ?>
                        <tr>
                            <td class="ps-4 small text-medium-emphasis"><?= $fmt($n['created_at']) ?></td>
                            <td>
                                <?php if ($n['channel'] === 'email'): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis"><i class="cil-envelope-closed me-1"></i>Email</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success-emphasis"><i class="cib-whatsapp me-1"></i>WhatsApp</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= View::e($n['recipient']) ?></td>
                            <td>
                                <?php if (!empty($n['order_code'])): ?>
                                    <span class="font-monospace small"><?= View::e($n['order_code']) ?></span>
                                <?php else: ?><span class="text-medium-emphasis">—</span><?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= $statusBadge[$n['status']] ?? 'secondary' ?>"><?= ucfirst((string)$n['status']) ?></span>
                                <?php if ((int)$n['attempts'] > 1): ?><span class="small text-medium-emphasis ms-1"><?= (int)$n['attempts'] ?>x</span><?php endif; ?>
                            </td>
                            <td class="small text-danger" style="max-width:260px"><?= View::e((string)($n['last_error'] ?? '')) ?></td>
                            <td class="text-end pe-4">
                                <?php if ($n['status'] !== 'sent'): ?>
                                    <form method="post" action="<?= base_url('/admin/logs/notifications/' . $n['id'] . '/retry') ?>" class="d-inline">
                                        <button class="btn btn-sm btn-outline-primary" type="submit"><i class="cil-reload me-1"></i>Kirim ulang</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-success small"><i class="cil-check-alt"></i> <?= $fmt($n['sent_at']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($notifications)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-medium-emphasis"><i class="cil-envelope-open fs-1 d-block mb-2"></i>Belum ada notifikasi terkirim.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($tab === 'webhooks'): ?>
                <div class="px-4 pt-3"><p class="small text-medium-emphasis mb-2"><i class="cil-info me-1"></i>Webhook pembayaran bersifat global (lintas tenant). Verifikasi via Pakasir Transaction Detail API.</p></div>
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th class="ps-4">Waktu</th><th>Provider</th><th>Endpoint</th>
                        <th>HTTP</th><th>Verifikasi</th><th class="pe-4">Payload</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($webhooks as $w):
                        $vr = json_decode((string)($w['verification_result'] ?? ''), true);
                        $verified = is_array($vr) ? ($vr['verified'] ?? null) : null;
                    ?>
                        <tr>
                            <td class="ps-4 small text-medium-emphasis"><?= $fmt($w['created_at']) ?></td>
                            <td><span class="text-uppercase small fw-semibold"><?= View::e((string)$w['provider']) ?></span></td>
                            <td class="small font-monospace"><?= View::e((string)$w['endpoint']) ?></td>
                            <td>
                                <span class="badge bg-<?= (int)$w['success'] === 1 ? 'success' : 'danger' ?>">
                                    <?= (int)$w['success'] === 1 ? 'OK' : 'FAIL' ?>
                                </span>
                                <span class="small text-medium-emphasis"><?= (int)($w['response_code'] ?? 0) ?></span>
                            </td>
                            <td>
                                <?php if ($verified === true): ?><span class="badge bg-success-subtle text-success-emphasis">Verified</span>
                                <?php elseif ($verified === false): ?><span class="badge bg-warning-subtle text-warning-emphasis">Unverified</span>
                                <?php else: ?><span class="text-medium-emphasis small">—</span><?php endif; ?>
                            </td>
                            <td class="pe-4" style="max-width:320px">
                                <code class="small text-truncate d-inline-block" style="max-width:300px"><?= View::e(mb_substr((string)$w['raw_request'], 0, 200)) ?></code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($webhooks)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-medium-emphasis"><i class="cil-link-broken fs-1 d-block mb-2"></i>Belum ada webhook diterima.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

            <?php else: /* scans */ ?>
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th class="ps-4">Waktu</th><th>Hasil</th><th>Event</th>
                        <th>Kursi</th><th>Order</th><th>Scanner</th><th class="pe-4">Oleh</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($scans as $s): ?>
                        <tr>
                            <td class="ps-4 small text-medium-emphasis"><?= $fmt($s['scanned_at']) ?></td>
                            <td><span class="badge bg-<?= $scanBadge[$s['result']] ?? 'secondary' ?>"><?= View::e((string)$s['result']) ?></span></td>
                            <td class="small"><?= View::e((string)($s['event_title'] ?? '—')) ?></td>
                            <td><span class="fw-semibold"><?= View::e((string)($s['seat_label'] ?: 'GA')) ?></span></td>
                            <td><span class="font-monospace small"><?= View::e((string)($s['order_code'] ?? '')) ?></span></td>
                            <td class="small text-medium-emphasis"><?= View::e((string)($s['scanner_id'] ?? '—')) ?></td>
                            <td class="pe-4 small"><?= View::e((string)($s['scanned_by'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($scans)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-medium-emphasis"><i class="cil-qr-code fs-1 d-block mb-2"></i>Belum ada scan tiket.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
