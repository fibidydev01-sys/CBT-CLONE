<?php
/**
 * Settings - Pengaturan Sistem
 * Admin Panel
 *
 * Semua pengaturan dalam satu halaman dengan tabs/sections
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Pengaturan Sistem';
$active_menu = 'settings';

// Load all settings
$settings = load_all_settings($conn);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';

    if ($section === 'sekolah') {
        update_setting('school_name', trim($_POST['school_name'] ?? ''), $conn);
        update_setting('school_description', trim($_POST['school_description'] ?? ''), $conn);
        update_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0', $conn);
        redirect(base_url('admin/settings/index.php#sekolah'), 'success', 'Pengaturan sekolah berhasil disimpan.');
    }

    if ($section === 'ujian') {
        update_setting('exam_timer', (int)($_POST['exam_timer'] ?? 75), $conn);
        update_setting('exam_without_schedule', isset($_POST['exam_without_schedule']) ? '1' : '0', $conn);
        update_setting('min_submit_time', (int)($_POST['min_submit_time'] ?? 10), $conn);
        update_setting('answer_all_required', isset($_POST['answer_all_required']) ? '1' : '0', $conn);
        redirect(base_url('admin/settings/index.php#ujian'), 'success', 'Pengaturan ujian berhasil disimpan.');
    }

    if ($section === 'token') {
        update_setting('token_enabled', isset($_POST['token_enabled']) ? '1' : '0', $conn);
        update_setting('exam_without_token', isset($_POST['exam_without_token']) ? '1' : '0', $conn);
        update_setting('token_duration', (int)($_POST['token_duration'] ?? 100), $conn);
        redirect(base_url('admin/settings/index.php#token'), 'success', 'Pengaturan token berhasil disimpan.');
    }

    if ($section === 'anticheat') {
        update_setting('exit_penalty', (int)($_POST['exit_penalty'] ?? 5), $conn);
        update_setting('max_exit_count', (int)($_POST['max_exit_count'] ?? 3), $conn);
        update_setting('penalty_lockout_duration', (int)($_POST['penalty_lockout_duration'] ?? 10), $conn);
        update_setting('violation_action_type', $_POST['violation_action_type'] ?? 'selesai', $conn);
        update_setting('enable_activity_log', isset($_POST['enable_activity_log']) ? '1' : '0', $conn);
        redirect(base_url('admin/settings/index.php#anticheat'), 'success', 'Pengaturan anti-cheat berhasil disimpan.');
    }

    if ($section === 'siswa') {
        update_setting('show_score_after_exam', isset($_POST['show_score_after_exam']) ? '1' : '0', $conn);
        update_setting('enable_student_logout', isset($_POST['enable_student_logout']) ? '1' : '0', $conn);
        update_setting('enable_reset_login', isset($_POST['enable_reset_login']) ? '1' : '0', $conn);
        update_setting('login_without_subject', isset($_POST['login_without_subject']) ? '1' : '0', $conn);
        redirect(base_url('admin/settings/index.php#siswa'), 'success', 'Pengaturan siswa berhasil disimpan.');
    }

    if ($section === 'logo') {
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['school_logo'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed)) {
                redirect(base_url('admin/settings/index.php#logo'), 'danger', 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.');
            }

            if ($file['size'] > $max_size) {
                redirect(base_url('admin/settings/index.php#logo'), 'danger', 'Ukuran file maksimal 2MB.');
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . strtolower($ext);
            $upload_dir = __DIR__ . '/../../uploads/logo/';
            $upload_path = $upload_dir . $filename;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old logo if not default
                $old_logo = $settings['school_logo'] ?? '';
                if ($old_logo && $old_logo !== 'uploads/logo/logo.png') {
                    $old_path = __DIR__ . '/../../' . $old_logo;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                update_setting('school_logo', 'uploads/logo/' . $filename, $conn);
                redirect(base_url('admin/settings/index.php#logo'), 'success', 'Logo berhasil diupload.');
            } else {
                redirect(base_url('admin/settings/index.php#logo'), 'danger', 'Gagal upload logo.');
            }
        } else {
            redirect(base_url('admin/settings/index.php#logo'), 'danger', 'Pilih file logo terlebih dahulu.');
        }
    }
}

// Reload settings after potential update
$settings = load_all_settings($conn);

$school_name = $settings['school_name'] ?? 'CBT Nusantara';

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-cog me-2"></i>Pengaturan Sistem</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pengaturan</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php render_flash(); ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#sekolah"><i class="fas fa-school me-1"></i>Sekolah</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ujian"><i class="fas fa-file-alt me-1"></i>Ujian</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#token"><i class="fas fa-key me-1"></i>Token</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#anticheat"><i class="fas fa-shield-alt me-1"></i>Anti-Cheat</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#siswa"><i class="fas fa-user-graduate me-1"></i>Siswa</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#logo"><i class="fas fa-image me-1"></i>Logo</a></li>
        </ul>

        <div class="tab-content">
            <!-- Tab: Sekolah -->
            <div class="tab-pane fade show active" id="sekolah">
                <div class="card">
                    <div class="card-header"><i class="fas fa-school me-2"></i>Pengaturan Sekolah</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="section" value="sekolah">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Sekolah</label>
                                <input type="text" name="school_name" class="form-control" value="<?= e($settings['school_name'] ?? 'NAMA SEKOLAH ANDA') ?>" required>
                                <div class="form-text">Ditampilkan di header, login page, dan laporan.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi Portal</label>
                                <input type="text" name="school_description" class="form-control" value="<?= e($settings['school_description'] ?? 'PORTAL UJIAN CBT') ?>">
                                <div class="form-text">Tagline yang ditampilkan di halaman login.</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="maintenance_mode" id="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="maintenance_mode">Mode Pemeliharaan</label>
                                </div>
                                <div class="form-text text-danger">Jika aktif, hanya admin yang bisa login. Siswa, guru, dan pengawas akan melihat halaman maintenance.</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Pengaturan Sekolah</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Ujian -->
            <div class="tab-pane fade" id="ujian">
                <div class="card">
                    <div class="card-header"><i class="fas fa-file-alt me-2"></i>Konfigurasi Ujian</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="section" value="ujian">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Default Durasi Ujian (menit)</label>
                                    <input type="number" name="exam_timer" class="form-control" value="<?= e($settings['exam_timer'] ?? '75') ?>" min="5" max="300">
                                    <div class="form-text">Durasi default saat membuat ujian baru.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Minimum Waktu Submit (menit)</label>
                                    <input type="number" name="min_submit_time" class="form-control" value="<?= e($settings['min_submit_time'] ?? '10') ?>" min="0" max="60">
                                    <div class="form-text">Siswa tidak bisa submit sebelum waktu ini berlalu.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="exam_without_schedule" id="exam_without_schedule" value="1" <?= ($settings['exam_without_schedule'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="exam_without_schedule">Izinkan Ujian Tanpa Jadwal</label>
                                </div>
                                <div class="form-text">Jika aktif, ujian bisa diakses kapan saja tanpa pembatasan tanggal/jam.</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="answer_all_required" id="answer_all_required" value="1" <?= ($settings['answer_all_required'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="answer_all_required">Wajib Jawab Semua Soal</label>
                                </div>
                                <div class="form-text">Siswa harus menjawab semua soal sebelum bisa submit.</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Pengaturan Ujian</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Token -->
            <div class="tab-pane fade" id="token">
                <div class="card">
                    <div class="card-header"><i class="fas fa-key me-2"></i>Sistem Token</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="section" value="token">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="token_enabled" id="token_enabled" value="1" <?= ($settings['token_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="token_enabled">Aktifkan Sistem Token</label>
                                </div>
                                <div class="form-text">Siswa harus memasukkan token sebelum mengakses ujian.</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="exam_without_token" id="exam_without_token" value="1" <?= ($settings['exam_without_token'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="exam_without_token">Izinkan Ujian Tanpa Token</label>
                                </div>
                                <div class="form-text">Jika aktif, siswa bisa langsung mengakses ujian tanpa token.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Durasi Token (menit)</label>
                                <input type="number" name="token_duration" class="form-control" value="<?= e($settings['token_duration'] ?? '100') ?>" min="5" max="600" style="max-width:200px;">
                                <div class="form-text">Token akan kadaluarsa setelah durasi ini.</div>
                            </div>

                            <?php
                            $current_token = $settings['current_token'] ?? '';
                            $token_time = $settings['token_generated_at'] ?? '';
                            $token_dur = (int)($settings['token_duration'] ?? 100);
                            $is_active = false;
                            if ($current_token && $token_time) {
                                $expires = strtotime($token_time) + ($token_dur * 60);
                                $is_active = time() < $expires;
                            }
                            ?>

                            <?php if ($current_token): ?>
                            <div class="alert <?= $is_active ? 'alert-success' : 'alert-secondary' ?> d-flex align-items-center">
                                <div class="me-3">
                                    <small class="d-block text-muted">Token Aktif</small>
                                    <span class="fs-3 fw-bold font-monospace"><?= e($current_token) ?></span>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Dibuat</small>
                                    <?= $token_time ?>
                                    <?php if (!$is_active): ?><br><span class="badge bg-danger">Kadaluarsa</span><?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Pengaturan Token</button>
                            <a href="<?= base_url('admin/token/generate.php') ?>" class="btn btn-outline-warning ms-2"><i class="fas fa-key me-1"></i>Generate Token Baru</a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Anti-Cheat -->
            <div class="tab-pane fade" id="anticheat">
                <div class="card">
                    <div class="card-header"><i class="fas fa-shield-alt me-2"></i>Konfigurasi Anti-Cheat</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="section" value="anticheat">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Penalti per Exit (detik)</label>
                                    <input type="number" name="exit_penalty" class="form-control" value="<?= e($settings['exit_penalty'] ?? '5') ?>" min="0" max="60">
                                    <div class="form-text">Waktu dikurangi setiap kali siswa berpindah tab.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Maksimal Exit</label>
                                    <input type="number" name="max_exit_count" class="form-control" value="<?= e($settings['max_exit_count'] ?? '3') ?>" min="1" max="20">
                                    <div class="form-text">Setelah ini, aksi pelanggaran dieksekusi.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Durasi Lockout (menit)</label>
                                    <input type="number" name="penalty_lockout_duration" class="form-control" value="<?= e($settings['penalty_lockout_duration'] ?? '10') ?>" min="1" max="60">
                                    <div class="form-text">Berlaku jika aksi = lockout.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Aksi Pelanggaran</label>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="violation_action_type" id="va_selesai" value="selesai" <?= ($settings['violation_action_type'] ?? 'selesai') === 'selesai' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="va_selesai"><strong>Auto Submit</strong> - Ujian otomatis diselesaikan dan dinilai</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="violation_action_type" id="va_lockout" value="lockout" <?= ($settings['violation_action_type'] ?? '') === 'lockout' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="va_lockout"><strong>Lockout</strong> - Siswa dikunci sementara, perlu reset oleh pengawas</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="enable_activity_log" id="enable_activity_log" value="1" <?= ($settings['enable_activity_log'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="enable_activity_log">Aktifkan Log Aktivitas</label>
                                </div>
                                <div class="form-text">Catat semua aktivitas mencurigakan (TAB_SWITCH, RIGHT_CLICK, dll).</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Pengaturan Anti-Cheat</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Siswa -->
            <div class="tab-pane fade" id="siswa">
                <div class="card">
                    <div class="card-header"><i class="fas fa-user-graduate me-2"></i>Pengaturan Siswa</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="section" value="siswa">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="show_score_after_exam" id="show_score_after_exam" value="1" <?= ($settings['show_score_after_exam'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="show_score_after_exam">Tampilkan Nilai Setelah Ujian</label>
                                </div>
                                <div class="form-text">Siswa bisa melihat nilainya langsung setelah menyelesaikan ujian.</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="enable_student_logout" id="enable_student_logout" value="1" <?= ($settings['enable_student_logout'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="enable_student_logout">Izinkan Siswa Logout</label>
                                </div>
                                <div class="form-text">Jika nonaktif, siswa tidak bisa logout sendiri (harus dikeluarkan oleh admin/pengawas).</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="enable_reset_login" id="enable_reset_login" value="1" <?= ($settings['enable_reset_login'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="enable_reset_login">Aktifkan Reset Password</label>
                                </div>
                                <div class="form-text">Siswa bisa mereset password mereka sendiri.</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="login_without_subject" id="login_without_subject" value="1" <?= ($settings['login_without_subject'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="login_without_subject">Login Tanpa Pilih Mata Pelajaran</label>
                                </div>
                                <div class="form-text">Siswa langsung login tanpa memilih mata pelajaran terlebih dahulu.</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Pengaturan Siswa</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Logo -->
            <div class="tab-pane fade" id="logo">
                <div class="card">
                    <div class="card-header"><i class="fas fa-image me-2"></i>Logo Sekolah</div>
                    <div class="card-body">
                        <?php $logo_path = $settings['school_logo'] ?? 'uploads/logo/logo.png'; ?>

                        <div class="text-center mb-4">
                            <p class="text-muted small mb-2">Logo Saat Ini:</p>
                            <?php if (file_exists(__DIR__ . '/../../' . $logo_path)): ?>
                            <img src="<?= base_url($logo_path) ?>" alt="Logo" class="border rounded p-2" style="max-width:200px; max-height:200px;">
                            <?php else: ?>
                            <div class="border rounded p-4 d-inline-block">
                                <i class="fas fa-school fa-3x text-muted"></i>
                                <p class="text-muted small mt-2 mb-0">Belum ada logo</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="section" value="logo">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Upload Logo Baru</label>
                                <input type="file" name="school_logo" class="form-control" accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">Format: JPG, PNG, GIF. Maksimal 2MB. Disarankan ukuran 200x200 px.</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Upload Logo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$inline_js = "
// Activate tab from URL hash
var hash = window.location.hash;
if (hash) {
    var tab = document.querySelector('a[href=\"' + hash + '\"]');
    if (tab) { new bootstrap.Tab(tab).show(); }
}
// Update hash on tab change
document.querySelectorAll('a[data-bs-toggle=\"tab\"]').forEach(function(el) {
    el.addEventListener('shown.bs.tab', function(e) {
        history.replaceState(null, null, e.target.getAttribute('href'));
    });
});
";
include __DIR__ . '/../../includes/footer.php';
?>
