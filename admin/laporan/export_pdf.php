<?php
/**
 * Laporan - Export PDF (Print-friendly HTML)
 * Admin Panel
 *
 * Halaman cetak yang bisa di-print ke PDF via browser
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT u.*, bs.kode_soal, bs.nama_bank FROM ujian u
    JOIN bank_soal bs ON u.bank_soal_id = bs.id WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ujian) {
    redirect(base_url('admin/laporan/index.php'), 'danger', 'Ujian tidak ditemukan.');
}

$stats = $conn->query("SELECT
    COUNT(*) as total,
    COUNT(CASE WHEN su.nilai IS NOT NULL THEN 1 END) as selesai,
    ROUND(AVG(CASE WHEN su.nilai IS NOT NULL THEN su.nilai END), 1) as rata_rata,
    MAX(CASE WHEN su.nilai IS NOT NULL THEN su.nilai END) as nilai_max,
    MIN(CASE WHEN su.nilai IS NOT NULL THEN su.nilai END) as nilai_min,
    COUNT(CASE WHEN su.nilai >= 70 THEN 1 END) as lulus,
    COUNT(CASE WHEN su.nilai < 70 AND su.nilai IS NOT NULL THEN 1 END) as tidak_lulus
    FROM sesi_ujian su WHERE su.ujian_id = $id")->fetch_assoc();

$data = $conn->query("SELECT su.*, s.nama as nama_siswa, s.username, k.nama_kelas,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND js.is_correct = 1) as benar,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND js.is_correct = 0 AND js.jawaban != '') as salah
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE su.ujian_id = $id
    ORDER BY k.nama_kelas ASC, s.nama ASC");

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - <?= e($ujian['nama_ujian']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #333; padding-bottom: 15px; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header h2 { font-size: 14px; font-weight: normal; margin-bottom: 3px; }
        .header p { font-size: 10px; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px 8px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 120px; color: #555; }
        .stats-row { display: flex; gap: 10px; margin-bottom: 15px; }
        .stat-box { flex: 1; text-align: center; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .stat-box .num { font-size: 18px; font-weight: bold; }
        .stat-box .lbl { font-size: 9px; color: #666; text-transform: uppercase; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data th, table.data td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        table.data th { background: #f0f0f0; font-size: 10px; text-transform: uppercase; }
        table.data tr:nth-child(even) { background: #fafafa; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .no-print { margin-bottom: 15px; text-align: center; }
        .no-print button { padding: 8px 24px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .no-print button:hover { background: #0b5ed7; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            @page { margin: 15mm; size: A4 landscape; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()"><b>&#128424;</b> Cetak / Simpan PDF</button>
    </div>

    <div class="header">
        <h1><?= e($school_name) ?></h1>
        <h2>Laporan Hasil Ujian: <?= e($ujian['nama_ujian']) ?></h2>
        <p>Dicetak pada <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table class="info-table">
        <tr><td class="label">Ujian</td><td>: <?= e($ujian['nama_ujian']) ?></td><td class="label">Tanggal</td><td>: <?= $ujian['tanggal_ujian'] ?></td></tr>
        <tr><td class="label">Bank Soal</td><td>: <?= e($ujian['kode_soal']) ?> - <?= e($ujian['nama_bank']) ?></td><td class="label">Waktu</td><td>: <?= $ujian['jam_mulai'] ?> - <?= $ujian['jam_selesai'] ?></td></tr>
        <tr><td class="label">Jumlah Soal</td><td>: <?= $ujian['jumlah_soal'] ?> soal</td><td class="label">Durasi</td><td>: <?= $ujian['alokasi_waktu'] ?> menit</td></tr>
    </table>

    <div class="stats-row">
        <div class="stat-box"><div class="num"><?= $stats['selesai'] ?>/<?= $stats['total'] ?></div><div class="lbl">Peserta</div></div>
        <div class="stat-box"><div class="num"><?= $stats['rata_rata'] ?? '-' ?></div><div class="lbl">Rata-rata</div></div>
        <div class="stat-box"><div class="num"><?= $stats['nilai_min'] !== null ? format_nilai($stats['nilai_min']) : '-' ?></div><div class="lbl">Terendah</div></div>
        <div class="stat-box"><div class="num"><?= $stats['nilai_max'] !== null ? format_nilai($stats['nilai_max']) : '-' ?></div><div class="lbl">Tertinggi</div></div>
        <div class="stat-box"><div class="num text-success"><?= $stats['lulus'] ?></div><div class="lbl">Lulus</div></div>
        <div class="stat-box"><div class="num text-danger"><?= $stats['tidak_lulus'] ?></div><div class="lbl">Tidak Lulus</div></div>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center" width="30">No</th>
                <th>Nama Siswa</th>
                <th width="80">Username</th>
                <th width="80">Kelas</th>
                <th class="text-center" width="60">Nilai</th>
                <th class="text-center" width="50">Benar</th>
                <th class="text-center" width="50">Salah</th>
                <th class="text-center" width="50">Exit</th>
                <th class="text-center" width="80">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="fw-bold"><?= e($row['nama_siswa']) ?></td>
                <td><?= e($row['username']) ?></td>
                <td><?= e($row['nama_kelas']) ?></td>
                <td class="text-center fw-bold <?= $row['nilai'] !== null ? ($row['nilai'] >= 70 ? 'text-success' : 'text-danger') : '' ?>">
                    <?= $row['nilai'] !== null ? format_nilai($row['nilai']) : '-' ?>
                </td>
                <td class="text-center"><?= $row['benar'] ?></td>
                <td class="text-center"><?= $row['salah'] ?></td>
                <td class="text-center"><?= $row['exit_count'] ?></td>
                <td class="text-center fw-bold <?= $row['nilai'] !== null ? ($row['nilai'] >= 70 ? 'text-success' : 'text-danger') : '' ?>">
                    <?php if ($row['nilai'] !== null): ?>
                    <?= $row['nilai'] >= 70 ? 'LULUS' : 'TIDAK LULUS' ?>
                    <?php else: ?>
                    <?= strtoupper(str_replace('_', ' ', $row['status'])) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        <?= e($school_name) ?> &middot; CBT Nusantara &middot; <?= date('Y') ?>
    </div>
</body>
</html>
