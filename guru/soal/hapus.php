<?php
/**
 * Soal - Hapus
 * Guru Panel (own bank soal only)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$guru_id = $_SESSION['user_id'];
$bank_id = (int)($_GET['bank_id'] ?? 0);
$soal_id = (int)($_GET['id'] ?? 0);

// Verify bank ownership
$stmt = $conn->prepare("SELECT id FROM bank_soal WHERE id = ? AND created_by_role = 'guru' AND created_by_id = ?");
$stmt->bind_param("ii", $bank_id, $guru_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    redirect(base_url('guru/bank_soal/index.php'), 'danger', 'Akses ditolak.');
}
$stmt->close();

// Get soal info
$stmt = $conn->prepare("SELECT nomor_soal FROM soal WHERE id = ? AND bank_soal_id = ?");
$stmt->bind_param("ii", $soal_id, $bank_id);
$stmt->execute();
$soal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$soal) {
    redirect(base_url('guru/soal/index.php?bank_id=' . $bank_id), 'danger', 'Soal tidak ditemukan.');
}

$stmt = $conn->prepare("DELETE FROM soal WHERE id = ?");
$stmt->bind_param("i", $soal_id);

if ($stmt->execute()) {
    redirect(base_url('guru/soal/index.php?bank_id=' . $bank_id), 'success', 'Soal nomor ' . $soal['nomor_soal'] . ' berhasil dihapus.');
} else {
    redirect(base_url('guru/soal/index.php?bank_id=' . $bank_id), 'danger', 'Gagal menghapus soal.');
}
$stmt->close();
