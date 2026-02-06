<?php
/**
 * Login Page
 * CBT Nusantara - Single School Edition
 *
 * Single login page untuk semua role (admin, guru, siswa, pengawas)
 * Deteksi role otomatis dari tabel masing-masing
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/includes/functions.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header('Location: ' . base_url('index.php'));
    exit();
}

$error = '';
$school_name = get_setting('school_name', $conn, 'CBT Nusantara');
$school_desc = get_setting('school_description', $conn, 'Portal Ujian CBT');
$maintenance = get_setting('maintenance_mode', $conn, '0');

// Handle error dari redirect
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'session_hijacked':
            $error = 'Sesi Anda telah digunakan di perangkat lain. Silakan login ulang.';
            break;
        case 'blocked':
            $error = 'Akun Anda telah diblokir. Hubungi pengawas atau admin.';
            break;
    }
}

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi.';
    } else {
        $logged_in = false;

        // Urutan cek: admin → guru → pengawas → siswa
        $tables = ['admin', 'guru', 'pengawas', 'siswa'];

        foreach ($tables as $role) {
            $stmt = $conn->prepare("SELECT * FROM `$role` WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                // Cek maintenance mode - hanya admin yang bisa login
                if ($maintenance === '1' && $role !== 'admin') {
                    $error = 'Sistem sedang dalam pemeliharaan. Hanya admin yang dapat login saat ini.';
                    break;
                }

                // Cek apakah siswa diblokir
                if ($role === 'siswa' && !empty($user['is_blocked'])) {
                    $error = 'Akun Anda telah diblokir. Hubungi pengawas atau admin.';
                    break;
                }

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];

                // Lock session untuk siswa
                if ($role === 'siswa') {
                    lock_student_session($user['id'], $conn);
                }

                // Redirect sesuai role
                switch ($role) {
                    case 'admin':
                        header('Location: ' . base_url('admin/index.php'));
                        break;
                    case 'guru':
                        header('Location: ' . base_url('guru/index.php'));
                        break;
                    case 'siswa':
                        header('Location: ' . base_url('siswa/index.php'));
                        break;
                    case 'pengawas':
                        header('Location: ' . base_url('pengawas/index.php'));
                        break;
                }
                exit();
            }
        }

        if (empty($error)) {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Login - <?= e($school_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #084298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 90%;
        }
        .login-card .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-card .header .icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #0d6efd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .login-card .header .icon i {
            font-size: 30px;
            color: white;
        }
        .login-card .header h4 {
            font-weight: 700;
            color: #212529;
            margin-bottom: 5px;
        }
        .login-card .header p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .form-floating {
            margin-bottom: 15px;
        }
        .form-floating .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding-left: 45px;
        }
        .form-floating .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
        }
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="header">
            <div class="icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <h4><?= e($school_name) ?></h4>
            <p><?= e($school_desc) ?></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="position-relative mb-3">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="form-control form-control-lg"
                       placeholder="Username" required autofocus
                       style="padding-left:45px; border-radius:10px; border:2px solid #e9ecef;"
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <div class="position-relative mb-4">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" class="form-control form-control-lg"
                       placeholder="Password" required
                       style="padding-left:45px; padding-right:45px; border-radius:10px; border:2px solid #e9ecef;">
                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Masuk
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted small">
                <i class="fas fa-arrow-left me-1"></i>Kembali ke Beranda
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.querySelector('.toggle-password');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    </script>
</body>
</html>
