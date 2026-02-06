<?php
/**
 * Soal - Hapus
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
$bank_id = (int)($_GET['bank_id'] ?? 0);

if ($id <= 0 || $bank_id <= 0) {
    redirect(base_url('admin/bank_soal/index.php'), 'danger', 'Parameter tidak valid.');
}

// Get soal info
$stmt = $conn->prepare("SELECT nomor_soal FROM soal WHERE id = ? AND bank_soal_id = ?");
$stmt->bind_param("ii", $id, $bank_id);
$stmt->execute();
$soal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$soal) {
    redirect(base_url('admin/soal/index.php?bank_id=' . $bank_id), 'danger', 'Soal tidak ditemukan.');
}

// Hapus (cascade ke opsi_jawaban)
$stmt = $conn->prepare("DELETE FROM soal WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect(base_url('admin/soal/index.php?bank_id=' . $bank_id), 'success',
        'Soal nomor ' . $soal['nomor_soal'] . ' berhasil dihapus.');
} else {
    redirect(base_url('admin/soal/index.php?bank_id=' . $bank_id), 'danger', 'Gagal menghapus soal.');
}
$stmt->close();
