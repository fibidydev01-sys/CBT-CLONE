<?php
/**
 * Bank Soal - Hapus
 * Guru Panel (own data only)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$guru_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(base_url('guru/bank_soal/index.php'), 'danger', 'ID bank soal tidak valid.');
}

// Verify ownership
$stmt = $conn->prepare("SELECT kode_soal, nama_bank FROM bank_soal WHERE id = ? AND created_by_role = 'guru' AND created_by_id = ?");
$stmt->bind_param("ii", $id, $guru_id);
$stmt->execute();
$bank = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bank) {
    redirect(base_url('guru/bank_soal/index.php'), 'danger', 'Bank soal tidak ditemukan atau bukan milik Anda.');
}

// Check if used in ujian
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ujian WHERE bank_soal_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ujian_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($ujian_count > 0) {
    redirect(base_url('guru/bank_soal/index.php'), 'danger',
        'Bank soal tidak bisa dihapus karena sudah dipakai di ' . $ujian_count . ' ujian.');
}

$stmt = $conn->prepare("DELETE FROM bank_soal WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect(base_url('guru/bank_soal/index.php'), 'success',
        'Bank soal "' . $bank['kode_soal'] . ' - ' . $bank['nama_bank'] . '" berhasil dihapus.');
} else {
    redirect(base_url('guru/bank_soal/index.php'), 'danger', 'Gagal menghapus bank soal.');
}
$stmt->close();
