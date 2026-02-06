<?php
/**
 * 403 Forbidden Page
 * CBT Nusantara - Single School Edition
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            text-align: center;
            padding: 50px 30px;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: #dc3545;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <h3 class="mt-3">Akses Ditolak</h3>
        <p class="text-muted">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <div class="mt-4">
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= base_url('index.php') ?>" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
            </a>
            <?php else: ?>
            <a href="<?= base_url('login.php') ?>" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
