<?php
/**
 * Laporan - Dashboard
 * Admin Panel
 *
 * Daftar semua ujian dengan ringkasan hasil
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Laporan Hasil Ujian';
$active_menu = 'laporan';

$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = "WHERE u.status IN ('aktif','selesai')";
if ($filter_status && in_array($filter_status, ['aktif', 'selesai'])) {
    $where .= " AND u.status = '" . $conn->real_escape_string($filter_status) . "'";
}
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND u.nama_ujian LIKE '%$search_esc%'";
}

$ujian_list = $conn->query("SELECT u.*, bs.kode_soal, bs.nama_bank,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id) as total_peserta,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'selesai') as sudah_selesai,
    (SELECT ROUND(AVG(su.nilai), 1) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.nilai IS NOT NULL) as rata_rata,
    (SELECT MAX(su.nilai) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.nilai IS NOT NULL) as nilai_max,
    (SELECT MIN(su.nilai) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.nilai IS NOT NULL) as nilai_min,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.nilai >= 70) as lulus
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    $where
    ORDER BY u.tanggal_ujian DESC, u.jam_mulai DESC");

// Global stats
$global = $conn->query("SELECT
    COUNT(DISTINCT u.id) as total_ujian,
    COUNT(su.id) as total_sesi,
    ROUND(AVG(su.nilai), 1) as rata_rata_global,
    COUNT(CASE WHEN su.nilai >= 70 THEN 1 END) as total_lulus,
    COUNT(CASE WHEN su.nilai < 70 AND su.nilai IS NOT NULL THEN 1 END) as total_tidak_lulus
    FROM ujian u
    LEFT JOIN sesi_ujian su ON u.id = su.ujian_id AND su.nilai IS NOT NULL
    WHERE u.status IN ('aktif','selesai')")->fetch_assoc();

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-chart-pie me-2"></i>Laporan Hasil Ujian</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php render_flash(); ?>

        <!-- Global Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md">
                <div class="stat-card bg-primary-gradient">
                    <div class="stat-number"><?= $global['total_ujian'] ?? 0 ?></div>
                    <div class="stat-label">Total Ujian</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-info-gradient">
                    <div class="stat-number"><?= $global['total_sesi'] ?? 0 ?></div>
                    <div class="stat-label">Total Peserta</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-warning-gradient">
                    <div class="stat-number"><?= $global['rata_rata_global'] ?? '-' ?></div>
                    <div class="stat-label">Rata-rata</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-success-gradient">
                    <div class="stat-number"><?= $global['total_lulus'] ?? 0 ?></div>
                    <div class="stat-label">Lulus (>=70)</div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-danger-gradient">
                    <div class="stat-number"><?= $global['total_tidak_lulus'] ?? 0 ?></div>
                    <div class="stat-label">Tidak Lulus</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="aktif" <?= $filter_status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari ujian..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        <a href="<?= base_url('admin/laporan/index.php') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ujian List -->
        <div class="card">
            <div class="card-header">Daftar Ujian</div>
            <div class="card-body p-0">
                <?php if ($ujian_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Ujian</th>
                                <th width="80">Status</th>
                                <th width="100">Peserta</th>
                                <th width="80">Rata-rata</th>
                                <th width="80">Min/Max</th>
                                <th width="80">Lulus</th>
                                <th width="140">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($u = $ujian_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($u['nama_ujian']) ?></div>
                                    <small class="text-muted"><?= e($u['kode_soal']) ?> &middot; <?= format_tanggal($u['tanggal_ujian']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $u['status'] === 'aktif' ? 'bg-success' : 'bg-primary' ?>"><?= e(ucfirst($u['status'])) ?></span>
                                </td>
                                <td class="small"><?= $u['sudah_selesai'] ?>/<?= $u['total_peserta'] ?></td>
                                <td>
                                    <?php if ($u['rata_rata'] !== null): ?>
                                    <span class="fw-bold <?= $u['rata_rata'] >= 70 ? 'text-success' : 'text-danger' ?>"><?= $u['rata_rata'] ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if ($u['nilai_min'] !== null): ?>
                                    <?= format_nilai($u['nilai_min']) ?> / <?= format_nilai($u['nilai_max']) ?>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['total_peserta'] > 0): ?>
                                    <span class="badge bg-success"><?= $u['lulus'] ?></span>
                                    <span class="badge bg-danger"><?= $u['sudah_selesai'] - $u['lulus'] ?></span>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/laporan/detail_ujian.php?id=' . $u['id']) ?>" class="btn btn-sm btn-primary" title="Detail"><i class="fas fa-eye me-1"></i>Detail</a>
                                    <div class="btn-group btn-group-sm ms-1">
                                        <a href="<?= base_url('admin/laporan/export_excel.php?id=' . $u['id']) ?>" class="btn btn-success" title="Excel"><i class="fas fa-file-excel"></i></a>
                                        <a href="<?= base_url('admin/laporan/export_pdf.php?id=' . $u['id']) ?>" class="btn btn-danger" title="PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-pie"></i>
                    <p>Belum ada ujian dengan hasil.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
