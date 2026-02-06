<?php
/**
 * Pengawas Dashboard
 * CBT Nusantara
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['pengawas']);

$page_title = 'Dashboard Pengawas';
$active_menu = 'dashboard';

$pengawas_id = $_SESSION['user_id'];

// Get pengawas info
$stmt = $conn->prepare("SELECT * FROM pengawas WHERE id = ?");
$stmt->bind_param("i", $pengawas_id);
$stmt->execute();
$pengawas = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$total_ujian_aktif = $conn->query("SELECT COUNT(*) as c FROM ujian WHERE status = 'aktif'")->fetch_assoc()['c'];
$total_sedang_ujian = $conn->query("SELECT COUNT(*) as c FROM sesi_ujian WHERE status = 'sedang_ujian'")->fetch_assoc()['c'];
$total_selesai_hari_ini = $conn->query("SELECT COUNT(*) as c FROM sesi_ujian WHERE status = 'selesai' AND DATE(waktu_selesai) = CURDATE()")->fetch_assoc()['c'];
$total_pelanggaran = $conn->query("SELECT COUNT(*) as c FROM log_aktivitas WHERE aktivitas IN ('TAB_SWITCH','EXIT_PENALTY') AND DATE(created_at) = CURDATE()")->fetch_assoc()['c'];

// Token info
$current_token = get_setting('current_token', $conn, '');
$token_generated_at = get_setting('token_generated_at', $conn, '');
$token_duration = (int)get_setting('token_duration', $conn, '100');
$token_expired = false;
if (!empty($current_token) && !empty($token_generated_at)) {
    $expiry = strtotime($token_generated_at) + ($token_duration * 60);
    $token_expired = time() > $expiry;
}

// Ujian aktif
$ujian_aktif = $conn->query("SELECT u.*, bs.nama_bank,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id) as total_peserta,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'sedang_ujian') as sedang_ujian,
    (SELECT COUNT(*) FROM sesi_ujian su WHERE su.ujian_id = u.id AND su.status = 'selesai') as sudah_selesai
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    WHERE u.status = 'aktif'
    ORDER BY u.tanggal_ujian DESC, u.jam_mulai ASC");

// Pelanggaran terbaru
$pelanggaran_terbaru = $conn->query("SELECT la.*, su.id as sesi_id, s.nama as nama_siswa, s.username, k.nama_kelas
    FROM log_aktivitas la
    JOIN sesi_ujian su ON la.sesi_ujian_id = su.id
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE la.aktivitas IN ('TAB_SWITCH','EXIT_PENALTY','COPY_ATTEMPT','RIGHT_CLICK','DEVTOOLS')
    ORDER BY la.created_at DESC LIMIT 10");

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4>Selamat Datang, <?= e($pengawas['nama']) ?>!</h4>
                <p class="text-muted mb-0"><span class="badge bg-info">Pengawas</span></p>
            </div>
        </div>

        <?php render_flash(); ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-primary-gradient">
                    <div class="stat-number"><?= $total_ujian_aktif ?></div>
                    <div class="stat-label">Ujian Aktif</div>
                    <i class="fas fa-file-alt stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-warning-gradient">
                    <div class="stat-number"><?= $total_sedang_ujian ?></div>
                    <div class="stat-label">Sedang Ujian</div>
                    <i class="fas fa-user-clock stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success-gradient">
                    <div class="stat-number"><?= $total_selesai_hari_ini ?></div>
                    <div class="stat-label">Selesai Hari Ini</div>
                    <i class="fas fa-check-circle stat-icon"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-danger-gradient">
                    <div class="stat-number"><?= $total_pelanggaran ?></div>
                    <div class="stat-label">Pelanggaran Hari Ini</div>
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- Token Status -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-key me-2"></i>Status Token</span>
                <a href="<?= base_url('pengawas/token.php') ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-sync-alt me-1"></i>Kelola Token
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($current_token)): ?>
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <div class="fs-1 fw-bold font-monospace <?= $token_expired ? 'text-danger' : 'text-success' ?>" style="letter-spacing:8px;">
                            <?= e($current_token) ?>
                        </div>
                        <span class="badge <?= $token_expired ? 'bg-danger' : 'bg-success' ?> mt-1">
                            <?= $token_expired ? 'EXPIRED' : 'AKTIF' ?>
                        </span>
                    </div>
                    <div class="col-md-8">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="140">Dibuat</td><td><?= format_datetime($token_generated_at) ?></td></tr>
                            <tr><td class="text-muted">Durasi</td><td><?= $token_duration ?> menit</td></tr>
                            <tr><td class="text-muted">Expired</td><td><?= date('H:i:s', strtotime($token_generated_at) + ($token_duration * 60)) ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center text-muted py-3">
                    <i class="fas fa-key" style="font-size:2rem;opacity:0.3;"></i>
                    <p class="mt-2">Belum ada token aktif. <a href="<?= base_url('pengawas/token.php') ?>">Generate token baru</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ujian Aktif -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-play-circle me-2"></i>Ujian Aktif</div>
            <div class="card-body">
                <?php if ($ujian_aktif->num_rows > 0): ?>
                <div class="row g-3">
                    <?php while ($u = $ujian_aktif->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h6 class="fw-bold"><?= e($u['nama_ujian']) ?></h6>
                                <p class="small text-muted mb-2"><?= e($u['nama_bank']) ?></p>
                                <div class="small mb-2">
                                    <i class="fas fa-calendar me-1"></i><?= format_tanggal($u['tanggal_ujian']) ?>
                                    <span class="ms-2"><i class="fas fa-clock me-1"></i><?= format_waktu($u['jam_mulai']) ?> - <?= format_waktu($u['jam_selesai']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div>
                                        <span class="badge bg-warning text-dark"><?= $u['sedang_ujian'] ?> online</span>
                                        <span class="badge bg-success ms-1"><?= $u['sudah_selesai'] ?>/<?= $u['total_peserta'] ?> selesai</span>
                                    </div>
                                    <a href="<?= base_url('pengawas/monitoring.php?id=' . $u['id']) ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-desktop me-1"></i>Monitor
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Tidak ada ujian aktif saat ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pelanggaran Terbaru -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-triangle me-2"></i>Pelanggaran Terbaru</span>
                <a href="<?= base_url('pengawas/log_aktivitas.php') ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if ($pelanggaran_terbaru->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Aktivitas</th>
                                <th>Keterangan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $pelanggaran_terbaru->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($p['nama_siswa']) ?></td>
                                <td><span class="badge bg-info"><?= e($p['nama_kelas']) ?></span></td>
                                <td>
                                    <?php
                                    $act_badge = match($p['aktivitas']) {
                                        'TAB_SWITCH','EXIT_PENALTY' => 'bg-danger',
                                        'COPY_ATTEMPT' => 'bg-warning text-dark',
                                        'RIGHT_CLICK' => 'bg-secondary',
                                        'DEVTOOLS' => 'bg-dark',
                                        default => 'bg-info'
                                    };
                                    ?>
                                    <span class="badge <?= $act_badge ?>"><?= e($p['aktivitas']) ?></span>
                                </td>
                                <td class="small"><?= e($p['keterangan']) ?></td>
                                <td class="small"><?= format_datetime($p['created_at']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shield-alt"></i>
                    <p>Tidak ada pelanggaran tercatat hari ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../includes/footer.php'; ?>
