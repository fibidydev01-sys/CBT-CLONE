<?php
/**
 * Siswa - Halaman Ujian (Main Exam Page)
 * CBT Nusantara
 *
 * Full-screen exam with:
 * - Timer countdown
 * - Question navigator sidebar
 * - Anti-cheat detection
 * - Auto-save via AJAX
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['siswa']);
verify_student_session($_SESSION['user_id'], $conn);

$siswa_id = $_SESSION['user_id'];
$sesi_id = (int)($_GET['sesi_id'] ?? 0);

// Get sesi + ujian
$stmt = $conn->prepare("SELECT su.*, u.nama_ujian, u.jumlah_soal, u.alokasi_waktu,
    u.acak_soal, u.acak_jawaban, u.bank_soal_id
    FROM sesi_ujian su
    JOIN ujian u ON su.ujian_id = u.id
    WHERE su.id = ? AND su.siswa_id = ? AND su.status = 'sedang_ujian'");
$stmt->bind_param("ii", $sesi_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    redirect(base_url('siswa/index.php'), 'danger', 'Sesi ujian tidak valid atau sudah selesai.');
}

// Get soal
$soal_query = $conn->query("SELECT * FROM soal WHERE bank_soal_id = {$sesi['bank_soal_id']} ORDER BY nomor_soal ASC LIMIT {$sesi['jumlah_soal']}");
$soal_all = [];
while ($s = $soal_query->fetch_assoc()) {
    // Get opsi
    $opsi_q = $conn->query("SELECT * FROM opsi_jawaban WHERE soal_id = {$s['id']} ORDER BY opsi_key ASC");
    $s['opsi'] = [];
    while ($o = $opsi_q->fetch_assoc()) $s['opsi'][] = $o;
    $soal_all[] = $s;
}

// Acak soal jika setting aktif
if ($sesi['acak_soal']) {
    mt_srand($siswa_id + $sesi['ujian_id']);
    shuffle($soal_all);
}

// Acak opsi jika setting aktif
if ($sesi['acak_jawaban']) {
    foreach ($soal_all as &$s) {
        mt_srand($siswa_id + $sesi['ujian_id'] + $s['id']);
        shuffle($s['opsi']);
    }
    unset($s);
}

// Get existing jawaban
$existing_jawaban = [];
$result = $conn->query("SELECT nomor_soal, jawaban FROM jawaban_siswa WHERE sesi_ujian_id = $sesi_id");
while ($r = $result->fetch_assoc()) $existing_jawaban[$r['nomor_soal']] = $r['jawaban'];

// Current nomor (1-indexed)
$current = max(1, min((int)($_GET['nomor'] ?? 1), count($soal_all)));
$soal = $soal_all[$current - 1];

// Anti-cheat settings
$exit_penalty = (int)get_setting('exit_penalty', $conn, '5');
$max_exit = (int)get_setting('max_exit_count', $conn, '3');

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Ujian - <?= e($sesi['nama_ujian']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <style>
        body { background: #f0f2f5; overflow-x: hidden; }
        .exam-header { background: #0d6efd; color: white; padding: 10px 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 200; display: flex; justify-content: space-between; align-items: center; }
        .exam-header .timer { font-size: 1.4rem; font-weight: 700; font-family: monospace; }
        .timer.warning { color: #ffc107; }
        .timer.danger { color: #ff4444; animation: blink 1s infinite; }
        @keyframes blink { 0%,50%{opacity:1} 51%,100%{opacity:0.3} }

        .exam-sidebar { position: fixed; left: 0; top: 52px; width: 220px; height: calc(100vh - 52px); background: white; border-right: 1px solid #dee2e6; overflow-y: auto; padding: 15px; z-index: 100; transition: left 0.3s; }
        .exam-content { margin-left: 220px; margin-top: 52px; padding: 20px; min-height: calc(100vh - 52px); }

        .nav-btn { width: 42px; height: 42px; border: 2px solid #dee2e6; border-radius: 8px; background: white; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .nav-btn:hover { border-color: #0d6efd; }
        .nav-btn.answered { background: #198754; color: white; border-color: #198754; }
        .nav-btn.current { background: #0d6efd; color: white; border-color: #0d6efd; }
        .nav-btn.flagged { background: #ffc107; color: #212529; border-color: #ffc107; }

        .question-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .question-text { font-size: 1.05rem; line-height: 1.8; margin-bottom: 25px; }
        .option-btn { width: 100%; padding: 14px 20px; margin: 8px 0; border: 2px solid #dee2e6; border-radius: 10px; background: white; text-align: left; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; min-height: 50px; }
        .option-btn:hover { border-color: #0d6efd; background: #f0f7ff; }
        .option-btn.selected { border-color: #0d6efd; background: #0d6efd; color: white; }
        .option-key { width: 32px; height: 32px; border-radius: 50%; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 12px; flex-shrink: 0; }
        .option-btn.selected .option-key { background: rgba(255,255,255,0.3); }

        .save-indicator { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 300; display: none; }

        .mobile-nav-toggle { display: none; position: fixed; bottom: 20px; left: 20px; z-index: 300; width: 48px; height: 48px; border-radius: 50%; background: #0d6efd; color: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }

        @media (max-width: 991.98px) {
            .exam-sidebar { left: -220px; }
            .exam-sidebar.show { left: 0; }
            .exam-content { margin-left: 0; }
            .mobile-nav-toggle { display: flex; align-items: center; justify-content: center; }
        }
        @media (max-width: 767.98px) {
            .exam-content { padding: 15px; }
            .question-card { padding: 20px; }
            .question-text { font-size: 0.95rem; }
            .option-btn { padding: 12px 15px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="exam-header">
        <div>
            <strong class="d-none d-md-inline"><?= e($sesi['nama_ujian']) ?></strong>
            <span class="badge bg-light text-primary ms-2"><?= $current ?>/<?= count($soal_all) ?></span>
        </div>
        <div class="timer" id="timer">--:--:--</div>
        <div>
            <span class="badge bg-warning text-dark" id="exitBadge" style="display:none;">Exit: <span id="exitDisplay">0</span>/<?= $max_exit ?></span>
        </div>
    </div>

    <!-- Sidebar Navigator -->
    <div class="exam-sidebar" id="examSidebar">
        <h6 class="fw-bold small text-muted mb-3">NAVIGASI SOAL</h6>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php for ($i = 1; $i <= count($soal_all); $i++):
                $s_data = $soal_all[$i - 1];
                $is_answered = isset($existing_jawaban[$s_data['nomor_soal']]) && $existing_jawaban[$s_data['nomor_soal']] !== '';
                $class = '';
                if ($i === $current) $class = 'current';
                elseif ($is_answered) $class = 'answered';
            ?>
            <a href="<?= base_url('siswa/ujian/soal.php?sesi_id=' . $sesi_id . '&nomor=' . $i) ?>"
               class="nav-btn <?= $class ?>" id="nav-btn-<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>

        <hr>
        <div class="small text-muted mb-2">
            <span class="nav-btn answered" style="width:16px;height:16px;font-size:0;display:inline-block;vertical-align:middle;"></span> Terjawab
            <span class="nav-btn current ms-2" style="width:16px;height:16px;font-size:0;display:inline-block;vertical-align:middle;"></span> Aktif
        </div>
        <div class="small text-muted">
            Terjawab: <strong id="answeredCount"><?= count(array_filter($existing_jawaban)) ?></strong>/<?= count($soal_all) ?>
        </div>

        <hr>
        <form action="<?= base_url('siswa/ujian/submit.php') ?>" method="POST" id="submitForm">
            <input type="hidden" name="sesi_id" value="<?= $sesi_id ?>">
            <button type="button" onclick="confirmSubmit()" class="btn btn-danger w-100">
                <i class="fas fa-paper-plane me-1"></i>Kumpulkan
            </button>
        </form>
    </div>

    <!-- Content -->
    <div class="exam-content">
        <div class="question-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary fs-6">Soal <?= $current ?></span>
                <span class="badge bg-secondary"><?= $soal['tipe_soal'] === 'PG' ? 'Pilihan Ganda' : 'Esai' ?></span>
            </div>

            <div class="question-text"><?= nl2br(e($soal['pertanyaan'])) ?></div>

            <?php if ($soal['tipe_soal'] === 'PG' && !empty($soal['opsi'])): ?>
                <?php foreach ($soal['opsi'] as $opsi):
                    $is_selected = (isset($existing_jawaban[$soal['nomor_soal']]) && $existing_jawaban[$soal['nomor_soal']] === $opsi['opsi_key']);
                ?>
                <button type="button" class="option-btn <?= $is_selected ? 'selected' : '' ?>"
                        onclick="selectAnswer(this, '<?= $soal['nomor_soal'] ?>', '<?= e($opsi['opsi_key']) ?>', <?= $current ?>)">
                    <span class="option-key"><?= e($opsi['opsi_key']) ?></span>
                    <span><?= e($opsi['opsi_text']) ?></span>
                </button>
                <?php endforeach; ?>
            <?php else: ?>
                <textarea class="form-control" rows="5" placeholder="Tulis jawaban esai..."
                          onchange="saveEssayAnswer('<?= $soal['nomor_soal'] ?>', this.value, <?= $current ?>)"><?= e($existing_jawaban[$soal['nomor_soal']] ?? '') ?></textarea>
            <?php endif; ?>

            <!-- Navigation buttons -->
            <div class="d-flex justify-content-between mt-4">
                <?php if ($current > 1): ?>
                <a href="<?= base_url('siswa/ujian/soal.php?sesi_id=' . $sesi_id . '&nomor=' . ($current - 1)) ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Sebelumnya
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <?php if ($current < count($soal_all)): ?>
                <a href="<?= base_url('siswa/ujian/soal.php?sesi_id=' . $sesi_id . '&nomor=' . ($current + 1)) ?>" class="btn btn-primary">
                    Selanjutnya<i class="fas fa-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <button type="button" class="btn btn-danger" onclick="confirmSubmit()">
                    <i class="fas fa-paper-plane me-1"></i>Kumpulkan
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Nav Toggle -->
    <button class="mobile-nav-toggle" onclick="document.getElementById('examSidebar').classList.toggle('show')">
        <i class="fas fa-th"></i>
    </button>

    <!-- Save indicator -->
    <div class="save-indicator" id="saveIndicator">
        <span class="badge bg-success py-2 px-3"><i class="fas fa-check me-1"></i>Tersimpan</span>
    </div>

    <!-- Hidden values -->
    <input type="hidden" id="sesi_ujian_id" value="<?= $sesi_id ?>">
    <input type="hidden" id="sisa_waktu" value="<?= $sesi['sisa_waktu'] ?>">
    <input type="hidden" id="exit_penalty" value="<?= $exit_penalty ?>">
    <input type="hidden" id="max_exit_count" value="<?= $max_exit ?>">
    <input type="hidden" id="current_exit_count" value="<?= $sesi['exit_count'] ?>">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    var sesiId = <?= $sesi_id ?>;
    var sisaWaktu = parseInt(document.getElementById('sisa_waktu').value);
    var exitPenalty = parseInt(document.getElementById('exit_penalty').value);
    var maxExit = parseInt(document.getElementById('max_exit_count').value);
    var exitCount = parseInt(document.getElementById('current_exit_count').value);
    var baseUrl = '<?= base_url('') ?>';

    // ==================== TIMER ====================
    function startTimer() {
        setInterval(function() {
            if (sisaWaktu <= 0) {
                autoSubmit('Waktu habis');
                return;
            }
            sisaWaktu--;
            updateTimerDisplay();
            if (sisaWaktu % 5 === 0) saveRemainingTime();
        }, 1000);
    }

    function updateTimerDisplay() {
        var h = Math.floor(sisaWaktu / 3600);
        var m = Math.floor((sisaWaktu % 3600) / 60);
        var s = sisaWaktu % 60;
        var display = pad(h) + ':' + pad(m) + ':' + pad(s);
        var el = document.getElementById('timer');
        el.textContent = display;
        el.className = 'timer';
        if (sisaWaktu < 300) el.classList.add('danger');
        else if (sisaWaktu < 600) el.classList.add('warning');
    }

    function pad(n) { return n < 10 ? '0' + n : n; }

    // ==================== SAVE ANSWER ====================
    function selectAnswer(btn, nomorSoal, jawaban, displayNomor) {
        btn.parentElement.querySelectorAll('.option-btn').forEach(function(b) { b.classList.remove('selected'); });
        btn.classList.add('selected');
        saveAnswer(nomorSoal, jawaban, displayNomor);
    }

    function saveEssayAnswer(nomorSoal, jawaban, displayNomor) {
        saveAnswer(nomorSoal, jawaban, displayNomor);
    }

    function saveAnswer(nomorSoal, jawaban, displayNomor) {
        $.ajax({
            url: baseUrl + 'api/save_jawaban.php',
            method: 'POST',
            data: { sesi_ujian_id: sesiId, nomor_soal: nomorSoal, jawaban: jawaban },
            success: function() {
                showSaveIndicator();
                var navBtn = document.getElementById('nav-btn-' + displayNomor);
                if (navBtn && jawaban) { navBtn.className = 'nav-btn answered'; }
            }
        });
    }

    function saveRemainingTime() {
        $.ajax({
            url: baseUrl + 'api/get_sisa_waktu.php',
            method: 'POST',
            data: { sesi_ujian_id: sesiId, sisa_waktu: sisaWaktu }
        });
    }

    function showSaveIndicator() {
        var el = document.getElementById('saveIndicator');
        el.style.display = 'block';
        setTimeout(function() { el.style.display = 'none'; }, 1500);
    }

    // ==================== ANTI-CHEAT ====================
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            exitCount++;
            logActivity('TAB_SWITCH', 'Siswa keluar tab');
            addPenalty();
            if (exitCount >= maxExit) {
                autoSubmit('Melebihi batas keluar tab');
            } else {
                alert('PERINGATAN! Anda keluar dari tab. (' + exitCount + '/' + maxExit + ')\nWaktu dikurangi ' + exitPenalty + ' detik.');
                updateExitDisplay();
            }
        }
    });

    document.addEventListener('contextmenu', function(e) { e.preventDefault(); logActivity('RIGHT_CLICK', 'Klik kanan'); });
    document.addEventListener('copy', function(e) { e.preventDefault(); logActivity('COPY_ATTEMPT', 'Copy text'); });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
            logActivity('DEVTOOLS', 'Attempt DevTools');
        }
    });

    function addPenalty() {
        sisaWaktu -= exitPenalty;
        if (sisaWaktu < 0) sisaWaktu = 0;
        $.ajax({
            url: baseUrl + 'api/log_activity.php',
            method: 'POST',
            data: { sesi_ujian_id: sesiId, action: 'penalty', exit_count: exitCount, penalty_seconds: exitPenalty }
        });
    }

    function logActivity(aktivitas, keterangan) {
        $.ajax({
            url: baseUrl + 'api/log_activity.php',
            method: 'POST',
            data: { sesi_ujian_id: sesiId, action: 'log', aktivitas: aktivitas, keterangan: keterangan }
        });
    }

    function updateExitDisplay() {
        document.getElementById('exitBadge').style.display = 'inline';
        document.getElementById('exitDisplay').textContent = exitCount;
    }

    // ==================== SUBMIT ====================
    function confirmSubmit() {
        if (confirm('Yakin ingin mengumpulkan ujian? Jawaban tidak bisa diubah setelah dikumpulkan.')) {
            document.getElementById('submitForm').submit();
        }
    }

    function autoSubmit(reason) {
        alert('Ujian otomatis dikumpulkan: ' + reason);
        document.getElementById('submitForm').submit();
    }

    // ==================== INIT ====================
    updateTimerDisplay();
    if (exitCount > 0) updateExitDisplay();
    startTimer();
    </script>
</body>
</html>
