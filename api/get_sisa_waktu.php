<?php
/**
 * API - Sync Sisa Waktu
 * CBT Nusantara
 *
 * AJAX endpoint: update sisa waktu dari client timer
 * POST: sesi_ujian_id, sisa_waktu (detik)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'siswa') {
    json_response(false, 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed');
}

$siswa_id = $_SESSION['user_id'];
$sesi_id = (int)($_POST['sesi_ujian_id'] ?? 0);
$sisa_waktu = (int)($_POST['sisa_waktu'] ?? 0);

if ($sesi_id <= 0) {
    json_response(false, 'Parameter tidak valid');
}

// Verify sesi milik siswa & masih aktif
$stmt = $conn->prepare("SELECT id FROM sesi_ujian WHERE id = ? AND siswa_id = ? AND status = 'sedang_ujian'");
$stmt->bind_param("ii", $sesi_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    json_response(false, 'Sesi tidak valid');
}

// Update sisa waktu
if ($sisa_waktu < 0) $sisa_waktu = 0;

$stmt = $conn->prepare("UPDATE sesi_ujian SET sisa_waktu = ? WHERE id = ?");
$stmt->bind_param("ii", $sisa_waktu, $sesi_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    json_response(true, 'Waktu tersinkronisasi', ['sisa_waktu' => $sisa_waktu]);
} else {
    json_response(false, 'Gagal update waktu');
}
