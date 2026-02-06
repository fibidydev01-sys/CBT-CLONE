<?php
/**
 * Siswa - Submit Ujian
 * CBT Nusantara
 *
 * Selesaikan sesi, hitung nilai
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['siswa']);

$siswa_id = $_SESSION['user_id'];
$sesi_id = (int)($_POST['sesi_id'] ?? 0);

if ($sesi_id <= 0) {
    redirect(base_url('siswa/index.php'), 'danger', 'Sesi tidak valid.');
}

// Verify sesi belongs to student
$stmt = $conn->prepare("SELECT su.*, u.id as ujian_id FROM sesi_ujian su
    JOIN ujian u ON su.ujian_id = u.id
    WHERE su.id = ? AND su.siswa_id = ? AND su.status = 'sedang_ujian'");
$stmt->bind_param("ii", $sesi_id, $siswa_id);
$stmt->execute();
$sesi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sesi) {
    redirect(base_url('siswa/index.php'), 'danger', 'Sesi ujian tidak ditemukan atau sudah selesai.');
}

$ujian_id = $sesi['ujian_id'];

// Update status sesi
$stmt = $conn->prepare("UPDATE sesi_ujian SET status = 'selesai', waktu_selesai = NOW() WHERE id = ?");
$stmt->bind_param("i", $sesi_id);
$stmt->execute();
$stmt->close();

// Hitung nilai
$kunci = [];
$result = $conn->query("SELECT * FROM kunci_jawaban WHERE ujian_id = $ujian_id");
while ($r = $result->fetch_assoc()) $kunci[$r['nomor_soal']] = $r;

$result = $conn->query("SELECT * FROM jawaban_siswa WHERE sesi_ujian_id = $sesi_id");

$total_skor = 0;
$max_skor = 0;
foreach ($kunci as $k) $max_skor += (float)$k['skor'];

while ($j = $result->fetch_assoc()) {
    $nomor = $j['nomor_soal'];
    if (isset($kunci[$nomor])) {
        $is_correct = (strtoupper(trim($j['jawaban'])) === strtoupper(trim($kunci[$nomor]['jawaban_benar']))) ? 1 : 0;
        $skor = $is_correct ? (float)$kunci[$nomor]['skor'] : 0;
        $total_skor += $skor;

        $stmt = $conn->prepare("UPDATE jawaban_siswa SET is_correct = ?, skor_diperoleh = ? WHERE id = ?");
        $stmt->bind_param("idi", $is_correct, $skor, $j['id']);
        $stmt->execute();
        $stmt->close();
    }
}

$nilai = $max_skor > 0 ? ($total_skor / $max_skor) * 100 : 0;

$stmt = $conn->prepare("UPDATE sesi_ujian SET nilai = ? WHERE id = ?");
$stmt->bind_param("di", $nilai, $sesi_id);
$stmt->execute();
$stmt->close();

// Log activity
$stmt = $conn->prepare("INSERT INTO log_aktivitas (sesi_ujian_id, aktivitas, keterangan) VALUES (?, 'EXAM_SUBMITTED', 'Ujian dikumpulkan')");
$stmt->bind_param("i", $sesi_id);
$stmt->execute();
$stmt->close();

redirect(base_url('siswa/ujian/hasil.php?sesi_id=' . $sesi_id));
