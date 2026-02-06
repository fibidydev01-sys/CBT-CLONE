<?php
/**
 * API - Save Jawaban Siswa
 * CBT Nusantara
 *
 * AJAX endpoint: simpan/update jawaban per soal
 * POST: sesi_ujian_id, nomor_soal, jawaban
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
$nomor_soal = (int)($_POST['nomor_soal'] ?? 0);
$jawaban = trim($_POST['jawaban'] ?? '');

if ($sesi_id <= 0 || $nomor_soal <= 0) {
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

// Upsert jawaban (INSERT or UPDATE)
$stmt = $conn->prepare("INSERT INTO jawaban_siswa (sesi_ujian_id, nomor_soal, jawaban)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE jawaban = VALUES(jawaban), updated_at = NOW()");
$stmt->bind_param("iis", $sesi_id, $nomor_soal, $jawaban);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    json_response(true, 'Jawaban tersimpan');
} else {
    json_response(false, 'Gagal menyimpan jawaban');
}
