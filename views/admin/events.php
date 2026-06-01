<?php ob_start(); ?>
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-3 fw-bold mb-1">Event</h1>
            <p class="text-medium-emphasis mb-0">Kelola semua event kamu</p>
        </div>
        <a href="<?= base_url('/admin/events/create') ?>" class="btn btn-primary"><i class="cil-plus me-1"></i> Buat Event</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Event</th>
                            <th class="d-none d-md-table-cell">Venue</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= View::e($e['title']) ?></td>
                                <td class="d-none d-md-table-cell text-medium-emphasis"><?= View::e($e['venue_name']) ?></td>
                                <td class="text-medium-emphasis small"><?= date('d M Y, H:i', strtotime($e['start_time'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $e['status']==='published'?'success':'secondary' ?>"><?= $e['status']==='published'?'Published':'Draft' ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= base_url('/admin/events/' . $e['id']) ?>" class="btn btn-sm btn-outline-primary me-1"><i class="cil-pencil me-1"></i>Edit</a>
                                    <a href="<?= base_url('/events/' . $e['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="cil-external-link me-1"></i>View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($events)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-medium-emphasis">
                                <i class="cil-calendar fs-1 mb-2 d-block"></i> Belum ada event. Klik <strong>Buat Event</strong> untuk mulai.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require VIEW_PATH . '/layouts/admin.php'; ?>
