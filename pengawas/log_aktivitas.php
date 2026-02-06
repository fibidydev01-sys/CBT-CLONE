<?php
/**
 * Pengawas - Log Aktivitas
 * CBT Nusantara
 *
 * View anti-cheat activity logs
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/functions.php';

check_login();
check_role(['pengawas']);

$page_title = 'Log Aktivitas';
$active_menu = 'log';

$sesi_id = (int)($_GET['sesi_id'] ?? 0);
$filter_aktivitas = $_GET['aktivitas'] ?? '';

// If viewing specific session
$sesi_info = null;
if ($sesi_id > 0) {
    $stmt = $conn->prepare("SELECT su.*, s.nama as nama_siswa, s.username, k.nama_kelas, u.nama_ujian
        FROM sesi_ujian su
        JOIN siswa s ON su.siswa_id = s.id
        JOIN kelas k ON s.kelas_id = k.id
        JOIN ujian u ON su.ujian_id = u.id
        WHERE su.id = ?");
    $stmt->bind_param("i", $sesi_id);
    $stmt->execute();
    $sesi_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Build query
$where = [];
$params = [];
$types = '';

if ($sesi_id > 0) {
    $where[] = "la.sesi_ujian_id = ?";
    $params[] = $sesi_id;
    $types .= 'i';
}

if (!empty($filter_aktivitas)) {
    $where[] = "la.aktivitas = ?";
    $params[] = $filter_aktivitas;
    $types .= 's';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$count_sql = "SELECT COUNT(*) as total FROM log_aktivitas la $where_sql";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = paginate($total, 30);

// Get logs
$query = "SELECT la.*, su.id as sesi_id, s.nama as nama_siswa, s.username, k.nama_kelas, u.nama_ujian
    FROM log_aktivitas la
    JOIN sesi_ujian su ON la.sesi_ujian_id = su.id
    JOIN siswa s ON su.siswa_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    JOIN ujian u ON su.ujian_id = u.id
    $where_sql
    ORDER BY la.created_at DESC
    LIMIT ? OFFSET ?";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

$school_name = get_setting('school_name', $conn, 'CBT Nusantara');

include __DIR__ . '/../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-history me-2"></i>Log Aktivitas</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('pengawas/index.php') ?>">Dashboard</a></li>
                        <?php if ($sesi_info): ?>
                        <li class="breadcrumb-item"><a href="<?= base_url('pengawas/monitoring.php?id=' . $sesi_info['ujian_id']) ?>">Monitoring</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active">Log Aktivitas</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php render_flash(); ?>

        <?php if ($sesi_info): ?>
        <!-- Sesi Info Card -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Siswa</small>
                        <strong><?= e($sesi_info['nama_siswa']) ?></strong>
                        <small class="text-muted d-block"><?= e($sesi_info['username']) ?></small>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Kelas</small>
                        <span class="badge bg-info"><?= e($sesi_info['nama_kelas']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Ujian</small>
                        <?= e($sesi_info['nama_ujian']) ?>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status</small>
                        <?php
                        $badge = match($sesi_info['status']) {
                            'belum_mulai' => 'bg-secondary',
                            'sedang_ujian' => 'bg-warning text-dark',
                            'selesai' => 'bg-success',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?= $badge ?>"><?= e(ucfirst(str_replace('_', ' ', $sesi_info['status']))) ?></span>
                        <?php if ($sesi_info['exit_count'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $sesi_info['exit_count'] ?> exit</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <?php if ($sesi_id): ?>
                    <input type="hidden" name="sesi_id" value="<?= $sesi_id ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="form-label small">Tipe Aktivitas</label>
                        <select name="aktivitas" class="form-select form-select-sm">
                            <option value="">-- Semua --</option>
                            <option value="TAB_SWITCH" <?= $filter_aktivitas === 'TAB_SWITCH' ? 'selected' : '' ?>>Tab Switch</option>
                            <option value="EXIT_PENALTY" <?= $filter_aktivitas === 'EXIT_PENALTY' ? 'selected' : '' ?>>Exit Penalty</option>
                            <option value="COPY_ATTEMPT" <?= $filter_aktivitas === 'COPY_ATTEMPT' ? 'selected' : '' ?>>Copy Attempt</option>
                            <option value="RIGHT_CLICK" <?= $filter_aktivitas === 'RIGHT_CLICK' ? 'selected' : '' ?>>Right Click</option>
                            <option value="DEVTOOLS" <?= $filter_aktivitas === 'DEVTOOLS' ? 'selected' : '' ?>>DevTools</option>
                            <option value="EXAM_STARTED" <?= $filter_aktivitas === 'EXAM_STARTED' ? 'selected' : '' ?>>Exam Started</option>
                            <option value="EXAM_SUBMITTED" <?= $filter_aktivitas === 'EXAM_SUBMITTED' ? 'selected' : '' ?>>Exam Submitted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                        <a href="<?= base_url('pengawas/log_aktivitas.php' . ($sesi_id ? '?sesi_id=' . $sesi_id : '')) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">Log Aktivitas (<?= $total ?>)</div>
            <div class="card-body p-0">
                <?php if ($logs->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <?php if (!$sesi_info): ?>
                                <th>Siswa</th>
                                <th>Ujian</th>
                                <?php endif; ?>
                                <th width="140">Aktivitas</th>
                                <th>Keterangan</th>
                                <th width="160">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $pagination['offset'] + 1; while ($l = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <?php if (!$sesi_info): ?>
                                <td>
                                    <div class="fw-semibold"><?= e($l['nama_siswa']) ?></div>
                                    <small class="text-muted"><?= e($l['nama_kelas']) ?></small>
                                </td>
                                <td class="small"><?= e($l['nama_ujian']) ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php
                                    $act_badge = match($l['aktivitas']) {
                                        'TAB_SWITCH','EXIT_PENALTY' => 'bg-danger',
                                        'COPY_ATTEMPT' => 'bg-warning text-dark',
                                        'RIGHT_CLICK' => 'bg-secondary',
                                        'DEVTOOLS' => 'bg-dark',
                                        'EXAM_STARTED' => 'bg-info',
                                        'EXAM_SUBMITTED' => 'bg-success',
                                        default => 'bg-info'
                                    };
                                    ?>
                                    <span class="badge <?= $act_badge ?>"><?= e($l['aktivitas']) ?></span>
                                </td>
                                <td class="small"><?= e($l['keterangan']) ?></td>
                                <td class="small"><?= format_datetime($l['created_at']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    <?php
                    $pg_url = 'pengawas/log_aktivitas.php?';
                    if ($sesi_id) $pg_url .= 'sesi_id=' . $sesi_id . '&';
                    if ($filter_aktivitas) $pg_url .= 'aktivitas=' . urlencode($filter_aktivitas) . '&';
                    render_pagination($pagination, base_url($pg_url));
                    ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>Tidak ada log aktivitas<?= $filter_aktivitas ? ' untuk filter ini' : '' ?>.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../includes/footer.php'; ?>
