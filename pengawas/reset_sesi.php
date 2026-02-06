<?php
/**
 * Pengawas - Reset Sesi Siswa
 * CBT Nusantara
 *
 * Reset sesi ujian siswa ke status belum_mulai
 * Siswa harus mulai ulang ujian
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['pengawas', 'admin']);

$sesi_id = (int)($_GET['sesi_id'] ?? 0);
$ujian_id = (int)($_GET['ujian_id'] ?? 0);

$redirect_url = base_url('pengawas/monitoring.php' . ($ujian_id ? '?id=' . $ujian_id : ''));

if ($sesi_id <= 0) {
    redirect($redirect_url, 'danger', 'Sesi tidak valid.');
}

// Get sesi info
$stmt = $conn->prepare("SELECT su.*, s.nama as nama_siswa, u.alokasi_waktu
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN ujian u ON su.ujian_id = u.id
    WHERE su.id = ?");
$stmt->bind_param("i", $sesi_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    redirect($redirect_url, 'danger', 'Sesi ujian tidak ditemukan.');
}

if ($sesi['status'] !== 'sedang_ujian') {
    redirect($redirect_url, 'warning', 'Hanya sesi yang sedang berlangsung yang bisa direset.');
}

// Reset sesi: status ke belum_mulai, reset waktu, hapus jawaban
$conn->begin_transaction();
try {
    // Reset sesi
    $waktu_baru = $sesi['alokasi_waktu'] * 60;
    $stmt = $conn->prepare("UPDATE sesi_ujian SET
        status = 'belum_mulai',
        waktu_mulai = NULL,
        waktu_selesai = NULL,
        sisa_waktu = ?,
        exit_count = 0,
        penalty_time = 0,
        nilai = NULL
        WHERE id = ?");
    $stmt->bind_param("ii", $waktu_baru, $sesi_id);
    $stmt->execute();
    $stmt->close();

    // Hapus jawaban siswa
    $stmt = $conn->prepare("DELETE FROM jawaban_siswa WHERE sesi_ujian_id = ?");
    $stmt->bind_param("i", $sesi_id);
    $stmt->execute();
    $stmt->close();

    // Clear student's active session
    $stmt = $conn->prepare("UPDATE siswa SET session_id = NULL WHERE id = ?");
    $stmt->bind_param("i", $sesi['siswa_id']);
    $stmt->execute();
    $stmt->close();

    // Log
    $keterangan = 'Sesi direset oleh pengawas: ' . ($_SESSION['nama'] ?? 'Unknown');
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (sesi_ujian_id, aktivitas, keterangan) VALUES (?, 'SESSION_RESET', ?)");
    $stmt->bind_param("is", $sesi_id, $keterangan);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    redirect($redirect_url, 'success', 'Sesi ' . $sesi['nama_siswa'] . ' berhasil direset. Siswa harus mulai ulang.');
} catch (Exception $e) {
    $conn->rollback();
    redirect($redirect_url, 'danger', 'Gagal reset sesi: ' . $e->getMessage());
}
