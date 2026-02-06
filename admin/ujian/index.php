<?php
/**
 * Ujian - List
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Manajemen Ujian';
$active_menu = 'ujian';

$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
$types = '';

if (!empty($filter_status)) {
    $where[] = "u.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if (!empty($search)) {
    $where[] = "(u.nama_ujian LIKE ? OR bs.kode_soal LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $types .= 'ss';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ujian u JOIN bank_soal bs ON u.bank_soal_id = bs.id $where_sql");
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = paginate($total, 15);

$query = "SELECT u.*, bs.kode_soal, bs.nama_bank,
    (SELECT COUNT(*) FROM ujian_kelas uk WHERE uk.ujian_id = u.id) as jumlah_kelas,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id) as total_peserta,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'selesai') as sudah_selesai,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'sedang_ujian') as sedang_ujian
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    $where_sql
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$ujian_list = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-file-alt me-2"></i>Manajemen Ujian</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Ujian</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('admin/ujian/buat.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Buat Ujian
            </a>
        </div>

        <?php render_flash(); ?>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Semua --</option>
                            <option value="draft" <?= $filter_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="aktif" <?= $filter_status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Cari</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama ujian atau kode..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                        <a href="<?= base_url('admin/ujian/index.php') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Daftar Ujian (<?= $total ?>)</div>
            <div class="card-body p-0">
                <?php if ($ujian_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Ujian</th>
                                <th width="100">Tanggal</th>
                                <th width="90">Waktu</th>
                                <th width="70">Soal</th>
                                <th width="80">Status</th>
                                <th width="100">Peserta</th>
                                <th width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $pagination['offset'] + 1; while ($u = $ujian_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($u['nama_ujian']) ?></div>
                                    <small class="text-muted"><code><?= e($u['kode_soal']) ?></code> &middot; <?= $u['jumlah_kelas'] ?> kelas &middot; <?= $u['alokasi_waktu'] ?> menit</small>
                                </td>
                                <td class="small"><?= format_tanggal($u['tanggal_ujian']) ?></td>
                                <td class="small"><?= format_waktu($u['jam_mulai']) ?> - <?= format_waktu($u['jam_selesai']) ?></td>
                                <td><span class="badge bg-info"><?= $u['jumlah_soal'] ?></span></td>
                                <td>
                                    <?php
                                    $badge = match($u['status']) {
                                        'draft' => 'bg-secondary',
                                        'aktif' => 'bg-success',
                                        'selesai' => 'bg-primary',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= e(ucfirst($u['status'])) ?></span>
                                    <?php if ($u['sedang_ujian'] > 0): ?>
                                    <span class="badge bg-warning"><?= $u['sedang_ujian'] ?> online</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= $u['sudah_selesai'] ?>/<?= $u['total_peserta'] ?> selesai</td>
                                <td>
                                    <?php if ($u['status'] === 'draft'): ?>
                                    <a href="<?= base_url('admin/ujian/edit.php?id=' . $u['id']) ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="<?= base_url('admin/ujian/aktifkan.php?id=' . $u['id']) ?>" class="btn btn-sm btn-success" title="Aktifkan" data-confirm="Aktifkan ujian ini? Pastikan kunci jawaban sudah diatur."><i class="fas fa-play"></i></a>
                                    <?php elseif ($u['status'] === 'aktif'): ?>
                                    <a href="<?= base_url('admin/ujian/monitoring.php?id=' . $u['id']) ?>" class="btn btn-sm btn-info" title="Monitoring"><i class="fas fa-desktop"></i></a>
                                    <a href="<?= base_url('admin/ujian/aktifkan.php?id=' . $u['id'] . '&action=selesai') ?>" class="btn btn-sm btn-danger" title="Akhiri" data-confirm="Akhiri ujian ini? Semua sesi aktif akan dihentikan."><i class="fas fa-stop"></i></a>
                                    <?php else: ?>
                                    <a href="<?= base_url('admin/ujian/monitoring.php?id=' . $u['id']) ?>" class="btn btn-sm btn-info" title="Hasil"><i class="fas fa-chart-bar"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    <?php render_pagination($pagination, base_url('admin/ujian/index.php?status=' . urlencode($filter_status) . '&search=' . urlencode($search))); ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Belum ada ujian<?= $filter_status || $search ? ' yang sesuai filter' : '' ?>. <a href="<?= base_url('admin/ujian/buat.php') ?>">Buat ujian baru</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
