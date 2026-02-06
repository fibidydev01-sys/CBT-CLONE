<?php
/**
 * Kelas - Tambah
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Tambah Kelas';
$active_menu = 'kelas';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');

    if (empty($nama_kelas)) {
        $error = 'Nama kelas harus diisi.';
    } else {
        // Cek duplikat
        $stmt = $conn->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
        $stmt->bind_param("s", $nama_kelas);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Nama kelas sudah ada.';
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas) VALUES (?)");
            $stmt->bind_param("s", $nama_kelas);

            if ($stmt->execute()) {
                redirect(base_url('admin/kelas/index.php'), 'success', 'Kelas "' . $nama_kelas . '" berhasil ditambahkan.');
            } else {
                $error = 'Gagal menambahkan kelas.';
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
                <h4><i class="fas fa-plus me-2"></i>Tambah Kelas</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/kelas/index.php') ?>">Kelas</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Form Tambah Kelas</div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                                <input type="text" name="nama_kelas" class="form-control" required
                                       placeholder="Contoh: VII A, VIII B, IX C"
                                       value="<?= e($_POST['nama_kelas'] ?? '') ?>" autofocus>
                                <div class="form-text">Contoh format: VII A, VIII B, IX C, X IPA 1, dll.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Simpan
                                </button>
                                <a href="<?= base_url('admin/kelas/index.php') ?>" class="btn btn-secondary">
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
