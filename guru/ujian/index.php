<?php
/**
 * Ujian - List
 * Guru Panel (own ujian only)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$page_title = 'Ujian Saya';
$active_menu = 'ujian';
$guru_id = $_SESSION['user_id'];

$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = "WHERE u.created_by_role = 'guru' AND u.created_by_id = $guru_id";
if ($filter_status && in_array($filter_status, ['draft', 'aktif', 'selesai'])) {
    $where .= " AND u.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND u.nama_ujian LIKE '%$search_esc%'";
}

$ujian_list = $conn->query("SELECT u.*, bs.kode_soal, bs.nama_bank,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id) as total_peserta,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'selesai') as sudah_selesai,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'sedang_ujian') as sedang_ujian
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    $where
    ORDER BY u.created_at DESC");

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-file-alt me-2"></i>Ujian Saya</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Ujian</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('guru/ujian/buat.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Buat Ujian
            </a>
        </div>

        <?php render_flash(); ?>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="draft" <?= $filter_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="aktif" <?= $filter_status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari ujian..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        <a href="<?= base_url('guru/ujian/index.php') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Daftar Ujian (<?= $ujian_list->num_rows ?>)</div>
            <div class="card-body p-0">
                <?php if ($ujian_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Ujian</th>
                                <th>Tanggal</th>
                                <th width="80">Status</th>
                                <th width="110">Peserta</th>
                                <th width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($u = $ujian_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($u['nama_ujian']) ?></div>
                                    <small class="text-muted"><?= e($u['kode_soal']) ?> &middot; <?= $u['jumlah_soal'] ?> soal &middot; <?= $u['alokasi_waktu'] ?> menit</small>
                                </td>
                                <td class="small">
                                    <?= format_tanggal($u['tanggal_ujian']) ?><br>
                                    <small class="text-muted"><?= format_waktu($u['jam_mulai']) ?> - <?= format_waktu($u['jam_selesai']) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $badge = match($u['status']) { 'draft' => 'bg-secondary', 'aktif' => 'bg-success', 'selesai' => 'bg-primary', default => 'bg-secondary' };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= e(ucfirst($u['status'])) ?></span>
                                    <?php if ($u['sedang_ujian'] > 0): ?>
                                    <span class="badge bg-warning text-dark"><?= $u['sedang_ujian'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= $u['sudah_selesai'] ?>/<?= $u['total_peserta'] ?> selesai</td>
                                <td>
                                    <?php if ($u['status'] === 'draft'): ?>
                                    <a href="<?= base_url('guru/ujian/edit.php?id=' . $u['id'] . '&tab=kunci') ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="<?= base_url('guru/ujian/aktifkan.php?id=' . $u['id']) ?>" class="btn btn-sm btn-success" title="Aktifkan" data-confirm="Aktifkan ujian ini?"><i class="fas fa-play"></i></a>
                                    <?php elseif ($u['status'] === 'aktif'): ?>
                                    <a href="<?= base_url('guru/ujian/monitoring.php?id=' . $u['id']) ?>" class="btn btn-sm btn-info" title="Monitor"><i class="fas fa-desktop"></i></a>
                                    <a href="<?= base_url('guru/ujian/aktifkan.php?id=' . $u['id'] . '&action=selesai') ?>" class="btn btn-sm btn-danger" title="Akhiri" data-confirm="Akhiri ujian ini?"><i class="fas fa-stop"></i></a>
                                    <?php elseif ($u['status'] === 'selesai'): ?>
                                    <a href="<?= base_url('guru/ujian/monitoring.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-info" title="Lihat Hasil"><i class="fas fa-chart-bar"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Belum ada ujian.<?= $filter_status || $search ? ' Coba ubah filter.' : '' ?></p>
                    <?php if (!$filter_status && !$search): ?>
                    <a href="<?= base_url('guru/ujian/buat.php') ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat Ujian</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
