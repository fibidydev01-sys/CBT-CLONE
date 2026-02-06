<?php
/**
 * Soal - List (per Bank Soal)
 * Guru Panel (own bank soal only)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$active_menu = 'bank_soal';
$guru_id = $_SESSION['user_id'];
$bank_id = (int)($_GET['bank_id'] ?? 0);

// Verify bank ownership
$stmt = $conn->prepare("SELECT * FROM bank_soal WHERE id = ? AND created_by_role = 'guru' AND created_by_id = ?");
$stmt->bind_param("ii", $bank_id, $guru_id);
$stmt->execute();
$bank = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bank) {
    redirect(base_url('guru/bank_soal/index.php'), 'danger', 'Bank soal tidak ditemukan atau bukan milik Anda.');
}

$page_title = 'Soal - ' . $bank['kode_soal'];

$stmt = $conn->prepare("SELECT s.*,
    (SELECT COUNT(*) FROM opsi_jawaban oj WHERE oj.soal_id = s.id) as jumlah_opsi
    FROM soal s WHERE s.bank_soal_id = ? ORDER BY s.nomor_soal ASC");
$stmt->bind_param("i", $bank_id);
$stmt->execute();
$soal_list = $stmt->get_result();
$stmt->close();

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-list me-2"></i>Kelola Soal</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/bank_soal/index.php') ?>">Bank Soal</a></li>
                        <li class="breadcrumb-item active"><?= e($bank['kode_soal']) ?></li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('guru/soal/tambah.php?bank_id=' . $bank_id) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tambah Soal
            </a>
        </div>

        <?php render_flash(); ?>

        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-3"><small class="text-muted d-block">Kode</small><strong><code><?= e($bank['kode_soal']) ?></code></strong></div>
                    <div class="col-md-5"><small class="text-muted d-block">Nama Bank Soal</small><strong><?= e($bank['nama_bank']) ?></strong></div>
                    <div class="col-md-2"><small class="text-muted d-block">Total Soal</small><strong><span class="badge bg-primary"><?= $soal_list->num_rows ?> soal</span></strong></div>
                    <div class="col-md-2"><a href="<?= base_url('guru/bank_soal/edit.php?id=' . $bank_id) ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>Edit Bank</a></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Daftar Soal</div>
            <div class="card-body p-0">
                <?php if ($soal_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th width="60">No</th><th>Pertanyaan</th><th width="70">Tipe</th><th width="70">Opsi</th><th width="130">Aksi</th></tr></thead>
                        <tbody>
                            <?php while ($s = $soal_list->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= $s['nomor_soal'] ?></span></td>
                                <td><div class="text-truncate-2" style="max-width:500px;"><?= e(strip_tags($s['pertanyaan'])) ?></div></td>
                                <td><span class="badge <?= $s['tipe_soal'] === 'PG' ? 'bg-info' : 'bg-warning' ?>"><?= $s['tipe_soal'] ?></span></td>
                                <td><?= $s['jumlah_opsi'] ?></td>
                                <td>
                                    <a href="<?= base_url('guru/soal/edit.php?id=' . $s['id'] . '&bank_id=' . $bank_id) ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="<?= base_url('guru/soal/hapus.php?id=' . $s['id'] . '&bank_id=' . $bank_id) ?>" class="btn btn-sm btn-danger" title="Hapus" data-confirm="Yakin hapus soal nomor <?= $s['nomor_soal'] ?>?"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Belum ada soal. <a href="<?= base_url('guru/soal/tambah.php?bank_id=' . $bank_id) ?>">Tambah soal pertama</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
