<?php
/**
 * Siswa Dashboard
 * CBT Nusantara
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['siswa']);
verify_student_session($_SESSION['user_id'], $conn);

$page_title = 'Dashboard Siswa';
$siswa_id = $_SESSION['user_id'];

// Get siswa info + kelas
$stmt = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ?");
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ujian aktif untuk kelas siswa
$ujian_aktif = $conn->query("SELECT u.*, bs.nama_bank,
    su.status as sesi_status, su.id as sesi_id, su.nilai
    FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    JOIN ujian_kelas uk ON u.id = uk.ujian_id AND uk.kelas_id = {$siswa['kelas_id']}
    LEFT JOIN sesi_ujian su ON u.id = su.ujian_id AND su.siswa_id = $siswa_id
    WHERE u.status = 'aktif'
    ORDER BY u.tanggal_ujian DESC, u.jam_mulai ASC");

// Riwayat ujian selesai
$riwayat = $conn->query("SELECT u.nama_ujian, u.tanggal_ujian, su.nilai, su.waktu_selesai, su.exit_count
    FROM sesi_ujian su
    JOIN ujian u ON su.ujian_id = u.id
    WHERE su.siswa_id = $siswa_id AND su.status = 'selesai'
    ORDER BY su.waktu_selesai DESC LIMIT 10");

$show_score = get_setting('show_score_after_exam', $conn, '1');

include __DIR__ . '/../includes/header.php';
?>

<div class="main-wrapper">
    <div class="container py-4">
        <div class="page-header">
            <div>
                <h4>Selamat Datang, <?= e($siswa['nama']) ?>!</h4>
                <p class="text-muted mb-0">
                    <span class="badge bg-info"><?= e($siswa['nama_kelas']) ?></span>
                    <span class="ms-2 small"><?= e($siswa['username']) ?></span>
                </p>
            </div>
        </div>

        <?php render_flash(); ?>

        <!-- Ujian Aktif -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-file-alt me-2"></i>Ujian Tersedia
            </div>
            <div class="card-body">
                <?php if ($ujian_aktif->num_rows > 0): ?>
                <div class="row g-3">
                    <?php while ($u = $ujian_aktif->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="card border <?= $u['sesi_status'] === 'selesai' ? 'border-success' : ($u['sesi_status'] === 'sedang_ujian' ? 'border-warning' : 'border-primary') ?>">
                            <div class="card-body">
                                <h6 class="fw-bold"><?= e($u['nama_ujian']) ?></h6>
                                <p class="small text-muted mb-2"><?= e($u['nama_bank']) ?></p>
                                <div class="small mb-2">
                                    <i class="fas fa-calendar me-1"></i><?= format_tanggal($u['tanggal_ujian']) ?>
                                    <span class="ms-2"><i class="fas fa-clock me-1"></i><?= format_waktu($u['jam_mulai']) ?> - <?= format_waktu($u['jam_selesai']) ?></span>
                                </div>
                                <div class="small mb-3">
                                    <span class="badge bg-secondary"><?= $u['jumlah_soal'] ?> soal</span>
                                    <span class="badge bg-secondary"><?= $u['alokasi_waktu'] ?> menit</span>
                                </div>

                                <?php if ($u['sesi_status'] === 'selesai'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Selesai</span>
                                    <?php if ($show_score === '1' && $u['nilai'] !== null): ?>
                                    <span class="badge bg-primary ms-1">Nilai: <?= format_nilai($u['nilai']) ?></span>
                                    <?php endif; ?>
                                <?php elseif ($u['sesi_status'] === 'sedang_ujian'): ?>
                                    <a href="<?= base_url('siswa/ujian/soal.php?sesi_id=' . $u['sesi_id']) ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-play me-1"></i>Lanjutkan Ujian
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('siswa/ujian/token.php?ujian_id=' . $u['id']) ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-sign-in-alt me-1"></i>Masuk Ujian
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Tidak ada ujian yang tersedia saat ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="card">
            <div class="card-header"><i class="fas fa-history me-2"></i>Riwayat Ujian</div>
            <div class="card-body p-0">
                <?php if ($riwayat->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ujian</th>
                                <th>Tanggal</th>
                                <?php if ($show_score === '1'): ?>
                                <th>Nilai</th>
                                <?php endif; ?>
                                <th>Pelanggaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $riwayat->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($r['nama_ujian']) ?></td>
                                <td class="small"><?= format_datetime($r['waktu_selesai']) ?></td>
                                <?php if ($show_score === '1'): ?>
                                <td>
                                    <span class="fw-bold <?= ($r['nilai'] ?? 0) >= 70 ? 'text-success' : 'text-danger' ?>">
                                        <?= format_nilai($r['nilai']) ?>
                                    </span>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($r['exit_count'] > 0): ?>
                                    <span class="badge bg-danger"><?= $r['exit_count'] ?> exit</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Bersih</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>Belum ada riwayat ujian.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
