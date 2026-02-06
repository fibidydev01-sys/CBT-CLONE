<?php
/**
 * Landing Page / Home
 * CBT Nusantara - Single School Edition
 *
 * Redirect ke dashboard jika sudah login, tampilkan welcome page jika belum
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Jika sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
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

// Load settings
require_once __DIR__ . '/config/settings.php';
$school_name = get_setting('school_name', $conn, 'CBT Nusantara');
$school_desc = get_setting('school_description', $conn, 'Portal Ujian CBT');
$school_logo = get_setting('school_logo', $conn, 'assets/img/logo.png');
$maintenance = get_setting('maintenance_mode', $conn, '0');

if ($maintenance === '1') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance - ' . e($school_name) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    </head><body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container text-center">
        <i class="fas fa-tools" style="font-size:4rem;color:#6c757d;"></i>
        <h2 class="mt-3">Sistem Sedang Maintenance</h2>
        <p class="text-muted">Silakan coba beberapa saat lagi.</p>
        <a href="' . base_url('login.php') . '" class="btn btn-sm btn-outline-secondary mt-2">Login Admin</a>
    </div></body></html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= e($school_name) ?> - <?= e($school_desc) ?></title>
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
        .landing-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .landing-card .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid #0d6efd;
        }
        .landing-card .logo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #0d6efd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .landing-card .logo-placeholder i {
            font-size: 40px;
            color: white;
        }
        .landing-card h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
        }
        .landing-card p {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn-login {
            padding: 12px 50px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
        }
        .features {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .feature-item {
            text-align: center;
        }
        .feature-item i {
            font-size: 1.5rem;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .feature-item span {
            display: block;
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="landing-card">
        <?php if ($school_logo && file_exists(__DIR__ . '/' . $school_logo)): ?>
        <img src="<?= base_url($school_logo) ?>" alt="Logo" class="logo">
        <?php else: ?>
        <div class="logo-placeholder">
            <i class="fas fa-laptop-code"></i>
        </div>
        <?php endif; ?>
        <h1><?= e($school_name) ?></h1>
        <p><?= e($school_desc) ?></p>
        <a href="<?= base_url('login.php') ?>" class="btn btn-primary btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>Masuk
        </a>
        <div class="features">
            <div class="feature-item">
                <i class="fas fa-shield-alt"></i>
                <span>Aman</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-clock"></i>
                <span>Real-time</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-mobile-alt"></i>
                <span>Responsive</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-users"></i>
                <span>Multi User</span>
            </div>
        </div>
    </div>
</body>
</html>
