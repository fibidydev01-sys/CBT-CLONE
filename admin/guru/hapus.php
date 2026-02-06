<?php
/**
 * Guru - Hapus
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(base_url('admin/guru/index.php'), 'danger', 'ID guru tidak valid.');
}

// Cek apakah guru masih punya bank soal / ujian
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bank_soal WHERE created_by_role = 'guru' AND created_by_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$bank_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ujian WHERE created_by_role = 'guru' AND created_by_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ujian_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($bank_count > 0 || $ujian_count > 0) {
    redirect(base_url('admin/guru/index.php'), 'danger',
        'Guru tidak bisa dihapus karena masih memiliki ' . $bank_count . ' bank soal dan ' . $ujian_count . ' ujian.');
}

// Get nama
$stmt = $conn->prepare("SELECT nama FROM guru WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$guru = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$guru) {
    redirect(base_url('admin/guru/index.php'), 'danger', 'Guru tidak ditemukan.');
}

$stmt = $conn->prepare("DELETE FROM guru WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect(base_url('admin/guru/index.php'), 'success', 'Guru "' . $guru['nama'] . '" berhasil dihapus.');
} else {
    redirect(base_url('admin/guru/index.php'), 'danger', 'Gagal menghapus guru.');
}
$stmt->close();
