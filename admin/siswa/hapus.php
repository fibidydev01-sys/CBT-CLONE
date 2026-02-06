<?php
/**
 * Siswa - Hapus
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(base_url('admin/siswa/index.php'), 'danger', 'ID siswa tidak valid.');
}

// Get nama siswa
$stmt = $conn->prepare("SELECT nama FROM siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    redirect(base_url('admin/siswa/index.php'), 'danger', 'Siswa tidak ditemukan.');
}

// Hapus (cascade akan hapus sesi_ujian, jawaban_siswa, log_aktivitas)
$stmt = $conn->prepare("DELETE FROM siswa WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect(base_url('admin/siswa/index.php'), 'success', 'Siswa "' . $siswa['nama'] . '" berhasil dihapus.');
} else {
    redirect(base_url('admin/siswa/index.php'), 'danger', 'Gagal menghapus siswa.');
}
$stmt->close();
