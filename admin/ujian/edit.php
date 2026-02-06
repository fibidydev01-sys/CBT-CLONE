<?php
/**
 * Ujian - Edit (+ Kunci Jawaban)
 * Admin Panel
 *
 * Tab 1: Info ujian
 * Tab 2: Kunci jawaban
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$active_menu = 'ujian';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$tab = $_GET['tab'] ?? $_POST['tab'] ?? 'info';

// Get ujian data
$stmt = $conn->prepare("SELECT u.*, bs.kode_soal, bs.nama_bank FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ujian) {
    redirect(base_url('admin/ujian/index.php'), 'danger', 'Ujian tidak ditemukan.');
}

if ($ujian['status'] !== 'draft') {
    redirect(base_url('admin/ujian/index.php'), 'warning', 'Ujian yang sudah aktif/selesai tidak bisa diedit.');
}

$page_title = 'Edit Ujian - ' . $ujian['nama_ujian'];
$error = '';

// Get kelas terpilih
$selected_kelas = [];
$result = $conn->query("SELECT kelas_id FROM ujian_kelas WHERE ujian_id = $id");
while ($r = $result->fetch_assoc()) $selected_kelas[] = $r['kelas_id'];

// Get bank soal list
$bank_list = $conn->query("SELECT bs.*,
    (SELECT COUNT(*) FROM soal s WHERE s.bank_soal_id = bs.id) as jumlah_soal
    FROM bank_soal bs HAVING jumlah_soal > 0 ORDER BY bs.nama_bank ASC");

// Get kelas list
$kelas_list = $conn->query("SELECT k.*,
    (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) as jumlah_siswa
    FROM kelas k ORDER BY k.nama_kelas ASC");

// Get soal untuk kunci jawaban
$soal_list = $conn->query("SELECT s.* FROM soal s
    WHERE s.bank_soal_id = {$ujian['bank_soal_id']}
    ORDER BY s.nomor_soal ASC
    LIMIT {$ujian['jumlah_soal']}");

// Get existing kunci jawaban
$existing_kunci = [];
$result = $conn->query("SELECT * FROM kunci_jawaban WHERE ujian_id = $id");
while ($r = $result->fetch_assoc()) $existing_kunci[$r['nomor_soal']] = $r;

// Get opsi per soal
$soal_opsi = [];
$result = $conn->query("SELECT oj.* FROM opsi_jawaban oj
    JOIN soal s ON oj.soal_id = s.id
    WHERE s.bank_soal_id = {$ujian['bank_soal_id']}
    ORDER BY oj.soal_id, oj.opsi_key");
while ($r = $result->fetch_assoc()) {
    $soal_opsi[$r['soal_id']][$r['opsi_key']] = $r['opsi_text'];
}

// HANDLE POST: Info tab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'info') {
    $nama_ujian = trim($_POST['nama_ujian'] ?? '');
    $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
    $tanggal_ujian = $_POST['tanggal_ujian'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $jumlah_soal = (int)($_POST['jumlah_soal'] ?? 0);
    $alokasi_waktu = (int)($_POST['alokasi_waktu'] ?? 60);
    $acak_soal = isset($_POST['acak_soal']) ? 1 : 0;
    $acak_jawaban = isset($_POST['acak_jawaban']) ? 1 : 0;
    $kelas_ids = $_POST['kelas_ids'] ?? [];

    if (empty($nama_ujian) || $bank_soal_id <= 0 || empty($tanggal_ujian) ||
        empty($jam_mulai) || empty($jam_selesai) || $jumlah_soal <= 0 || empty($kelas_ids)) {
        $error = 'Semua field wajib diisi dan pilih minimal 1 kelas.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE ujian SET
                bank_soal_id=?, nama_ujian=?, tanggal_ujian=?, jam_mulai=?, jam_selesai=?,
                jumlah_soal=?, alokasi_waktu=?, acak_soal=?, acak_jawaban=? WHERE id=?");
            $stmt->bind_param("issssiiiii",
                $bank_soal_id, $nama_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai,
                $jumlah_soal, $alokasi_waktu, $acak_soal, $acak_jawaban, $id);
            $stmt->execute();
            $stmt->close();

            // Update kelas
            $conn->query("DELETE FROM ujian_kelas WHERE ujian_id = $id");
            $stmt = $conn->prepare("INSERT INTO ujian_kelas (ujian_id, kelas_id) VALUES (?, ?)");
            foreach ($kelas_ids as $kid) {
                $kid = (int)$kid;
                $stmt->bind_param("ii", $id, $kid);
                $stmt->execute();
            }
            $stmt->close();

            $conn->commit();
            redirect(base_url('admin/ujian/edit.php?id=' . $id . '&tab=kunci'), 'success', 'Ujian berhasil diupdate.');
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Gagal mengupdate: ' . $e->getMessage();
        }
    }
}

// HANDLE POST: Kunci jawaban tab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'kunci') {
    $conn->begin_transaction();
    try {
        // Delete existing
        $conn->query("DELETE FROM kunci_jawaban WHERE ujian_id = $id");

        $stmt = $conn->prepare("INSERT INTO kunci_jawaban (ujian_id, nomor_soal, jawaban_benar, skor) VALUES (?, ?, ?, ?)");

        $nomor_list = $_POST['nomor_soal'] ?? [];
        foreach ($nomor_list as $idx => $nomor) {
            $jawaban = $_POST['jawaban_benar'][$idx] ?? '';
            $skor = floatval($_POST['skor'][$idx] ?? 1);
            if (!empty($jawaban)) {
                $nomor = (int)$nomor;
                $stmt->bind_param("iisd", $id, $nomor, $jawaban, $skor);
                $stmt->execute();
            }
        }
        $stmt->close();

        $conn->commit();
        redirect(base_url('admin/ujian/edit.php?id=' . $id . '&tab=kunci'), 'success', 'Kunci jawaban berhasil disimpan.');
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Gagal menyimpan kunci: ' . $e->getMessage();
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
                <h4><i class="fas fa-edit me-2"></i>Edit Ujian</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/ujian/index.php') ?>">Ujian</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
            <div>
                <span class="badge bg-secondary fs-6"><?= e(ucfirst($ujian['status'])) ?></span>
            </div>
        </div>

        <?php render_flash(); ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'info' ? 'active' : '' ?>" href="<?= base_url('admin/ujian/edit.php?id=' . $id . '&tab=info') ?>">
                    <i class="fas fa-info-circle me-1"></i>Info Ujian
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'kunci' ? 'active' : '' ?>" href="<?= base_url('admin/ujian/edit.php?id=' . $id . '&tab=kunci') ?>">
                    <i class="fas fa-key me-1"></i>Kunci Jawaban
                </a>
            </li>
        </ul>

        <?php if ($tab === 'info'): ?>
        <!-- TAB: INFO UJIAN -->
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="tab" value="info">

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header">Informasi Ujian</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Ujian <span class="text-danger">*</span></label>
                                <input type="text" name="nama_ujian" class="form-control" required
                                       value="<?= e($_POST['nama_ujian'] ?? $ujian['nama_ujian']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bank Soal <span class="text-danger">*</span></label>
                                <select name="bank_soal_id" id="bankSoal" class="form-select" required onchange="updateMaxSoal()">
                                    <option value="">-- Pilih --</option>
                                    <?php while ($bs = $bank_list->fetch_assoc()): ?>
                                    <option value="<?= $bs['id'] ?>" data-soal="<?= $bs['jumlah_soal'] ?>"
                                        <?= ($_POST['bank_soal_id'] ?? $ujian['bank_soal_id']) == $bs['id'] ? 'selected' : '' ?>>
                                        [<?= e($bs['kode_soal']) ?>] <?= e($bs['nama_bank']) ?> (<?= $bs['jumlah_soal'] ?> soal)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_ujian" class="form-control" required
                                           value="<?= e($_POST['tanggal_ujian'] ?? $ujian['tanggal_ujian']) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                    <input type="time" name="jam_mulai" class="form-control" required
                                           value="<?= e($_POST['jam_mulai'] ?? substr($ujian['jam_mulai'], 0, 5)) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                    <input type="time" name="jam_selesai" class="form-control" required
                                           value="<?= e($_POST['jam_selesai'] ?? substr($ujian['jam_selesai'], 0, 5)) ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Soal <span class="text-danger">*</span></label>
                                    <input type="number" name="jumlah_soal" id="jumlahSoal" class="form-control" min="1" required
                                           value="<?= e($_POST['jumlah_soal'] ?? $ujian['jumlah_soal']) ?>">
                                    <div class="form-text" id="soalInfo"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alokasi Waktu (menit) <span class="text-danger">*</span></label>
                                    <input type="number" name="alokasi_waktu" class="form-control" min="5" required
                                           value="<?= e($_POST['alokasi_waktu'] ?? $ujian['alokasi_waktu']) ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="acak_soal"
                                               <?= ($_POST['acak_soal'] ?? $ujian['acak_soal']) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Acak urutan soal</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="acak_jawaban"
                                               <?= ($_POST['acak_jawaban'] ?? $ujian['acak_jawaban']) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Acak urutan jawaban</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            Kelas Peserta <span class="text-danger">*</span>
                            <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="toggleAllKelas()">Pilih Semua</button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php while ($k = $kelas_list->fetch_assoc()): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input kelas-cb" type="checkbox" name="kelas_ids[]"
                                               value="<?= $k['id'] ?>" id="kelas<?= $k['id'] ?>"
                                               <?= in_array($k['id'], $_POST['kelas_ids'] ?? $selected_kelas) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="kelas<?= $k['id'] ?>">
                                            <?= e($k['nama_kelas']) ?> <small class="text-muted">(<?= $k['jumlah_siswa'] ?>)</small>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-1"></i>Update Info
                        </button>
                        <a href="<?= base_url('admin/ujian/index.php') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <?php else: ?>
        <!-- TAB: KUNCI JAWABAN -->
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="tab" value="kunci">

            <div class="row">
                <div class="col-lg-9">
                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="fas fa-key me-2"></i>Kunci Jawaban - <?= e($ujian['nama_ujian']) ?>
                            <span class="badge bg-info float-end"><?= $ujian['jumlah_soal'] ?> soal</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="60">No</th>
                                            <th>Pertanyaan</th>
                                            <th width="250">Kunci Jawaban</th>
                                            <th width="80">Skor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $soal_list->data_seek(0); $idx = 0; while ($s = $soal_list->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?= $s['nomor_soal'] ?></span>
                                                <input type="hidden" name="nomor_soal[<?= $idx ?>]" value="<?= $s['nomor_soal'] ?>">
                                            </td>
                                            <td>
                                                <div class="small" style="max-width:400px;">
                                                    <?= e(mb_substr(strip_tags($s['pertanyaan']), 0, 100)) ?><?= mb_strlen($s['pertanyaan']) > 100 ? '...' : '' ?>
                                                </div>
                                                <?php if ($s['tipe_soal'] === 'PG' && isset($soal_opsi[$s['id']])): ?>
                                                <div class="mt-1">
                                                    <?php foreach ($soal_opsi[$s['id']] as $key => $text): ?>
                                                    <small class="d-block text-muted"><strong><?= $key ?>.</strong> <?= e(mb_substr($text, 0, 50)) ?></small>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($s['tipe_soal'] === 'PG'): ?>
                                                <div class="d-flex gap-1">
                                                    <?php
                                                    $opsi_keys = isset($soal_opsi[$s['id']]) ? array_keys($soal_opsi[$s['id']]) : ['A','B','C','D','E'];
                                                    $current_kunci = $existing_kunci[$s['nomor_soal']]['jawaban_benar'] ?? '';
                                                    foreach ($opsi_keys as $key):
                                                    ?>
                                                    <label class="btn btn-sm <?= $current_kunci === $key ? 'btn-primary' : 'btn-outline-secondary' ?> kunci-btn">
                                                        <input type="radio" name="jawaban_benar[<?= $idx ?>]" value="<?= $key ?>"
                                                               <?= $current_kunci === $key ? 'checked' : '' ?>
                                                               style="display:none;" onchange="highlightKunci(this)">
                                                        <?= $key ?>
                                                    </label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php else: ?>
                                                <input type="text" name="jawaban_benar[<?= $idx ?>]" class="form-control form-control-sm"
                                                       placeholder="Jawaban esai" value="<?= e($existing_kunci[$s['nomor_soal']]['jawaban_benar'] ?? '') ?>">
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <input type="number" name="skor[<?= $idx ?>]" class="form-control form-control-sm" step="0.5" min="0"
                                                       value="<?= e($existing_kunci[$s['nomor_soal']]['skor'] ?? '1') ?>">
                                            </td>
                                        </tr>
                                        <?php $idx++; endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card mb-3">
                        <div class="card-header">Status Kunci</div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Terisi:</strong> <span class="badge bg-success"><?= count($existing_kunci) ?></span> / <?= $ujian['jumlah_soal'] ?></p>
                            <?php if (count($existing_kunci) < $ujian['jumlah_soal']): ?>
                            <div class="alert alert-warning small mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Lengkapi semua kunci jawaban sebelum mengaktifkan ujian.
                            </div>
                            <?php else: ?>
                            <div class="alert alert-success small mb-0">
                                <i class="fas fa-check-circle me-1"></i>
                                Semua kunci jawaban sudah terisi.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-1"></i>Simpan Kunci
                        </button>
                        <?php if (count($existing_kunci) >= $ujian['jumlah_soal']): ?>
                        <a href="<?= base_url('admin/ujian/aktifkan.php?id=' . $id) ?>" class="btn btn-success"
                           data-confirm="Aktifkan ujian ini?">
                            <i class="fas fa-play me-1"></i>Aktifkan Ujian
                        </a>
                        <?php endif; ?>
                        <a href="<?= base_url('admin/ujian/index.php') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$inline_js = "
function updateMaxSoal() {
    var sel = document.getElementById('bankSoal');
    if (!sel) return;
    var opt = sel.options[sel.selectedIndex];
    var max = opt ? opt.getAttribute('data-soal') : 0;
    var info = document.getElementById('soalInfo');
    if (info) info.textContent = max > 0 ? 'Tersedia: ' + max + ' soal' : '';
}

function toggleAllKelas() {
    var cbs = document.querySelectorAll('.kelas-cb');
    var allChecked = Array.from(cbs).every(function(cb) { return cb.checked; });
    cbs.forEach(function(cb) { cb.checked = !allChecked; });
}

function highlightKunci(radio) {
    var parent = radio.closest('td');
    parent.querySelectorAll('.kunci-btn').forEach(function(btn) {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    });
    radio.parentElement.classList.remove('btn-outline-secondary');
    radio.parentElement.classList.add('btn-primary');
}

if (document.getElementById('bankSoal')) updateMaxSoal();
";
include __DIR__ . '/../../includes/footer.php';
?>
