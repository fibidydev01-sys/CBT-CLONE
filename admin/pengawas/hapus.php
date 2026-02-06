<?php
/**
 * Pengawas - Hapus
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(base_url('admin/pengawas/index.php'), 'danger', 'ID pengawas tidak valid.');
}

// Get nama
$stmt = $conn->prepare("SELECT nama FROM pengawas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pengawas = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pengawas) {
    redirect(base_url('admin/pengawas/index.php'), 'danger', 'Pengawas tidak ditemukan.');
}

$stmt = $conn->prepare("DELETE FROM pengawas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect(base_url('admin/pengawas/index.php'), 'success', 'Pengawas "' . $pengawas['nama'] . '" berhasil dihapus.');
} else {
    redirect(base_url('admin/pengawas/index.php'), 'danger', 'Gagal menghapus pengawas.');
}
$stmt->close();
