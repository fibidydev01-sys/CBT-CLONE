<?php
/**
 * Siswa - Input Token Ujian
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
$ujian_id = (int)($_GET['ujian_id'] ?? $_POST['ujian_id'] ?? 0);

// Get ujian
$stmt = $conn->prepare("SELECT u.*, bs.nama_bank FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id WHERE u.id = ? AND u.status = 'aktif'");
$stmt->bind_param("i", $ujian_id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ujian) {
    redirect(base_url('siswa/index.php'), 'danger', 'Ujian tidak ditemukan atau belum aktif.');
}

// Cek apakah siswa punya sesi
$stmt = $conn->prepare("SELECT * FROM sesi_ujian WHERE ujian_id = ? AND siswa_id = ?");
$stmt->bind_param("ii", $ujian_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    redirect(base_url('siswa/index.php'), 'danger', 'Anda tidak terdaftar untuk ujian ini.');
}

// Jika sudah selesai
if ($sesi['status'] === 'selesai') {
    redirect(base_url('siswa/ujian/hasil.php?sesi_id=' . $sesi['id']), 'info', 'Anda sudah menyelesaikan ujian ini.');
}

// Jika sedang ujian, langsung lanjut
if ($sesi['status'] === 'sedang_ujian') {
    redirect(base_url('siswa/ujian/soal.php?sesi_id=' . $sesi['id']));
}

$token_enabled = get_setting('token_enabled', $conn, '1');
$page_title = 'Masuk Ujian';
$error = '';

// Jika token disabled, langsung ke mulai
if ($token_enabled !== '1') {
    redirect(base_url('siswa/ujian/mulai.php?sesi_id=' . $sesi['id']));
}

// Process token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_token = trim($_POST['token'] ?? '');

    if (empty($input_token)) {
        $error = 'Token wajib diisi.';
    } else {
        $current_token = get_setting('current_token', $conn, '');
        $generated_at = get_setting('token_generated_at', $conn, '');
        $duration = (int)get_setting('token_duration', $conn, '100');

        if (empty($current_token)) {
            $error = 'Token belum digenerate. Hubungi pengawas/admin.';
        } elseif ($input_token !== $current_token) {
            $error = 'Token salah. Periksa kembali token dari pengawas.';
        } else {
            // Cek expiry
            if (!empty($generated_at)) {
                $expiry = strtotime($generated_at) + ($duration * 60);
                if (time() > $expiry) {
                    $error = 'Token sudah expired. Minta token baru dari pengawas.';
                }
            }

            if (empty($error)) {
                // Token valid, update sesi
                $stmt = $conn->prepare("UPDATE sesi_ujian SET token_used = ? WHERE id = ?");
                $stmt->bind_param("si", $input_token, $sesi['id']);
                $stmt->execute();
                $stmt->close();

                redirect(base_url('siswa/ujian/mulai.php?sesi_id=' . $sesi['id']));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Input Token - <?= e($ujian['nama_ujian']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .token-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 450px; width: 90%; text-align: center; }
        .token-input { font-size: 2rem; text-align: center; letter-spacing: 10px; font-weight: 700; border-radius: 12px; border: 3px solid #dee2e6; padding: 15px; }
        .token-input:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.15); }
    </style>
</head>
<body>
    <div class="token-card">
        <div class="mb-4">
            <i class="fas fa-key" style="font-size:3rem;color:#0d6efd;"></i>
        </div>
        <h5 class="fw-bold mb-1"><?= e($ujian['nama_ujian']) ?></h5>
        <p class="text-muted small mb-4"><?= e($ujian['nama_bank']) ?> &middot; <?= $ujian['jumlah_soal'] ?> soal &middot; <?= $ujian['alokasi_waktu'] ?> menit</p>

        <?php if ($error): ?>
        <div class="alert alert-danger small"><i class="fas fa-exclamation-circle me-1"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="ujian_id" value="<?= $ujian_id ?>">

            <div class="mb-4">
                <label class="form-label fw-semibold">Masukkan Token</label>
                <input type="text" name="token" class="form-control token-input" maxlength="6"
                       placeholder="______" required autofocus autocomplete="off"
                       value="<?= e($_POST['token'] ?? '') ?>">
                <div class="form-text">Minta token 6 digit dari pengawas ujian.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Masuk Ujian
            </button>

            <a href="<?= base_url('siswa/index.php') ?>" class="btn btn-link text-muted">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
