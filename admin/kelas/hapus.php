<?php
/**
 * Kelas - Hapus
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(base_url('admin/kelas/index.php'), 'danger', 'ID kelas tidak valid.');
}

// Cek apakah kelas masih punya siswa
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($count > 0) {
    redirect(base_url('admin/kelas/index.php'), 'danger', 'Kelas tidak bisa dihapus karena masih memiliki ' . $count . ' siswa.');
}

// Get nama kelas untuk flash message
$stmt = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$kelas = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$kelas) {
    redirect(base_url('admin/kelas/index.php'), 'danger', 'Kelas tidak ditemukan.');
}

// Hapus
$stmt = $conn->prepare("DELETE FROM kelas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect(base_url('admin/kelas/index.php'), 'success', 'Kelas "' . $kelas['nama_kelas'] . '" berhasil dihapus.');
} else {
    redirect(base_url('admin/kelas/index.php'), 'danger', 'Gagal menghapus kelas.');
}
$stmt->close();
