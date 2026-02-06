<?php
/**
 * Pengawas - Generate & Kelola Token
 * CBT Nusantara
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['pengawas', 'admin', 'guru']);

$page_title = 'Kelola Token';
$active_menu = 'token';

// Generate token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $new_token = generate_token(6);
    $now = date('Y-m-d H:i:s');

    update_setting('current_token', $new_token, $conn);
    update_setting('token_generated_at', $now, $conn);

    set_flash('success', "Token baru berhasil digenerate: <strong>$new_token</strong>");
    redirect(base_url($_SESSION['role'] === 'pengawas' ? 'pengawas/token.php' : 'admin/token/generate.php'));
}

// Get current token info
$current_token = get_setting('current_token', $conn, '');
$token_generated_at = get_setting('token_generated_at', $conn, '');
$token_duration = (int)get_setting('token_duration', $conn, '100');
$token_enabled = get_setting('token_enabled', $conn, '1');

$token_expired = false;
$time_remaining = 0;
if (!empty($current_token) && !empty($token_generated_at)) {
    $expiry = strtotime($token_generated_at) + ($token_duration * 60);
    $token_expired = time() > $expiry;
    $time_remaining = max(0, $expiry - time());
}

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-key me-2"></i>Kelola Token Ujian</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url($_SESSION['role'] . '/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Token</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php render_flash(); ?>

        <?php if ($token_enabled !== '1'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle me-2"></i>Sistem token sedang <strong>dinonaktifkan</strong>. Siswa bisa masuk ujian tanpa token.
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Current Token -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header fw-semibold"><i class="fas fa-key me-2"></i>Token Aktif</div>
                    <div class="card-body text-center py-5">
                        <?php if (!empty($current_token)): ?>
                        <div class="mb-3">
                            <div class="fs-1 fw-bold font-monospace <?= $token_expired ? 'text-danger text-decoration-line-through' : 'text-success' ?>"
                                 style="letter-spacing: 12px; font-size: 3.5rem !important;">
                                <?= e($current_token) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <?php if ($token_expired): ?>
                            <span class="badge bg-danger fs-6 py-2 px-3"><i class="fas fa-times-circle me-1"></i>EXPIRED</span>
                            <?php else: ?>
                            <span class="badge bg-success fs-6 py-2 px-3"><i class="fas fa-check-circle me-1"></i>AKTIF</span>
                            <div class="mt-2">
                                <span class="text-muted">Sisa waktu:</span>
                                <span class="fw-bold text-primary" id="tokenCountdown"><?= floor($time_remaining / 60) ?>m <?= $time_remaining % 60 ?>s</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="small text-muted">
                            Dibuat: <?= format_datetime($token_generated_at) ?><br>
                            Durasi: <?= $token_duration ?> menit
                        </div>
                        <?php else: ?>
                        <div class="text-muted">
                            <i class="fas fa-key" style="font-size:4rem;opacity:0.2;"></i>
                            <p class="mt-3 fs-5">Belum ada token aktif</p>
                            <p class="small">Klik tombol di bawah untuk generate token baru.</p>
                        </div>
                        <?php endif; ?>

                        <hr>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="generate" value="1" class="btn btn-primary btn-lg"
                                    onclick="return confirm('<?= !empty($current_token) && !$token_expired ? 'Token aktif akan diganti. Lanjutkan?' : 'Generate token baru?' ?>')">
                                <i class="fas fa-sync-alt me-2"></i><?= !empty($current_token) ? 'Generate Token Baru' : 'Generate Token' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2"></i>Info Token</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted">Sistem Token</td>
                                <td><span class="badge <?= $token_enabled === '1' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $token_enabled === '1' ? 'Aktif' : 'Nonaktif' ?>
                                </span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Durasi Token</td>
                                <td><?= $token_duration ?> menit</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Panjang</td>
                                <td>6 digit angka</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold"><i class="fas fa-question-circle me-2"></i>Panduan</div>
                    <div class="card-body small">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Klik <strong>Generate Token</strong> untuk membuat token 6 digit baru.</li>
                            <li class="mb-2">Bagikan token ke siswa secara <strong>lisan</strong> atau tampilkan di layar proyektor.</li>
                            <li class="mb-2">Siswa memasukkan token untuk memulai ujian.</li>
                            <li class="mb-2">Token otomatis expired setelah <strong><?= $token_duration ?> menit</strong>.</li>
                            <li class="mb-0">Generate token baru jika diperlukan (token lama otomatis tidak berlaku).</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$inline_js = "";
if (!$token_expired && $time_remaining > 0) {
    $inline_js = "
    var tokenRemaining = $time_remaining;
    setInterval(function(){
        tokenRemaining--;
        if (tokenRemaining <= 0) { location.reload(); return; }
        var m = Math.floor(tokenRemaining / 60);
        var s = tokenRemaining % 60;
        document.getElementById('tokenCountdown').textContent = m + 'm ' + s + 's';
    }, 1000);
    ";
}
include __DIR__ . '/../includes/footer.php';
?>
