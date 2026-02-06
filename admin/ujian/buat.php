<?php
/**
 * Ujian - Buat
 * Admin Panel
 *
 * Step 1: Isi form ujian + pilih bank soal + pilih kelas
 * Step 2: Atur kunci jawaban (setelah simpan)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Buat Ujian';
$active_menu = 'ujian';
$error = '';

// Get bank soal yang punya soal
$bank_list = $conn->query("SELECT bs.*,
    (SELECT COUNT(*) FROM soal s WHERE s.bank_soal_id = bs.id) as jumlah_soal
    FROM bank_soal bs
    HAVING jumlah_soal > 0
    ORDER BY bs.nama_bank ASC");

// Get kelas
$kelas_list = $conn->query("SELECT k.*,
    (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) as jumlah_siswa
    FROM kelas k ORDER BY k.nama_kelas ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        empty($jam_mulai) || empty($jam_selesai) || $jumlah_soal <= 0 || $alokasi_waktu <= 0) {
        $error = 'Semua field wajib diisi.';
    } elseif (empty($kelas_ids)) {
        $error = 'Pilih minimal 1 kelas peserta.';
    } else {
        // Verify bank soal punya cukup soal
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM soal WHERE bank_soal_id = ?");
        $stmt->bind_param("i", $bank_soal_id);
        $stmt->execute();
        $total_available = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        if ($jumlah_soal > $total_available) {
            $error = "Jumlah soal melebihi yang tersedia ($total_available soal).";
        } else {
            $conn->begin_transaction();

            try {
                $role = $_SESSION['role'];
                $user_id = $_SESSION['user_id'];

                // Insert ujian
                $stmt = $conn->prepare("INSERT INTO ujian
                    (bank_soal_id, nama_ujian, tanggal_ujian, jam_mulai, jam_selesai, jumlah_soal, alokasi_waktu, acak_soal, acak_jawaban, status, created_by_role, created_by_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?)");
                $stmt->bind_param("issssiiissi",
                    $bank_soal_id, $nama_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai,
                    $jumlah_soal, $alokasi_waktu, $acak_soal, $acak_jawaban, $role, $user_id);
                $stmt->execute();
                $ujian_id = $stmt->insert_id;
                $stmt->close();

                // Insert ujian_kelas
                $stmt = $conn->prepare("INSERT INTO ujian_kelas (ujian_id, kelas_id) VALUES (?, ?)");
                foreach ($kelas_ids as $kid) {
                    $kid = (int)$kid;
                    $stmt->bind_param("ii", $ujian_id, $kid);
                    $stmt->execute();
                }
                $stmt->close();

                $conn->commit();
                redirect(base_url('admin/ujian/edit.php?id=' . $ujian_id . '&tab=kunci'), 'success',
                    'Ujian berhasil dibuat. Silakan atur kunci jawaban.');

            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Gagal membuat ujian: ' . $e->getMessage();
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
                <h4><i class="fas fa-plus me-2"></i>Buat Ujian Baru</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/ujian/index.php') ?>">Ujian</a></li>
                        <li class="breadcrumb-item active">Buat</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Info Ujian -->
                    <div class="card mb-3">
                        <div class="card-header"><i class="fas fa-info-circle me-2"></i>Informasi Ujian</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Ujian <span class="text-danger">*</span></label>
                                <input type="text" name="nama_ujian" class="form-control" required
                                       placeholder="Contoh: UAS Matematika Kelas IX Semester 1"
                                       value="<?= e($_POST['nama_ujian'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bank Soal <span class="text-danger">*</span></label>
                                <select name="bank_soal_id" id="bankSoal" class="form-select" required onchange="updateMaxSoal()">
                                    <option value="">-- Pilih Bank Soal --</option>
                                    <?php while ($bs = $bank_list->fetch_assoc()): ?>
                                    <option value="<?= $bs['id'] ?>" data-soal="<?= $bs['jumlah_soal'] ?>"
                                        <?= ($_POST['bank_soal_id'] ?? '') == $bs['id'] ? 'selected' : '' ?>>
                                        [<?= e($bs['kode_soal']) ?>] <?= e($bs['nama_bank']) ?> (<?= $bs['jumlah_soal'] ?> soal)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tanggal Ujian <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_ujian" class="form-control" required
                                           value="<?= e($_POST['tanggal_ujian'] ?? date('Y-m-d')) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                                    <input type="time" name="jam_mulai" class="form-control" required
                                           value="<?= e($_POST['jam_mulai'] ?? '08:00') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                                    <input type="time" name="jam_selesai" class="form-control" required
                                           value="<?= e($_POST['jam_selesai'] ?? '10:00') ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Soal <span class="text-danger">*</span></label>
                                    <input type="number" name="jumlah_soal" id="jumlahSoal" class="form-control" min="1" required
                                           value="<?= e($_POST['jumlah_soal'] ?? '') ?>"
                                           placeholder="Jumlah soal yang ditampilkan">
                                    <div class="form-text" id="soalInfo">Pilih bank soal dulu.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alokasi Waktu (menit) <span class="text-danger">*</span></label>
                                    <input type="number" name="alokasi_waktu" class="form-control" min="5" required
                                           value="<?= e($_POST['alokasi_waktu'] ?? '60') ?>"
                                           placeholder="Durasi ujian dalam menit">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="acak_soal" id="acakSoal"
                                               <?= ($_POST['acak_soal'] ?? '') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="acakSoal">Acak urutan soal per siswa</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="acak_jawaban" id="acakJawaban"
                                               <?= ($_POST['acak_jawaban'] ?? '') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="acakJawaban">Acak urutan jawaban per siswa</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pilih Kelas -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="fas fa-school me-2"></i>Kelas Peserta <span class="text-danger">*</span>
                            <button type="button" class="btn btn-sm btn-outline-primary float-end" onclick="toggleAllKelas()">Pilih Semua</button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php while ($k = $kelas_list->fetch_assoc()): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input kelas-cb" type="checkbox" name="kelas_ids[]"
                                               value="<?= $k['id'] ?>" id="kelas<?= $k['id'] ?>"
                                               <?= in_array($k['id'], $_POST['kelas_ids'] ?? []) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="kelas<?= $k['id'] ?>">
                                            <?= e($k['nama_kelas']) ?>
                                            <small class="text-muted">(<?= $k['jumlah_siswa'] ?> siswa)</small>
                                        </label>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header">Langkah</div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary rounded-circle me-2" style="width:28px;height:28px;line-height:20px;">1</span>
                                <span class="fw-semibold">Isi form ujian</span>
                            </div>
                            <div class="d-flex align-items-center mb-3 text-muted">
                                <span class="badge bg-secondary rounded-circle me-2" style="width:28px;height:28px;line-height:20px;">2</span>
                                <span>Atur kunci jawaban</span>
                            </div>
                            <div class="d-flex align-items-center text-muted">
                                <span class="badge bg-secondary rounded-circle me-2" style="width:28px;height:28px;line-height:20px;">3</span>
                                <span>Aktifkan ujian</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-1"></i>Simpan & Atur Kunci
                        </button>
                        <a href="<?= base_url('admin/ujian/index.php') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$inline_js = "
function updateMaxSoal() {
    var sel = document.getElementById('bankSoal');
    var opt = sel.options[sel.selectedIndex];
    var max = opt.getAttribute('data-soal') || 0;
    var inp = document.getElementById('jumlahSoal');
    inp.max = max;
    document.getElementById('soalInfo').textContent = max > 0 ? 'Tersedia: ' + max + ' soal' : 'Pilih bank soal dulu.';
    if (!inp.value) inp.value = max;
}

function toggleAllKelas() {
    var cbs = document.querySelectorAll('.kelas-cb');
    var allChecked = Array.from(cbs).every(function(cb) { return cb.checked; });
    cbs.forEach(function(cb) { cb.checked = !allChecked; });
}

updateMaxSoal();
";
include __DIR__ . '/../../includes/footer.php';
?>
