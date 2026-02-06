<?php
/**
 * Bank Soal - Edit
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Edit Bank Soal';
$active_menu = 'bank_soal';
$error = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM bank_soal WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$bank = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bank) {
    redirect(base_url('admin/bank_soal/index.php'), 'danger', 'Bank soal tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_soal = strtoupper(trim($_POST['kode_soal'] ?? ''));
    $nama_bank = trim($_POST['nama_bank'] ?? '');

    if (empty($kode_soal) || empty($nama_bank)) {
        $error = 'Kode soal dan nama bank soal wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9._-]+$/', $kode_soal)) {
        $error = 'Kode soal hanya boleh huruf kapital, angka, titik, underscore, dan dash.';
    } else {
        // Cek duplikat (exclude current)
        $stmt = $conn->prepare("SELECT id FROM bank_soal WHERE kode_soal = ? AND id != ?");
        $stmt->bind_param("si", $kode_soal, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Kode soal sudah digunakan.';
        } else {
            $stmt->close();
            $stmt = $conn->prepare("UPDATE bank_soal SET kode_soal = ?, nama_bank = ? WHERE id = ?");
            $stmt->bind_param("ssi", $kode_soal, $nama_bank, $id);

            if ($stmt->execute()) {
                redirect(base_url('admin/bank_soal/index.php'), 'success', 'Bank soal berhasil diupdate.');
            } else {
                $error = 'Gagal mengupdate bank soal.';
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
                <h4><i class="fas fa-edit me-2"></i>Edit Bank Soal</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/bank_soal/index.php') ?>">Bank Soal</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Edit: <?= e($bank['kode_soal']) ?></div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kode Soal <span class="text-danger">*</span></label>
                                <input type="text" name="kode_soal" class="form-control" required
                                       value="<?= e($_POST['kode_soal'] ?? $bank['kode_soal']) ?>"
                                       style="text-transform: uppercase">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Bank Soal <span class="text-danger">*</span></label>
                                <input type="text" name="nama_bank" class="form-control" required
                                       value="<?= e($_POST['nama_bank'] ?? $bank['nama_bank']) ?>">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                                <a href="<?= base_url('admin/soal/index.php?bank_id=' . $id) ?>" class="btn btn-info">
                                    <i class="fas fa-list me-1"></i>Kelola Soal
                                </a>
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
