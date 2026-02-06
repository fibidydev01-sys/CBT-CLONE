<?php
/**
 * Bank Soal - Buat
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Buat Bank Soal';
$active_menu = 'bank_soal';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_soal = strtoupper(trim($_POST['kode_soal'] ?? ''));
    $nama_bank = trim($_POST['nama_bank'] ?? '');

    if (empty($kode_soal) || empty($nama_bank)) {
        $error = 'Kode soal dan nama bank soal wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9._-]+$/', $kode_soal)) {
        $error = 'Kode soal hanya boleh huruf kapital, angka, titik, underscore, dan dash.';
    } else {
        // Cek duplikat kode
        $stmt = $conn->prepare("SELECT id FROM bank_soal WHERE kode_soal = ?");
        $stmt->bind_param("s", $kode_soal);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Kode soal sudah digunakan.';
        } else {
            $stmt->close();
            $role = $_SESSION['role'];
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO bank_soal (kode_soal, nama_bank, created_by_role, created_by_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $kode_soal, $nama_bank, $role, $user_id);

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                redirect(base_url('admin/soal/index.php?bank_id=' . $new_id), 'success',
                    'Bank soal "' . $kode_soal . '" berhasil dibuat. Silakan tambah soal.');
            } else {
                $error = 'Gagal membuat bank soal.';
            }
        }
        $stmt->close();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-plus me-2"></i>Buat Bank Soal</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/bank_soal/index.php') ?>">Bank Soal</a></li>
                        <li class="breadcrumb-item active">Buat</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Form Bank Soal Baru</div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kode Soal <span class="text-danger">*</span></label>
                                <input type="text" name="kode_soal" class="form-control" required
                                       placeholder="Contoh: MAT.IX.001"
                                       value="<?= e($_POST['kode_soal'] ?? '') ?>" autofocus
                                       style="text-transform: uppercase">
                                <div class="form-text">Format: MAPEL.KELAS.NOMOR (contoh: MAT.IX.001, IPA.VII.002)</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Bank Soal <span class="text-danger">*</span></label>
                                <input type="text" name="nama_bank" class="form-control" required
                                       placeholder="Contoh: Matematika Kelas IX - UAS Semester 1"
                                       value="<?= e($_POST['nama_bank'] ?? '') ?>">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Simpan & Kelola Soal
                                </button>
                                <a href="<?= base_url('admin/bank_soal/index.php') ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
