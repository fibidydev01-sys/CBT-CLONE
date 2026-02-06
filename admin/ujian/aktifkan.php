<?php
/**
 * Ujian - Aktifkan / Selesaikan
 * Admin Panel
 *
 * ?action=selesai → akhiri ujian
 * default → aktifkan ujian (draft → aktif)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'aktifkan';

if ($id <= 0) {
    redirect(base_url('admin/ujian/index.php'), 'danger', 'ID ujian tidak valid.');
}

// Get ujian
$stmt = $conn->prepare("SELECT * FROM ujian WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ujian) {
    redirect(base_url('admin/ujian/index.php'), 'danger', 'Ujian tidak ditemukan.');
}

if ($action === 'selesai') {
    // Akhiri ujian aktif → selesai
    if ($ujian['status'] !== 'aktif') {
        redirect(base_url('admin/ujian/index.php'), 'warning', 'Hanya ujian aktif yang bisa diakhiri.');
    }

    $conn->begin_transaction();
    try {
        // Update status ujian
        $stmt = $conn->prepare("UPDATE ujian SET status = 'selesai' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Selesaikan semua sesi yang masih berjalan
        $stmt = $conn->prepare("UPDATE sesi_ujian SET status = 'selesai', waktu_selesai = NOW() WHERE ujian_id = ? AND status = 'sedang_ujian'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        // Hitung nilai untuk sesi yang baru diselesaikan
        $sesi_list = $conn->query("SELECT su.id as sesi_id FROM sesi_ujian su WHERE su.ujian_id = $id AND su.nilai IS NULL");
        while ($sesi = $sesi_list->fetch_assoc()) {
            hitung_nilai($sesi['sesi_id'], $id, $conn);
        }

        $conn->commit();
        redirect(base_url('admin/ujian/index.php'), 'success',
            'Ujian berhasil diakhiri. ' . $affected . ' sesi aktif dihentikan.');
    } catch (Exception $e) {
        $conn->rollback();
        redirect(base_url('admin/ujian/index.php'), 'danger', 'Gagal mengakhiri ujian.');
    }

} else {
    // Aktifkan ujian draft → aktif
    if ($ujian['status'] !== 'draft') {
        redirect(base_url('admin/ujian/index.php'), 'warning', 'Hanya ujian draft yang bisa diaktifkan.');
    }

    // Cek kunci jawaban lengkap
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM kunci_jawaban WHERE ujian_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $kunci_count = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($kunci_count < $ujian['jumlah_soal']) {
        redirect(base_url('admin/ujian/edit.php?id=' . $id . '&tab=kunci'), 'danger',
            'Kunci jawaban belum lengkap (' . $kunci_count . '/' . $ujian['jumlah_soal'] . '). Lengkapi dulu.');
    }

    // Aktifkan + buat sesi untuk semua siswa di kelas terpilih
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE ujian SET status = 'aktif' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Get siswa dari kelas terpilih
        $siswa_ids = [];
        $result = $conn->query("SELECT s.id FROM siswa s
            JOIN ujian_kelas uk ON s.kelas_id = uk.kelas_id
            WHERE uk.ujian_id = $id AND s.is_blocked = 0");
        while ($r = $result->fetch_assoc()) $siswa_ids[] = $r['id'];

        // Buat sesi_ujian untuk tiap siswa
        $waktu_detik = $ujian['alokasi_waktu'] * 60;
        $stmt = $conn->prepare("INSERT IGNORE INTO sesi_ujian (ujian_id, siswa_id, sisa_waktu, status) VALUES (?, ?, ?, 'belum_mulai')");
        foreach ($siswa_ids as $sid) {
            $stmt->bind_param("iii", $id, $sid, $waktu_detik);
            $stmt->execute();
        }
        $stmt->close();

        $conn->commit();
        redirect(base_url('admin/ujian/index.php'), 'success',
            'Ujian berhasil diaktifkan! ' . count($siswa_ids) . ' siswa siap mengerjakan.');
    } catch (Exception $e) {
        $conn->rollback();
        redirect(base_url('admin/ujian/index.php'), 'danger', 'Gagal mengaktifkan ujian.');
    }
}

/**
 * Hitung nilai siswa berdasarkan kunci jawaban
 */
function hitung_nilai($sesi_id, $ujian_id, $conn) {
    // Get kunci jawaban
    $kunci = [];
    $result = $conn->query("SELECT * FROM kunci_jawaban WHERE ujian_id = $ujian_id");
    while ($r = $result->fetch_assoc()) {
        $kunci[$r['nomor_soal']] = $r;
    }

    // Get jawaban siswa
    $result = $conn->query("SELECT * FROM jawaban_siswa WHERE sesi_ujian_id = $sesi_id");
    $total_skor = 0;
    $max_skor = 0;

    foreach ($kunci as $nomor => $k) {
        $max_skor += (float)$k['skor'];
    }

    while ($j = $result->fetch_assoc()) {
        $nomor = $j['nomor_soal'];
        if (isset($kunci[$nomor])) {
            $is_correct = (strtoupper(trim($j['jawaban'])) === strtoupper(trim($kunci[$nomor]['jawaban_benar']))) ? 1 : 0;
            $skor = $is_correct ? (float)$kunci[$nomor]['skor'] : 0;
            $total_skor += $skor;

            // Update jawaban
            $stmt = $conn->prepare("UPDATE jawaban_siswa SET is_correct=?, skor_diperoleh=? WHERE id=?");
            $stmt->bind_param("idi", $is_correct, $skor, $j['id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Hitung nilai skala 100
    $nilai = $max_skor > 0 ? ($total_skor / $max_skor) * 100 : 0;

    $stmt = $conn->prepare("UPDATE sesi_ujian SET nilai = ? WHERE id = ?");
    $stmt->bind_param("di", $nilai, $sesi_id);
    $stmt->execute();
    $stmt->close();
}
