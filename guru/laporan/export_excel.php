<?php
/**
 * Laporan - Export Excel (CSV)
 * Guru Panel (own ujian only)
 *
 * Export hasil ujian ke CSV (compatible Excel)
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['guru']);

$guru_id = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// Verify ownership
$stmt = $conn->prepare("SELECT u.*, bs.kode_soal, bs.nama_bank FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id
    WHERE u.id = ? AND u.created_by_role = 'guru' AND u.created_by_id = ?");
$stmt->bind_param("ii", $id, $guru_id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ujian) {
    redirect(base_url('guru/laporan/index.php'), 'danger', 'Ujian tidak ditemukan.');
}

// Get data
$data = $conn->query("SELECT su.*, s.nama as nama_siswa, s.username, k.nama_kelas,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND js.is_correct = 1) as benar,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND js.is_correct = 0 AND js.jawaban != '') as salah,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND (js.jawaban = '' OR js.jawaban IS NULL)) as kosong
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE su.ujian_id = $id
    ORDER BY k.nama_kelas ASC, s.nama ASC");

// Generate filename
$filename = 'Hasil_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $ujian['nama_ujian']) . '_' . date('Y-m-d') . '.csv';

// Output CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header info
fputcsv($output, ['Laporan Hasil Ujian']);
fputcsv($output, ['Ujian', $ujian['nama_ujian']]);
fputcsv($output, ['Bank Soal', $ujian['kode_soal'] . ' - ' . $ujian['nama_bank']]);
fputcsv($output, ['Tanggal', $ujian['tanggal_ujian']]);
fputcsv($output, ['Waktu', $ujian['jam_mulai'] . ' - ' . $ujian['jam_selesai']]);
fputcsv($output, ['Jumlah Soal', $ujian['jumlah_soal']]);
fputcsv($output, ['Alokasi Waktu', $ujian['alokasi_waktu'] . ' menit']);
fputcsv($output, []);

// Table header
fputcsv($output, ['No', 'Nama Siswa', 'Username', 'Kelas', 'Nilai', 'Benar', 'Salah', 'Kosong', 'Exit Count', 'Penalty (s)', 'Status', 'Waktu Mulai', 'Waktu Selesai']);

// Data rows
$no = 1;
while ($row = $data->fetch_assoc()) {
    $status = $row['nilai'] !== null ? ($row['nilai'] >= 70 ? 'LULUS' : 'TIDAK LULUS') : strtoupper(str_replace('_', ' ', $row['status']));

    fputcsv($output, [
        $no++,
        $row['nama_siswa'],
        $row['username'],
        $row['nama_kelas'],
        $row['nilai'] !== null ? number_format($row['nilai'], 1) : '-',
        $row['benar'],
        $row['salah'],
        $row['kosong'],
        $row['exit_count'],
        $row['penalty_time'],
        $status,
        $row['waktu_mulai'] ?? '-',
        $row['waktu_selesai'] ?? '-'
    ]);
}

fclose($output);
exit;
