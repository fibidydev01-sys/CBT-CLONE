<?php
/**
 * Bank Soal - List (Guru Only)
 * Guru Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$page_title = 'Bank Soal Saya';
$active_menu = 'bank_soal';
$guru_id = $_SESSION['user_id'];

$search = trim($_GET['q'] ?? '');

$where = "WHERE bs.created_by_role = 'guru' AND bs.created_by_id = $guru_id";
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND (bs.kode_soal LIKE '%$search_esc%' OR bs.nama_bank LIKE '%$search_esc%')";
}

$bank_list = $conn->query("SELECT bs.*,
    (SELECT COUNT(*) FROM soal s WHERE s.bank_soal_id = bs.id) as jumlah_soal,
    (SELECT COUNT(*) FROM ujian u WHERE u.bank_soal_id = bs.id) as jumlah_ujian
    FROM bank_soal bs
    $where
    ORDER BY bs.created_at DESC");

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-database me-2"></i>Bank Soal Saya</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Bank Soal</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('guru/bank_soal/buat.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Buat Bank Soal
            </a>
        </div>

        <?php render_flash(); ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Bank Soal</span>
                <form method="GET" class="d-flex" style="max-width:280px;">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari..." value="<?= e($search) ?>">
                </form>
            </div>
            <div class="card-body p-0">
                <?php if ($bank_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Kode</th>
                                <th>Nama Bank Soal</th>
                                <th width="70">Soal</th>
                                <th width="70">Ujian</th>
                                <th width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($bs = $bank_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code><?= e($bs['kode_soal']) ?></code></td>
                                <td class="fw-semibold"><?= e($bs['nama_bank']) ?></td>
                                <td><span class="badge bg-info"><?= $bs['jumlah_soal'] ?></span></td>
                                <td><span class="badge bg-primary"><?= $bs['jumlah_ujian'] ?></span></td>
                                <td>
                                    <a href="<?= base_url('guru/soal/index.php?bank_id=' . $bs['id']) ?>" class="btn btn-sm btn-info" title="Kelola Soal"><i class="fas fa-list"></i></a>
                                    <a href="<?= base_url('guru/bank_soal/edit.php?id=' . $bs['id']) ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <?php if ($bs['jumlah_ujian'] == 0): ?>
                                    <a href="<?= base_url('guru/bank_soal/hapus.php?id=' . $bs['id']) ?>" class="btn btn-sm btn-danger" title="Hapus" data-confirm="Hapus bank soal ini?"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-database"></i>
                    <p>Belum ada bank soal.<?= $search ? ' Coba ubah kata kunci pencarian.' : '' ?></p>
                    <?php if (!$search): ?>
                    <a href="<?= base_url('guru/bank_soal/buat.php') ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat Bank Soal</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
