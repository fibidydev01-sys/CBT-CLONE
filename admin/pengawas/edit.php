<?php
/**
 * Pengawas - Edit
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Edit Pengawas';
$active_menu = 'pengawas';
$error = '';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM pengawas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pengawas = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pengawas) {
    redirect(base_url('admin/pengawas/index.php'), 'danger', 'Pengawas tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($username)) {
        $error = 'Nama dan username wajib diisi.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, titik, underscore, dan dash.';
    } else {
        // Cek duplikat (exclude self)
        $duplicate = false;
        foreach (['admin', 'guru', 'siswa'] as $table) {
            $stmt = $conn->prepare("SELECT id FROM `$table` WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) $duplicate = true;
            $stmt->close();
        }
        $stmt = $conn->prepare("SELECT id FROM pengawas WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $duplicate = true;
        $stmt->close();

        if ($duplicate) {
            $error = 'Username sudah digunakan.';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 4) {
                    $error = 'Password minimal 4 karakter.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE pengawas SET nama=?, username=?, password=? WHERE id=?");
                    $stmt->bind_param("sssi", $nama, $username, $hashed, $id);
                }
            }

            if (empty($error)) {
                if (empty($password)) {
                    $stmt = $conn->prepare("UPDATE pengawas SET nama=?, username=? WHERE id=?");
                    $stmt->bind_param("ssi", $nama, $username, $id);
                }

                if ($stmt->execute()) {
                    redirect(base_url('admin/pengawas/index.php'), 'success', 'Data pengawas "' . $nama . '" berhasil diupdate.');
                } else {
                    $error = 'Gagal mengupdate pengawas.';
                }
                $stmt->close();
            }
        }
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
                <h4><i class="fas fa-edit me-2"></i>Edit Pengawas</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/pengawas/index.php') ?>">Pengawas</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Edit: <?= e($pengawas['nama']) ?></div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" required
                                       value="<?= e($_POST['nama'] ?? $pengawas['nama']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required
                                       value="<?= e($_POST['username'] ?? $pengawas['username']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="text" name="password" class="form-control"
                                       placeholder="Kosongkan jika tidak ingin mengubah password">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                                <a href="<?= base_url('admin/pengawas/index.php') ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Info Pengawas</div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr><td class="fw-semibold" width="140">ID</td><td><?= $pengawas['id'] ?></td></tr>
                            <tr><td class="fw-semibold">Username</td><td><code><?= e($pengawas['username']) ?></code></td></tr>
                            <tr><td class="fw-semibold">Terdaftar</td><td class="small"><?= format_datetime($pengawas['created_at']) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
