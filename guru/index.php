<?php
/**
 * Guru Dashboard
 * CBT Nusantara
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['guru']);

$page_title = 'Dashboard Guru';
$active_menu = 'dashboard';

$guru_id = $_SESSION['user_id'];

// Get guru info
$stmt = $conn->prepare("SELECT * FROM guru WHERE id = ?");
$stmt->bind_param("i", $guru_id);
$stmt->execute();
$guru = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats (own data only)
$my_bank_soal = $conn->query("SELECT COUNT(*) as c FROM bank_soal WHERE created_by_role = 'guru' AND created_by_id = $guru_id")->fetch_assoc()['c'];
$my_soal = $conn->query("SELECT COUNT(*) as c FROM soal s JOIN bank_soal bs ON s.bank_soal_id = bs.id WHERE bs.created_by_role = 'guru' AND bs.created_by_id = $guru_id")->fetch_assoc()['c'];
$my_ujian = $conn->query("SELECT COUNT(*) as c FROM ujian WHERE created_by_role = 'guru' AND created_by_id = $guru_id")->fetch_assoc()['c'];
$my_ujian_aktif = $conn->query("SELECT COUNT(*) as c FROM ujian WHERE created_by_role = 'guru' AND created_by_id = $guru_id AND status = 'aktif'")->fetch_assoc()['c'];

// Ujian terbaru
$ujian_terbaru = $conn->query("SELECT u.*, bs.kode_soal, bs.nama_bank,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id) as total_peserta,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'selesai') as sudah_selesai,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'sedang_ujian') as sedang_ujian
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    WHERE u.created_by_role = 'guru' AND u.created_by_id = $guru_id
    ORDER BY u.created_at DESC LIMIT 5");

// Bank soal terbaru
$bank_terbaru = $conn->query("SELECT bs.*,
    (SELECT COUNT(*) FROM soal s WHERE s.bank_soal_id = bs.id) as jumlah_soal
    FROM bank_soal bs
    WHERE bs.created_by_role = 'guru' AND bs.created_by_id = $guru_id
    ORDER BY bs.created_at DESC LIMIT 5");

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4>Selamat Datang, <?= e($guru['nama']) ?>!</h4>
                <p class="text-muted mb-0"><span class="badge bg-success">Guru</span></p>
            </div>
            <div>
                <a href="<?= base_url('guru/bank_soal/buat.php') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Buat Bank Soal
                </a>
            </div>
        </div>

        <?php render_flash(); ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-primary-gradient">
                    <div class="stat-number"><?= $my_bank_soal ?></div>
                    <div class="stat-label">Bank Soal</div>
                    <i class="fas fa-database stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-info-gradient">
                    <div class="stat-number"><?= $my_soal ?></div>
                    <div class="stat-label">Total Soal</div>
                    <i class="fas fa-question-circle stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success-gradient">
                    <div class="stat-number"><?= $my_ujian ?></div>
                    <div class="stat-label">Ujian</div>
                    <i class="fas fa-file-alt stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-warning-gradient">
                    <div class="stat-number"><?= $my_ujian_aktif ?></div>
                    <div class="stat-label">Ujian Aktif</div>
                    <i class="fas fa-play-circle stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Ujian Terbaru -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-alt me-2"></i>Ujian Saya</span>
                        <a href="<?= base_url('guru/ujian/index.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($ujian_terbaru->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Ujian</th>
                                        <th width="80">Status</th>
                                        <th width="100">Peserta</th>
                                        <th width="80">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($u = $ujian_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($u['nama_ujian']) ?></div>
                                            <small class="text-muted"><?= e($u['kode_soal']) ?> &middot; <?= format_tanggal($u['tanggal_ujian']) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $badge = match($u['status']) { 'draft' => 'bg-secondary', 'aktif' => 'bg-success', 'selesai' => 'bg-primary', default => 'bg-secondary' };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= e(ucfirst($u['status'])) ?></span>
                                        </td>
                                        <td class="small">
                                            <?= $u['sudah_selesai'] ?>/<?= $u['total_peserta'] ?>
                                            <?php if ($u['sedang_ujian'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= $u['sedang_ujian'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['status'] === 'draft'): ?>
                                            <a href="<?= base_url('guru/ujian/edit.php?id=' . $u['id'] . '&tab=kunci') ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <?php elseif ($u['status'] === 'aktif'): ?>
                                            <a href="<?= base_url('guru/ujian/monitoring.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-desktop"></i></a>
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
                            <p>Belum ada ujian. <a href="<?= base_url('guru/ujian/buat.php') ?>">Buat ujian pertama</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bank Soal Terbaru -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-database me-2"></i>Bank Soal Saya</span>
                        <a href="<?= base_url('guru/bank_soal/index.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($bank_terbaru->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Bank Soal</th>
                                        <th width="60">Soal</th>
                                        <th width="70">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($bs = $bank_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($bs['nama_bank']) ?></div>
                                            <small class="text-muted"><code><?= e($bs['kode_soal']) ?></code></small>
                                        </td>
                                        <td><span class="badge bg-info"><?= $bs['jumlah_soal'] ?></span></td>
                                        <td>
                                            <a href="<?= base_url('guru/soal/index.php?bank_id=' . $bs['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-list"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <p>Belum ada bank soal. <a href="<?= base_url('guru/bank_soal/buat.php') ?>">Buat bank soal</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../includes/footer.php'; ?>
