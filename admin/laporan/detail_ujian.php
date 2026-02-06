<?php
/**
 * Laporan - Detail Ujian
 * Admin Panel
 *
 * Detail nilai per siswa + statistik + chart
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$active_menu = 'laporan';

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

$page_title = 'Laporan - ' . $ujian['nama_ujian'];

// Stats
$stats = $conn->query("SELECT
    COUNT(*) as total,
    COUNT(CASE WHEN su.nilai IS NOT NULL THEN 1 END) as selesai,
    ROUND(AVG(CASE WHEN su.nilai IS NOT NULL THEN su.nilai END), 1) as rata_rata,
    MAX(CASE WHEN su.nilai IS NOT NULL THEN su.nilai END) as nilai_max,
    MIN(CASE WHEN su.nilai IS NOT NULL THEN su.nilai END) as nilai_min,
    COUNT(CASE WHEN su.nilai >= 70 THEN 1 END) as lulus,
    COUNT(CASE WHEN su.nilai < 70 AND su.nilai IS NOT NULL THEN 1 END) as tidak_lulus,
    ROUND(AVG(su.exit_count), 1) as avg_exit,
    SUM(su.exit_count) as total_exit
    FROM sesi_ujian su WHERE su.ujian_id = $id")->fetch_assoc();

// Kelas stats
$kelas_stats = $conn->query("SELECT k.nama_kelas,
    COUNT(su.id) as total,
    ROUND(AVG(su.nilai), 1) as rata_rata,
    COUNT(CASE WHEN su.nilai >= 70 THEN 1 END) as lulus
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE su.ujian_id = $id AND su.nilai IS NOT NULL
    GROUP BY k.id
    ORDER BY k.nama_kelas");

// Per-siswa results
$filter_kelas = (int)($_GET['kelas_id'] ?? 0);
$sort = $_GET['sort'] ?? 'nama';

$siswa_where = "WHERE su.ujian_id = $id";
if ($filter_kelas > 0) {
    $siswa_where .= " AND s.kelas_id = $filter_kelas";
}

$order = match($sort) {
    'nilai_asc' => 'su.nilai ASC',
    'nilai_desc' => 'su.nilai DESC',
    'kelas' => 'k.nama_kelas ASC, s.nama ASC',
    default => 's.nama ASC'
};

$siswa_list = $conn->query("SELECT su.*, s.nama as nama_siswa, s.username, k.nama_kelas,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND js.is_correct = 1) as benar,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND js.is_correct = 0 AND js.jawaban != '') as salah,
    (SELECT COUNT(*) FROM jawaban_siswa js WHERE js.sesi_ujian_id = su.id AND (js.jawaban = '' OR js.jawaban IS NULL)) as kosong
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    $siswa_where
    ORDER BY $order");

// Kelas list for filter
$kelas_list = $conn->query("SELECT DISTINCT k.id, k.nama_kelas
    FROM sesi_ujian su
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE su.ujian_id = $id
    ORDER BY k.nama_kelas");

// Score distribution for chart
$dist = $conn->query("SELECT
    COUNT(CASE WHEN nilai >= 90 THEN 1 END) as a,
    COUNT(CASE WHEN nilai >= 80 AND nilai < 90 THEN 1 END) as b,
    COUNT(CASE WHEN nilai >= 70 AND nilai < 80 THEN 1 END) as c,
    COUNT(CASE WHEN nilai >= 60 AND nilai < 70 THEN 1 END) as d,
    COUNT(CASE WHEN nilai < 60 AND nilai IS NOT NULL THEN 1 END) as e
    FROM sesi_ujian WHERE ujian_id = $id AND nilai IS NOT NULL")->fetch_assoc();

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-chart-bar me-2"></i>Detail Laporan</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/laporan/index.php') ?>">Laporan</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                <a href="<?= base_url('admin/laporan/export_excel.php?id=' . $id) ?>" class="btn btn-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
                <a href="<?= base_url('admin/laporan/export_pdf.php?id=' . $id) ?>" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf me-1"></i>PDF</a>
            </div>
        </div>

        <!-- Ujian Info -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col"><small class="text-muted d-block">Ujian</small><strong><?= e($ujian['nama_ujian']) ?></strong></div>
                    <div class="col"><small class="text-muted d-block">Bank Soal</small><code><?= e($ujian['kode_soal']) ?></code></div>
                    <div class="col"><small class="text-muted d-block">Tanggal</small><?= format_tanggal($ujian['tanggal_ujian']) ?></div>
                    <div class="col"><small class="text-muted d-block">Soal</small><?= $ujian['jumlah_soal'] ?> soal / <?= $ujian['alokasi_waktu'] ?> menit</div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md">
                <div class="stat-card bg-primary-gradient"><div class="stat-number"><?= $stats['selesai'] ?? 0 ?>/<?= $stats['total'] ?? 0 ?></div><div class="stat-label">Peserta</div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-warning-gradient"><div class="stat-number"><?= $stats['rata_rata'] ?? '-' ?></div><div class="stat-label">Rata-rata</div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-info-gradient"><div class="stat-number"><?= $stats['nilai_min'] !== null ? format_nilai($stats['nilai_min']) : '-' ?> / <?= $stats['nilai_max'] !== null ? format_nilai($stats['nilai_max']) : '-' ?></div><div class="stat-label">Min / Max</div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-success-gradient"><div class="stat-number"><?= $stats['lulus'] ?? 0 ?></div><div class="stat-label">Lulus (>=70)</div></div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card bg-danger-gradient"><div class="stat-number"><?= $stats['tidak_lulus'] ?? 0 ?></div><div class="stat-label">Tidak Lulus</div></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <!-- Chart -->
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header">Distribusi Nilai</div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="scoreChart" width="300" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Per-kelas stats -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header">Statistik per Kelas</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Kelas</th><th width="70">Peserta</th><th width="80">Rata-rata</th><th width="70">Lulus</th><th width="100">% Lulus</th></tr></thead>
                                <tbody>
                                    <?php while ($ks = $kelas_stats->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($ks['nama_kelas']) ?></td>
                                        <td><?= $ks['total'] ?></td>
                                        <td><span class="fw-bold <?= $ks['rata_rata'] >= 70 ? 'text-success' : 'text-danger' ?>"><?= $ks['rata_rata'] ?></span></td>
                                        <td><?= $ks['lulus'] ?>/<?= $ks['total'] ?></td>
                                        <td>
                                            <?php $pct = $ks['total'] > 0 ? round(($ks['lulus'] / $ks['total']) * 100) : 0; ?>
                                            <div class="progress" style="height:18px;">
                                                <div class="progress-bar <?= $pct >= 70 ? 'bg-success' : 'bg-danger' ?>" style="width:<?= $pct ?>%"><?= $pct ?>%</div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Per-siswa results -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Hasil per Siswa</span>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <select name="kelas_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        <?php $kelas_list->data_seek(0); while ($k = $kelas_list->fetch_assoc()): ?>
                        <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <select name="sort" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="nama" <?= $sort === 'nama' ? 'selected' : '' ?>>Nama A-Z</option>
                        <option value="nilai_desc" <?= $sort === 'nilai_desc' ? 'selected' : '' ?>>Nilai Tertinggi</option>
                        <option value="nilai_asc" <?= $sort === 'nilai_asc' ? 'selected' : '' ?>>Nilai Terendah</option>
                        <option value="kelas" <?= $sort === 'kelas' ? 'selected' : '' ?>>Per Kelas</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th width="80">Nilai</th>
                                <th width="80">Benar</th>
                                <th width="80">Salah</th>
                                <th width="80">Kosong</th>
                                <th width="60">Exit</th>
                                <th width="90">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($s = $siswa_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($s['nama_siswa']) ?></div>
                                    <small class="text-muted"><?= e($s['username']) ?></small>
                                </td>
                                <td><span class="badge bg-info"><?= e($s['nama_kelas']) ?></span></td>
                                <td>
                                    <?php if ($s['nilai'] !== null): ?>
                                    <span class="fw-bold fs-6 <?= $s['nilai'] >= 70 ? 'text-success' : 'text-danger' ?>"><?= format_nilai($s['nilai']) ?></span>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                                <td><span class="text-success"><?= $s['benar'] ?></span></td>
                                <td><span class="text-danger"><?= $s['salah'] ?></span></td>
                                <td><span class="text-muted"><?= $s['kosong'] ?></span></td>
                                <td>
                                    <?php if ($s['exit_count'] > 0): ?>
                                    <span class="badge bg-danger"><?= $s['exit_count'] ?></span>
                                    <?php else: ?><span class="text-muted">0</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['nilai'] !== null): ?>
                                    <span class="badge <?= $s['nilai'] >= 70 ? 'bg-success' : 'bg-danger' ?>"><?= $s['nilai'] >= 70 ? 'LULUS' : 'TIDAK LULUS' ?></span>
                                    <?php else: ?>
                                    <?php
                                    $badge = match($s['status']) { 'belum_mulai' => 'bg-secondary', 'sedang_ujian' => 'bg-warning text-dark', default => 'bg-secondary' };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= e(ucfirst(str_replace('_', ' ', $s['status']))) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php
$extra_js = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'];
$inline_js = "
new Chart(document.getElementById('scoreChart'), {
    type: 'doughnut',
    data: {
        labels: ['90-100 (A)', '80-89 (B)', '70-79 (C)', '60-69 (D)', '<60 (E)'],
        datasets: [{
            data: [{$dist['a']}, {$dist['b']}, {$dist['c']}, {$dist['d']}, {$dist['e']}],
            backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#fd7e14', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});
";
include __DIR__ . '/../../includes/footer.php';
?>
