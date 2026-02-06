<?php
/**
 * Siswa - Edit
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Edit Siswa';
$active_menu = 'siswa';
$error = '';

// Get siswa data
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    redirect(base_url('admin/siswa/index.php'), 'danger', 'Siswa tidak ditemukan.');
}

// Get daftar kelas
$kelas_list = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;

    if (empty($nama) || empty($username) || $kelas_id <= 0) {
        $error = 'Nama, username, dan kelas wajib diisi.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, titik, underscore, dan dash.';
    } else {
        // Cek username duplikat (exclude current)
        $duplicate = false;
        foreach (['admin', 'guru', 'pengawas'] as $table) {
            $stmt = $conn->prepare("SELECT id FROM `$table` WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $duplicate = true;
            }
            $stmt->close();
        }
        // Check siswa table excluding self
        $stmt = $conn->prepare("SELECT id FROM siswa WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $duplicate = true;
        }
        $stmt->close();

        if ($duplicate) {
            $error = 'Username sudah digunakan.';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 4) {
                    $error = 'Password minimal 4 karakter.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE siswa SET nama=?, kelas_id=?, username=?, password=?, password_plain=?, is_blocked=? WHERE id=?");
                    $stmt->bind_param("sisssii", $nama, $kelas_id, $username, $hashed, $password, $is_blocked, $id);
                }
            }

            if (empty($error)) {
                if (empty($password)) {
                    $stmt = $conn->prepare("UPDATE siswa SET nama=?, kelas_id=?, username=?, is_blocked=? WHERE id=?");
                    $stmt->bind_param("sisii", $nama, $kelas_id, $username, $is_blocked, $id);
                }

                if ($stmt->execute()) {
                    redirect(base_url('admin/siswa/index.php'), 'success', 'Data siswa "' . $nama . '" berhasil diupdate.');
                } else {
                    $error = 'Gagal mengupdate siswa.';
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
                <h4><i class="fas fa-edit me-2"></i>Edit Siswa</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/siswa/index.php') ?>">Siswa</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Edit: <?= e($siswa['nama']) ?></div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" required
                                       value="<?= e($_POST['nama'] ?? $siswa['nama']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php while ($k = $kelas_list->fetch_assoc()): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($_POST['kelas_id'] ?? $siswa['kelas_id']) == $k['id'] ? 'selected' : '' ?>>
                                        <?= e($k['nama_kelas']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required
                                       value="<?= e($_POST['username'] ?? $siswa['username']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="text" name="password" class="form-control"
                                       placeholder="Kosongkan jika tidak ingin mengubah password">
                                <div class="form-text">
                                    <?php if ($siswa['password_plain']): ?>
                                    Password saat ini: <code><?= e($siswa['password_plain']) ?></code>
                                    <?php else: ?>
                                    Password plain tidak tersedia.
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_blocked" id="is_blocked"
                                           <?= ($_POST['is_blocked'] ?? $siswa['is_blocked']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_blocked">
                                        Blokir Siswa <small class="text-muted">(siswa tidak bisa login)</small>
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update
                                </button>
                                <a href="<?= base_url('admin/siswa/index.php') ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Sidebar -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Info Siswa</div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="fw-semibold" width="140">ID</td>
                                <td><?= $siswa['id'] ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Kelas</td>
                                <td><span class="badge bg-info"><?= e($siswa['nama_kelas']) ?></span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Status</td>
                                <td>
                                    <?php if ($siswa['is_blocked']): ?>
                                    <span class="badge bg-danger">Blocked</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Aktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Session</td>
                                <td>
                                    <?php if ($siswa['session_id']): ?>
                                    <span class="badge bg-warning">Online</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Offline</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Terdaftar</td>
                                <td class="small"><?= format_datetime($siswa['created_at']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
