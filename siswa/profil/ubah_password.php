<?php
/**
 * Ubah Password Siswa
 * Siswa Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['siswa']);

$page_title = 'Ubah Password';
$siswa_id = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validate old password
    $stmt = $conn->prepare("SELECT password FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $siswa_id);
    $stmt->execute();
    $siswa = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$siswa || !password_verify($old_pass, $siswa['password'])) {
        $errors[] = 'Password lama tidak sesuai.';
    }

    if (strlen($new_pass) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }

    if ($new_pass !== $confirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if ($old_pass === $new_pass) {
        $errors[] = 'Password baru tidak boleh sama dengan password lama.';
    }

    if (empty($errors)) {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE siswa SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $siswa_id);
        $stmt->execute();
        $stmt->close();

        redirect(base_url('siswa/profil/index.php'), 'success', 'Password berhasil diubah.');
    }
}

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4" style="max-width: 500px;">
    <div class="mb-3">
        <a href="<?= base_url('siswa/profil/index.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Profil</a>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-key me-2"></i>Ubah Password</div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password Lama</label>
                    <div class="input-group">
                        <input type="password" name="old_password" class="form-control" id="oldPass" required>
                        <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="oldPass"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="new_password" class="form-control" id="newPass" required minlength="6">
                        <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="newPass"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="form-text">Minimal 6 karakter.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" class="form-control" id="confirmPass" required>
                        <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="confirmPass"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Password</button>
                    <a href="<?= base_url('siswa/profil/index.php') ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$inline_js = "
document.querySelectorAll('.toggle-pass').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.getElementById(this.dataset.target);
        var icon = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});
";
include __DIR__ . '/../../includes/footer.php';
?>
