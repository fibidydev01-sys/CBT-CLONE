<?php
/**
 * Admin - Token Management
 * CBT Nusantara
 *
 * Generate & kelola token ujian (wrapper ke pengawas/token.php logic)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin', 'guru']);

$page_title = 'Kelola Token';
$active_menu = 'token';

// Generate token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $new_token = generate_token(6);
    $now = date('Y-m-d H:i:s');

    update_setting('current_token', $new_token, $conn);
    update_setting('token_generated_at', $now, $conn);

    set_flash('success', "Token baru berhasil digenerate: <strong>$new_token</strong>");
    redirect(base_url('admin/token/generate.php'));
}

// Update token settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $token_enabled = ($_POST['token_enabled'] ?? '0') === '1' ? '1' : '0';
    $token_duration = max(1, (int)($_POST['token_duration'] ?? 100));

    update_setting('token_enabled', $token_enabled, $conn);
    update_setting('token_duration', (string)$token_duration, $conn);

    set_flash('success', 'Pengaturan token berhasil disimpan.');
    redirect(base_url('admin/token/generate.php'));
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

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-key me-2"></i>Kelola Token Ujian</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Token</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php render_flash(); ?>

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
                            Dibuat: <?= format_datetime($token_generated_at) ?> &middot; Durasi: <?= $token_duration ?> menit
                        </div>
                        <?php else: ?>
                        <div class="text-muted">
                            <i class="fas fa-key" style="font-size:4rem;opacity:0.2;"></i>
                            <p class="mt-3 fs-5">Belum ada token aktif</p>
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

            <!-- Settings -->
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header fw-semibold"><i class="fas fa-cog me-2"></i>Pengaturan Token</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sistem Token</label>
                                <select name="token_enabled" class="form-select">
                                    <option value="1" <?= $token_enabled === '1' ? 'selected' : '' ?>>Aktif (wajib token)</option>
                                    <option value="0" <?= $token_enabled !== '1' ? 'selected' : '' ?>>Nonaktif (tanpa token)</option>
                                </select>
                                <div class="form-text">Jika nonaktif, siswa bisa masuk ujian tanpa token.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Durasi Token (menit)</label>
                                <input type="number" name="token_duration" class="form-control" value="<?= $token_duration ?>" min="1" max="1440">
                                <div class="form-text">Berapa lama token valid setelah digenerate.</div>
                            </div>
                            <button type="submit" name="save_settings" value="1" class="btn btn-success w-100">
                                <i class="fas fa-save me-1"></i>Simpan Pengaturan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold"><i class="fas fa-question-circle me-2"></i>Panduan</div>
                    <div class="card-body small">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Generate token 6 digit baru.</li>
                            <li class="mb-2">Bagikan ke siswa secara lisan/tampilkan di layar.</li>
                            <li class="mb-2">Siswa input token untuk mulai ujian.</li>
                            <li class="mb-0">Token expired otomatis setelah durasi habis.</li>
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
include __DIR__ . '/../../includes/footer.php';
?>
