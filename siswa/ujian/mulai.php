<?php
/**
 * Siswa - Konfirmasi Mulai Ujian
 * CBT Nusantara
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['siswa']);
verify_student_session($_SESSION['user_id'], $conn);

$siswa_id = $_SESSION['user_id'];
$sesi_id = (int)($_GET['sesi_id'] ?? $_POST['sesi_id'] ?? 0);

// Get sesi + ujian
$stmt = $conn->prepare("SELECT su.*, u.nama_ujian, u.jumlah_soal, u.alokasi_waktu, u.acak_soal, u.acak_jawaban,
    u.bank_soal_id, bs.nama_bank
    FROM sesi_ujian su
    JOIN ujian u ON su.ujian_id = u.id
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    WHERE su.id = ? AND su.siswa_id = ?");
$stmt->bind_param("ii", $sesi_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    redirect(base_url('siswa/index.php'), 'danger', 'Sesi ujian tidak ditemukan.');
}

if ($sesi['status'] === 'selesai') {
    redirect(base_url('siswa/ujian/hasil.php?sesi_id=' . $sesi_id));
}

if ($sesi['status'] === 'sedang_ujian') {
    redirect(base_url('siswa/ujian/soal.php?sesi_id=' . $sesi_id));
}

// Process: Mulai ujian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $waktu_detik = $sesi['alokasi_waktu'] * 60;

    $stmt = $conn->prepare("UPDATE sesi_ujian SET status = 'sedang_ujian', waktu_mulai = NOW(), sisa_waktu = ? WHERE id = ?");
    $stmt->bind_param("ii", $waktu_detik, $sesi_id);
    $stmt->execute();
    $stmt->close();

    redirect(base_url('siswa/ujian/soal.php?sesi_id=' . $sesi_id));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Mulai Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .start-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 550px; width: 90%; }
        .rule-item { display: flex; align-items: flex-start; margin-bottom: 12px; }
        .rule-item i { margin-right: 10px; margin-top: 3px; color: #dc3545; }
    </style>
</head>
<body>
    <div class="start-card">
        <div class="text-center mb-4">
            <i class="fas fa-file-alt" style="font-size:3rem;color:#0d6efd;"></i>
            <h4 class="fw-bold mt-3"><?= e($sesi['nama_ujian']) ?></h4>
            <p class="text-muted"><?= e($sesi['nama_bank']) ?></p>
        </div>

        <div class="card bg-light mb-4">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-primary"><?= $sesi['jumlah_soal'] ?></div>
                        <small class="text-muted">Soal</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-primary"><?= $sesi['alokasi_waktu'] ?></div>
                        <small class="text-muted">Menit</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-primary">
                            <?php if ($sesi['acak_soal']): ?>Acak<?php else: ?>Urut<?php endif; ?>
                        </div>
                        <small class="text-muted">Soal</small>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="fw-bold mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Peraturan Ujian:</h6>
        <div class="rule-item"><i class="fas fa-times-circle"></i><span>Dilarang membuka tab/window lain selama ujian.</span></div>
        <div class="rule-item"><i class="fas fa-times-circle"></i><span>Dilarang klik kanan, copy text, atau buka DevTools.</span></div>
        <div class="rule-item"><i class="fas fa-times-circle"></i><span>Setiap pelanggaran akan mengurangi waktu ujian.</span></div>
        <div class="rule-item"><i class="fas fa-times-circle"></i><span>Melebihi batas pelanggaran = ujian otomatis dikumpulkan.</span></div>
        <div class="rule-item"><i class="fas fa-check-circle" style="color:#198754 !important;"></i><span>Jawaban otomatis tersimpan setiap kali dipilih.</span></div>

        <form method="POST" class="mt-4">
            <input type="hidden" name="sesi_id" value="<?= $sesi_id ?>">
            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" onclick="return confirm('Yakin mulai ujian? Timer akan langsung berjalan.')">
                <i class="fas fa-play me-2"></i>MULAI UJIAN
            </button>
            <a href="<?= base_url('siswa/index.php') ?>" class="btn btn-link text-muted d-block text-center">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </form>
    </div>
</body>
</html>
