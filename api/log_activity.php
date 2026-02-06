<?php
/**
 * API - Log Aktivitas & Penalty
 * CBT Nusantara
 *
 * AJAX endpoint: catat aktivitas anti-cheat & terapkan penalty
 * POST: sesi_ujian_id, action ('log' | 'penalty')
 *   - log: aktivitas, keterangan
 *   - penalty: exit_count, penalty_seconds
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
$action = $_POST['action'] ?? '';

if ($sesi_id <= 0) {
    json_response(false, 'Parameter tidak valid');
}

// Verify sesi milik siswa & masih aktif
$stmt = $conn->prepare("SELECT id, sisa_waktu FROM sesi_ujian WHERE id = ? AND siswa_id = ? AND status = 'sedang_ujian'");
$stmt->bind_param("ii", $sesi_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    json_response(false, 'Sesi tidak valid');
}

if ($action === 'penalty') {
    // Apply exit penalty
    $exit_count = (int)($_POST['exit_count'] ?? 0);
    $penalty_seconds = (int)($_POST['penalty_seconds'] ?? 0);

    $new_sisa = max(0, $sesi['sisa_waktu'] - $penalty_seconds);

    $stmt = $conn->prepare("UPDATE sesi_ujian SET exit_count = ?, penalty_time = penalty_time + ?, sisa_waktu = ? WHERE id = ?");
    $stmt->bind_param("iiii", $exit_count, $penalty_seconds, $new_sisa, $sesi_id);
    $stmt->execute();
    $stmt->close();

    // Log penalty activity
    $keterangan = "Exit count: $exit_count, Penalty: {$penalty_seconds}s";
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (sesi_ujian_id, aktivitas, keterangan) VALUES (?, 'EXIT_PENALTY', ?)");
    $stmt->bind_param("is", $sesi_id, $keterangan);
    $stmt->execute();
    $stmt->close();

    json_response(true, 'Penalty diterapkan', ['sisa_waktu' => $new_sisa]);

} elseif ($action === 'log') {
    // Log activity only
    $aktivitas = trim($_POST['aktivitas'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($aktivitas)) {
        json_response(false, 'Aktivitas kosong');
    }

    $stmt = $conn->prepare("INSERT INTO log_aktivitas (sesi_ujian_id, aktivitas, keterangan) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $sesi_id, $aktivitas, $keterangan);
    $stmt->execute();
    $stmt->close();

    json_response(true, 'Activity logged');

} else {
    json_response(false, 'Action tidak valid');
}
