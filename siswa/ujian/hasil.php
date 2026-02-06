<?php
/**
 * Siswa - Hasil Ujian
 * CBT Nusantara
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['siswa']);

$siswa_id = $_SESSION['user_id'];
$sesi_id = (int)($_GET['sesi_id'] ?? 0);

// Get sesi + ujian
$stmt = $conn->prepare("SELECT su.*, u.nama_ujian, u.jumlah_soal, u.alokasi_waktu, bs.nama_bank
    FROM sesi_ujian su
    JOIN ujian u ON su.ujian_id = u.id
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    WHERE su.id = ? AND su.siswa_id = ? AND su.status = 'selesai'");
$stmt->bind_param("ii", $sesi_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    redirect(base_url('siswa/index.php'), 'danger', 'Hasil ujian tidak ditemukan.');
}

$show_score = get_setting('show_score_after_exam', $conn, '1');
$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

// Stats jawaban
$stats = $conn->query("SELECT
    COUNT(*) as total_jawab,
    SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as benar,
    SUM(CASE WHEN is_correct = 0 AND jawaban IS NOT NULL AND jawaban != '' THEN 1 ELSE 0 END) as salah
    FROM jawaban_siswa WHERE sesi_ujian_id = $sesi_id")->fetch_assoc();

$tidak_dijawab = $sesi['jumlah_soal'] - ($stats['total_jawab'] ?? 0);

// Durasi
$durasi = '-';
if ($sesi['waktu_mulai'] && $sesi['waktu_selesai']) {
    $diff = strtotime($sesi['waktu_selesai']) - strtotime($sesi['waktu_mulai']);
    $durasi = floor($diff / 60) . ' menit ' . ($diff % 60) . ' detik';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Ujian - <?= e($sesi['nama_ujian']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; min-height: 100vh; padding: 30px 15px; }
        .result-card { max-width: 600px; margin: 0 auto; }
        .score-circle { width: 150px; height: 150px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto 20px; font-weight: 800; }
        .score-good { background: linear-gradient(135deg, #198754, #20c997); color: white; }
        .score-bad { background: linear-gradient(135deg, #dc3545, #fd7e14); color: white; }
        .score-number { font-size: 2.5rem; line-height: 1; }
        .score-label { font-size: 0.8rem; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="result-card">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle text-success" style="font-size:3rem;"></i>
                <h4 class="fw-bold mt-3">Ujian Selesai!</h4>
                <p class="text-muted"><?= e($sesi['nama_ujian']) ?></p>

                <?php if ($show_score === '1' && $sesi['nilai'] !== null): ?>
                <div class="score-circle <?= $sesi['nilai'] >= 70 ? 'score-good' : 'score-bad' ?>">
                    <div class="score-number"><?= number_format($sesi['nilai'], 1) ?></div>
                    <div class="score-label">NILAI</div>
                </div>
                <?php else: ?>
                <div class="my-4">
                    <span class="badge bg-info fs-5 py-2 px-4">Ujian Telah Dikumpulkan</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detail -->
        <div class="card mt-3">
            <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2"></i>Detail</div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="fw-semibold" width="160">Ujian</td><td><?= e($sesi['nama_ujian']) ?></td></tr>
                    <tr><td class="fw-semibold">Bank Soal</td><td><?= e($sesi['nama_bank']) ?></td></tr>
                    <tr><td class="fw-semibold">Mulai</td><td><?= format_datetime($sesi['waktu_mulai']) ?></td></tr>
                    <tr><td class="fw-semibold">Selesai</td><td><?= format_datetime($sesi['waktu_selesai']) ?></td></tr>
                    <tr><td class="fw-semibold">Durasi</td><td><?= $durasi ?></td></tr>
                </table>
            </div>
        </div>

        <?php if ($show_score === '1'): ?>
        <div class="card mt-3">
            <div class="card-header fw-semibold"><i class="fas fa-chart-pie me-2"></i>Statistik Jawaban</div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-success"><?= $stats['benar'] ?? 0 ?></div>
                        <small class="text-muted">Benar</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-danger"><?= $stats['salah'] ?? 0 ?></div>
                        <small class="text-muted">Salah</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-secondary"><?= $tidak_dijawab ?></div>
                        <small class="text-muted">Kosong</small>
                    </div>
                </div>

                <div class="progress mt-3" style="height:8px;">
                    <?php
                    $total = $sesi['jumlah_soal'];
                    $pct_benar = $total > 0 ? (($stats['benar'] ?? 0) / $total * 100) : 0;
                    $pct_salah = $total > 0 ? (($stats['salah'] ?? 0) / $total * 100) : 0;
                    ?>
                    <div class="progress-bar bg-success" style="width:<?= $pct_benar ?>%"></div>
                    <div class="progress-bar bg-danger" style="width:<?= $pct_salah ?>%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($sesi['exit_count'] > 0): ?>
        <div class="card mt-3 border-warning">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <span class="ms-2">Pelanggaran tercatat: <strong><?= $sesi['exit_count'] ?> exit</strong>, Penalty: <strong><?= $sesi['penalty_time'] ?>s</strong></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-3 d-grid">
            <a href="<?= base_url('siswa/index.php') ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
