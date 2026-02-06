<?php
/**
 * Siswa - Tambah
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Tambah Siswa';
$active_menu = 'siswa';
$error = '';

// Get daftar kelas
$kelas_list = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);

    if (empty($nama) || empty($username) || empty($password) || $kelas_id <= 0) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, titik, underscore, dan dash.';
    } else {
        // Cek username duplikat di semua tabel user
        $duplicate = false;
        foreach (['admin', 'guru', 'pengawas', 'siswa'] as $table) {
            $stmt = $conn->prepare("SELECT id FROM `$table` WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $duplicate = true;
            }
            $stmt->close();
        }

        if ($duplicate) {
            $error = 'Username sudah digunakan.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO siswa (nama, kelas_id, username, password, password_plain) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisis", $nama, $kelas_id, $username, $hashed, $password);

            if ($stmt->execute()) {
                redirect(base_url('admin/siswa/index.php'), 'success', 'Siswa "' . $nama . '" berhasil ditambahkan.');
            } else {
                $error = 'Gagal menambahkan siswa.';
            }
            $stmt->close();
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
                <h4><i class="fas fa-plus me-2"></i>Tambah Siswa</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/siswa/index.php') ?>">Siswa</a></li>
                        <li class="breadcrumb-item active">Tambah</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Form Tambah Siswa</div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" required
                                       placeholder="Nama lengkap siswa"
                                       value="<?= e($_POST['nama'] ?? '') ?>" autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php while ($k = $kelas_list->fetch_assoc()): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($_POST['kelas_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                        <?= e($k['nama_kelas']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required
                                       placeholder="Username untuk login"
                                       value="<?= e($_POST['username'] ?? '') ?>">
                                <div class="form-text">Huruf, angka, titik, underscore, dash. Tanpa spasi.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="text" name="password" class="form-control" required
                                       placeholder="Password untuk login"
                                       value="<?= e($_POST['password'] ?? '') ?>">
                                <div class="form-text">Minimal 4 karakter. Password ini akan disimpan juga dalam bentuk plain text untuk recovery.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Simpan
                                </button>
                                <a href="<?= base_url('admin/siswa/index.php') ?>" class="btn btn-secondary">
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
