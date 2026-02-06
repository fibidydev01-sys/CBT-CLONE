<?php
/**
 * Profil Siswa
 * Siswa Panel
 *
 * Tampilkan info profil siswa yang login
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['siswa']);

$page_title = 'Profil Saya';
$siswa_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ?");
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    redirect(base_url('siswa/index.php'), 'danger', 'Data tidak ditemukan.');
}

// Exam stats
$exam_stats = $conn->query("SELECT
    COUNT(*) as total_ujian,
    COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai,
    ROUND(AVG(CASE WHEN nilai IS NOT NULL THEN nilai END), 1) as rata_rata,
    MAX(CASE WHEN nilai IS NOT NULL THEN nilai END) as nilai_max
    FROM sesi_ujian WHERE siswa_id = $siswa_id")->fetch_assoc();

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4" style="max-width: 700px;">
    <div class="mb-3">
        <a href="<?= base_url('siswa/index.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-user me-2"></i>Profil Saya</div>
        <div class="card-body">
            <div class="text-center mb-4">
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                    <i class="fas fa-user-graduate fa-2x text-white"></i>
                </div>
                <h5 class="mt-2 mb-0"><?= e($siswa['nama']) ?></h5>
                <span class="badge bg-info"><?= e($siswa['nama_kelas']) ?></span>
            </div>

            <table class="table table-borderless mb-0">
                <tr>
                    <td class="text-muted" width="150"><i class="fas fa-user me-2"></i>Nama</td>
                    <td class="fw-semibold"><?= e($siswa['nama']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="fas fa-at me-2"></i>Username</td>
                    <td><code><?= e($siswa['username']) ?></code></td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="fas fa-school me-2"></i>Kelas</td>
                    <td><?= e($siswa['nama_kelas']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="fas fa-calendar me-2"></i>Terdaftar</td>
                    <td><?= format_datetime($siswa['created_at']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted"><i class="fas fa-circle me-2"></i>Status</td>
                    <td><span class="badge <?= $siswa['is_active'] ? 'bg-success' : 'bg-danger' ?>"><?= $siswa['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Exam Stats -->
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Statistik Ujian</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-3">
                    <div class="fs-4 fw-bold text-primary"><?= $exam_stats['total_ujian'] ?></div>
                    <small class="text-muted">Total Ujian</small>
                </div>
                <div class="col-3">
                    <div class="fs-4 fw-bold text-success"><?= $exam_stats['selesai'] ?></div>
                    <small class="text-muted">Selesai</small>
                </div>
                <div class="col-3">
                    <div class="fs-4 fw-bold text-warning"><?= $exam_stats['rata_rata'] ?? '-' ?></div>
                    <small class="text-muted">Rata-rata</small>
                </div>
                <div class="col-3">
                    <div class="fs-4 fw-bold text-info"><?= $exam_stats['nilai_max'] !== null ? format_nilai($exam_stats['nilai_max']) : '-' ?></div>
                    <small class="text-muted">Tertinggi</small>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center">
        <a href="<?= base_url('siswa/profil/ubah_password.php') ?>" class="btn btn-warning"><i class="fas fa-key me-1"></i>Ubah Password</a>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
