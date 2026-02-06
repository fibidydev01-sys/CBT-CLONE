<?php
/**
 * Soal - Edit
 * Guru Panel (own bank soal only)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$active_menu = 'bank_soal';
$guru_id = $_SESSION['user_id'];
$bank_id = (int)($_GET['bank_id'] ?? $_POST['bank_id'] ?? 0);
$soal_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Verify bank ownership
$stmt = $conn->prepare("SELECT * FROM bank_soal WHERE id = ? AND created_by_role = 'guru' AND created_by_id = ?");
$stmt->bind_param("ii", $bank_id, $guru_id);
$stmt->execute();
$bank = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bank) {
    redirect(base_url('guru/bank_soal/index.php'), 'danger', 'Bank soal tidak ditemukan atau bukan milik Anda.');
}

// Get soal
$stmt = $conn->prepare("SELECT * FROM soal WHERE id = ? AND bank_soal_id = ?");
$stmt->bind_param("ii", $soal_id, $bank_id);
$stmt->execute();
$soal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$soal) {
    redirect(base_url('guru/soal/index.php?bank_id=' . $bank_id), 'danger', 'Soal tidak ditemukan.');
}

$page_title = 'Edit Soal #' . $soal['nomor_soal'];
$error = '';

// Get existing opsi
$existing_opsi = [];
$result = $conn->query("SELECT * FROM opsi_jawaban WHERE soal_id = $soal_id ORDER BY opsi_key");
while ($r = $result->fetch_assoc()) $existing_opsi[$r['opsi_key']] = $r['opsi_text'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_soal = (int)($_POST['nomor_soal'] ?? $soal['nomor_soal']);
    $tipe_soal = $_POST['tipe_soal'] ?? $soal['tipe_soal'];
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');

    if (empty($pertanyaan)) {
        $error = 'Pertanyaan wajib diisi.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE soal SET nomor_soal=?, tipe_soal=?, pertanyaan=? WHERE id=?");
            $stmt->bind_param("issi", $nomor_soal, $tipe_soal, $pertanyaan, $soal_id);
            $stmt->execute();
            $stmt->close();

            // Delete & reinsert opsi
            $conn->query("DELETE FROM opsi_jawaban WHERE soal_id = $soal_id");

            if ($tipe_soal === 'PG') {
                $opsi_keys = ['A', 'B', 'C', 'D', 'E'];
                $stmt = $conn->prepare("INSERT INTO opsi_jawaban (soal_id, opsi_key, opsi_text) VALUES (?, ?, ?)");
                foreach ($opsi_keys as $key) {
                    $text = trim($_POST['opsi_' . $key] ?? '');
                    if (!empty($text)) {
                        $stmt->bind_param("iss", $soal_id, $key, $text);
                        $stmt->execute();
                    }
                }
                $stmt->close();
            }

            $conn->commit();
            redirect(base_url('guru/soal/index.php?bank_id=' . $bank_id), 'success', 'Soal berhasil diupdate.');
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Gagal mengupdate: ' . $e->getMessage();
        }
    }
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
                <h4><i class="fas fa-edit me-2"></i>Edit Soal #<?= $soal['nomor_soal'] ?></h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('guru/soal/index.php?bank_id=' . $bank_id) ?>"><?= e($bank['kode_soal']) ?></a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $soal_id ?>">
            <input type="hidden" name="bank_id" value="<?= $bank_id ?>">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header">Pertanyaan</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Nomor Soal</label>
                                    <input type="number" name="nomor_soal" class="form-control" min="1" required value="<?= e($_POST['nomor_soal'] ?? $soal['nomor_soal']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipe Soal</label>
                                    <select name="tipe_soal" id="tipeSoal" class="form-select" onchange="toggleOpsi()">
                                        <option value="PG" <?= ($_POST['tipe_soal'] ?? $soal['tipe_soal']) === 'PG' ? 'selected' : '' ?>>Pilihan Ganda</option>
                                        <option value="ESAI" <?= ($_POST['tipe_soal'] ?? $soal['tipe_soal']) === 'ESAI' ? 'selected' : '' ?>>Esai</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                                <textarea name="pertanyaan" class="form-control" rows="5" required><?= e($_POST['pertanyaan'] ?? $soal['pertanyaan']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3" id="opsiCard">
                        <div class="card-header">Opsi Jawaban</div>
                        <div class="card-body">
                            <?php
                            $opsi_keys = ['A', 'B', 'C', 'D', 'E'];
                            $colors = ['primary', 'success', 'warning', 'info', 'secondary'];
                            foreach ($opsi_keys as $i => $key):
                            ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <span class="badge bg-<?= $colors[$i] ?> me-1"><?= $key ?></span>
                                    Opsi <?= $key ?> <?= $i < 4 ? '<span class="text-danger">*</span>' : '<small class="text-muted">(opsional)</small>' ?>
                                </label>
                                <input type="text" name="opsi_<?= $key ?>" class="form-control"
                                       value="<?= e($_POST['opsi_' . $key] ?? $existing_opsi[$key] ?? '') ?>"
                                       <?= $i < 4 ? 'required' : '' ?>>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-1"></i>Update Soal</button>
                        <a href="<?= base_url('guru/soal/index.php?bank_id=' . $bank_id) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$inline_js = "
function toggleOpsi() {
    var tipe = document.getElementById('tipeSoal').value;
    var card = document.getElementById('opsiCard');
    var inputs = card.querySelectorAll('input');
    if (tipe === 'ESAI') {
        card.style.display = 'none';
        inputs.forEach(function(inp) { inp.removeAttribute('required'); });
    } else {
        card.style.display = 'block';
        inputs.forEach(function(inp, i) { if (i < 4) inp.setAttribute('required', ''); });
    }
}
toggleOpsi();
";
include __DIR__ . '/../../includes/footer.php';
?>
