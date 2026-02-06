<?php
/**
 * Ujian - Monitoring
 * Guru Panel (own ujian only)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$active_menu = 'ujian';
$guru_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// Verify ownership
$stmt = $conn->prepare("SELECT u.*, bs.kode_soal, bs.nama_bank FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    WHERE u.id = ? AND u.created_by_role = 'guru' AND u.created_by_id = ?");
$stmt->bind_param("ii", $id, $guru_id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ujian) {
    redirect(base_url('guru/ujian/index.php'), 'danger', 'Ujian tidak ditemukan atau bukan milik Anda.');
}

$page_title = 'Monitoring - ' . $ujian['nama_ujian'];

$stats = $conn->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'belum_mulai' THEN 1 ELSE 0 END) as belum_mulai,
    SUM(CASE WHEN status = 'sedang_ujian' THEN 1 ELSE 0 END) as sedang_ujian,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    ROUND(AVG(CASE WHEN nilai IS NOT NULL THEN nilai END), 1) as rata_rata
    FROM sesi_ujian WHERE ujian_id = $id")->fetch_assoc();

$sesi_list = $conn->query("SELECT
    su.id as sesi_id, su.status, su.waktu_mulai, su.sisa_waktu,
    su.exit_count, su.penalty_time, su.nilai,
    s.nama as nama_siswa, s.username,
    k.nama_kelas
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE su.ujian_id = $id
    ORDER BY
        CASE su.status WHEN 'sedang_ujian' THEN 1 WHEN 'belum_mulai' THEN 2 WHEN 'selesai' THEN 3 END,
        s.nama ASC");

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-desktop me-2"></i>Monitoring Ujian</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/ujian/index.php') ?>">Ujian</a></li>
                        <li class="breadcrumb-item active">Monitoring</li>
                    </ol>
                </nav>
            </div>
            <div>
                <span class="badge <?= $ujian['status'] === 'aktif' ? 'bg-success' : 'bg-primary' ?> fs-6"><?= e(ucfirst($ujian['status'])) ?></span>
                <?php if ($ujian['status'] === 'aktif'): ?>
                <a href="<?= base_url('guru/ujian/aktifkan.php?id=' . $id . '&action=selesai') ?>" class="btn btn-sm btn-danger ms-2" data-confirm="Akhiri ujian ini?">
                    <i class="fas fa-stop me-1"></i>Akhiri
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php render_flash(); ?>

        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col"><small class="text-muted d-block">Ujian</small><strong><?= e($ujian['nama_ujian']) ?></strong></div>
                    <div class="col"><small class="text-muted d-block">Bank Soal</small><code><?= e($ujian['kode_soal']) ?></code></div>
                    <div class="col"><small class="text-muted d-block">Tanggal</small><?= format_tanggal($ujian['tanggal_ujian']) ?></div>
                    <div class="col"><small class="text-muted d-block">Waktu</small><?= format_waktu($ujian['jam_mulai']) ?> - <?= format_waktu($ujian['jam_selesai']) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-2"><div class="stat-card bg-primary-gradient"><div class="stat-number"><?= $stats['total'] ?? 0 ?></div><div class="stat-label">Total</div></div></div>
            <div class="col-6 col-md-2"><div class="stat-card" style="background:#6c757d;color:white;"><div class="stat-number"><?= $stats['belum_mulai'] ?? 0 ?></div><div class="stat-label">Belum Mulai</div></div></div>
            <div class="col-6 col-md-2"><div class="stat-card bg-warning-gradient"><div class="stat-number"><?= $stats['sedang_ujian'] ?? 0 ?></div><div class="stat-label">Sedang Ujian</div></div></div>
            <div class="col-6 col-md-2"><div class="stat-card bg-success-gradient"><div class="stat-number"><?= $stats['selesai'] ?? 0 ?></div><div class="stat-label">Selesai</div></div></div>
            <div class="col-6 col-md-2"><div class="stat-card bg-info-gradient"><div class="stat-number"><?= $stats['rata_rata'] ?? '-' ?></div><div class="stat-label">Rata-rata</div></div></div>
            <div class="col-6 col-md-2"><div class="stat-card bg-danger-gradient"><div class="stat-number" id="clock"><?= date('H:i:s') ?></div><div class="stat-label">Waktu Server</div></div></div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Peserta</span>
                <?php if ($ujian['status'] === 'aktif'): ?>
                <span class="badge bg-success"><i class="fas fa-sync-alt fa-spin me-1"></i>Auto-refresh 10 detik</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th width="40">No</th><th>Siswa</th><th>Kelas</th><th width="110">Status</th><th width="90">Sisa Waktu</th><th width="60">Exit</th><th width="80">Penalty</th><th width="70">Nilai</th></tr></thead>
                        <tbody>
                            <?php $no = 1; while ($s = $sesi_list->fetch_assoc()): ?>
                            <tr class="<?= $s['status'] === 'sedang_ujian' ? 'table-warning' : ($s['status'] === 'selesai' ? '' : 'table-light') ?>">
                                <td><?= $no++ ?></td>
                                <td><div class="fw-semibold"><?= e($s['nama_siswa']) ?></div><small class="text-muted"><?= e($s['username']) ?></small></td>
                                <td><span class="badge bg-info"><?= e($s['nama_kelas']) ?></span></td>
                                <td>
                                    <?php
                                    $badge = match($s['status']) { 'belum_mulai' => 'bg-secondary', 'sedang_ujian' => 'bg-warning text-dark', 'selesai' => 'bg-success', default => 'bg-secondary' };
                                    $label = match($s['status']) { 'belum_mulai' => 'Belum Mulai', 'sedang_ujian' => 'Mengerjakan', 'selesai' => 'Selesai', default => $s['status'] };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $label ?></span>
                                </td>
                                <td>
                                    <?php if ($s['status'] === 'sedang_ujian' && $s['sisa_waktu']): ?>
                                    <?php $h = floor($s['sisa_waktu'] / 3600); $m = floor(($s['sisa_waktu'] % 3600) / 60); $sec = $s['sisa_waktu'] % 60; ?>
                                    <span class="<?= $s['sisa_waktu'] < 300 ? 'text-danger fw-bold' : '' ?>"><?= sprintf('%02d:%02d:%02d', $h, $m, $sec) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td><?php if ($s['exit_count'] > 0): ?><span class="badge bg-danger"><?= $s['exit_count'] ?></span><?php else: ?><span class="text-muted">0</span><?php endif; ?></td>
                                <td><?php if ($s['penalty_time'] > 0): ?><span class="text-danger"><?= $s['penalty_time'] ?>s</span><?php else: ?><span class="text-muted">0s</span><?php endif; ?></td>
                                <td>
                                    <?php if ($s['nilai'] !== null): ?>
                                    <span class="fw-bold <?= $s['nilai'] >= 70 ? 'text-success' : 'text-danger' ?>"><?= format_nilai($s['nilai']) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$auto_refresh = $ujian['status'] === 'aktif' ? "setInterval(function(){ location.reload(); }, 10000);" : "";
$inline_js = "
setInterval(function(){ document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID'); }, 1000);
$auto_refresh
";
include __DIR__ . '/../../includes/footer.php';
?>
