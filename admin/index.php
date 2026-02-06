<?php
/**
 * Admin Dashboard
 * CBT Nusantara - Single School Edition
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Dashboard Admin';
$active_menu = 'dashboard';

// Stats: Total siswa
$result = $conn->query("SELECT COUNT(*) as total FROM siswa");
$total_siswa = $result->fetch_assoc()['total'];

// Stats: Total guru
$result = $conn->query("SELECT COUNT(*) as total FROM guru");
$total_guru = $result->fetch_assoc()['total'];

// Stats: Total kelas
$result = $conn->query("SELECT COUNT(*) as total FROM kelas");
$total_kelas = $result->fetch_assoc()['total'];

// Stats: Total bank soal
$result = $conn->query("SELECT COUNT(*) as total FROM bank_soal");
$total_bank_soal = $result->fetch_assoc()['total'];

// Stats: Total ujian
$result = $conn->query("SELECT COUNT(*) as total FROM ujian");
$total_ujian = $result->fetch_assoc()['total'];

// Stats: Ujian aktif
$result = $conn->query("SELECT COUNT(*) as total FROM ujian WHERE status = 'aktif'");
$ujian_aktif = $result->fetch_assoc()['total'];

// Stats: Siswa sedang ujian
$result = $conn->query("SELECT COUNT(*) as total FROM sesi_ujian WHERE status = 'sedang_ujian'");
$sedang_ujian = $result->fetch_assoc()['total'];

// Stats: Total pengawas
$result = $conn->query("SELECT COUNT(*) as total FROM pengawas");
$total_pengawas = $result->fetch_assoc()['total'];

// Ujian terbaru
$ujian_terbaru = $conn->query("SELECT u.*, bs.nama_bank,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id) as total_peserta,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'selesai') as sudah_selesai
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    ORDER BY u.created_at DESC LIMIT 5");

// Siswa terbaru
$siswa_terbaru = $conn->query("SELECT s.*, k.nama_kelas FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    ORDER BY s.created_at DESC LIMIT 5");

include __DIR__ . '/../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4>Dashboard</h4>
                <p class="text-muted mb-0">Selamat datang, <?= e($_SESSION['nama']) ?>!</p>
            </div>
        </div>

        <?php render_flash(); ?>

        <!-- Stat Cards Row 1 -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-primary-gradient">
                    <div class="stat-number"><?= $total_siswa ?></div>
                    <div class="stat-label">Total Siswa</div>
                    <i class="fas fa-user-graduate stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success-gradient">
                    <div class="stat-number"><?= $total_guru ?></div>
                    <div class="stat-label">Total Guru</div>
                    <i class="fas fa-chalkboard-teacher stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-info-gradient">
                    <div class="stat-number"><?= $total_kelas ?></div>
                    <div class="stat-label">Total Kelas</div>
                    <i class="fas fa-school stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-warning-gradient">
                    <div class="stat-number"><?= $total_pengawas ?></div>
                    <div class="stat-label">Pengawas</div>
                    <i class="fas fa-user-shield stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- Stat Cards Row 2 -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-info-gradient">
                    <div class="stat-number"><?= $total_bank_soal ?></div>
                    <div class="stat-label">Bank Soal</div>
                    <i class="fas fa-database stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success-gradient">
                    <div class="stat-number"><?= $total_ujian ?></div>
                    <div class="stat-label">Total Ujian</div>
                    <i class="fas fa-file-alt stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-danger-gradient">
                    <div class="stat-number"><?= $ujian_aktif ?></div>
                    <div class="stat-label">Ujian Aktif</div>
                    <i class="fas fa-play-circle stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-primary-gradient">
                    <div class="stat-number"><?= $sedang_ujian ?></div>
                    <div class="stat-label">Sedang Ujian</div>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Ujian Terbaru -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-alt me-2"></i>Ujian Terbaru</span>
                        <a href="<?= base_url('admin/ujian/index.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($ujian_terbaru->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama Ujian</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Peserta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($u = $ujian_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($u['nama_ujian']) ?></div>
                                            <small class="text-muted"><?= e($u['nama_bank']) ?></small>
                                        </td>
                                        <td class="small"><?= format_tanggal($u['tanggal_ujian']) ?></td>
                                        <td>
                                            <?php
                                            $badge_class = match($u['status']) {
                                                'draft' => 'bg-secondary',
                                                'aktif' => 'bg-success',
                                                'selesai' => 'bg-primary',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= e(ucfirst($u['status'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-muted"><?= $u['sudah_selesai'] ?>/<?= $u['total_peserta'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>Belum ada ujian dibuat</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Siswa Terbaru -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-graduate me-2"></i>Siswa Terbaru</span>
                        <a href="<?= base_url('admin/siswa/index.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($siswa_terbaru->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($s = $siswa_terbaru->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($s['nama']) ?></div>
                                            <small class="text-muted"><?= e($s['username']) ?></small>
                                        </td>
                                        <td><span class="badge bg-info"><?= e($s['nama_kelas']) ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <p>Belum ada siswa terdaftar</p>
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
