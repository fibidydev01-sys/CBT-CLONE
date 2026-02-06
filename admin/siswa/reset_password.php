<?php
/**
 * Siswa - Reset Password
 * Admin Panel
 *
 * Reset password ke default: username siswa
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

// Get data siswa
$stmt = $conn->prepare("SELECT nama, username FROM siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    redirect(base_url('admin/siswa/index.php'), 'danger', 'Siswa tidak ditemukan.');
}

// Reset password ke username
$new_password = $siswa['username'];
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE siswa SET password = ?, password_plain = ?, session_id = NULL WHERE id = ?");
$stmt->bind_param("ssi", $hashed, $new_password, $id);

if ($stmt->execute()) {
    redirect(base_url('admin/siswa/index.php'), 'success',
        'Password siswa "' . $siswa['nama'] . '" berhasil direset ke: ' . $new_password);
} else {
    redirect(base_url('admin/siswa/index.php'), 'danger', 'Gagal mereset password.');
}
$stmt->close();
