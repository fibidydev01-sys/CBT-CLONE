<?php
/**
 * Guru - List
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Manajemen Guru';
$active_menu = 'guru';

$search = trim($_GET['search'] ?? '');

// Build query
$where_sql = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_sql = "WHERE (g.nama LIKE ? OR g.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types = 'ss';
}

// Count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM guru g $where_sql");
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = paginate($total, 20);

// Get data + hitung bank soal & ujian yang dibuat
$query = "SELECT g.*,
    (SELECT COUNT(*) FROM bank_soal bs WHERE bs.created_by_role = 'guru' AND bs.created_by_id = g.id) as total_bank_soal,
    (SELECT COUNT(*) FROM ujian u WHERE u.created_by_role = 'guru' AND u.created_by_id = g.id) as total_ujian
    FROM guru g $where_sql ORDER BY g.nama ASC LIMIT ? OFFSET ?";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$guru_list = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-chalkboard-teacher me-2"></i>Manajemen Guru</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Guru</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('admin/guru/tambah.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tambah Guru
            </a>
        </div>

        <?php render_flash(); ?>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Cari</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama atau username..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Cari</button>
                        <a href="<?= base_url('admin/guru/index.php') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Daftar Guru (<?= $total ?> guru)</div>
            <div class="card-body p-0">
                <?php if ($guru_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th width="100">Bank Soal</th>
                                <th width="80">Ujian</th>
                                <th width="150">Terdaftar</th>
                                <th width="130">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $pagination['offset'] + 1; while ($g = $guru_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= e($g['nama']) ?></td>
                                <td><code><?= e($g['username']) ?></code></td>
                                <td><span class="badge bg-info"><?= $g['total_bank_soal'] ?></span></td>
                                <td><span class="badge bg-success"><?= $g['total_ujian'] ?></span></td>
                                <td class="small text-muted"><?= format_datetime($g['created_at']) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/guru/edit.php?id=' . $g['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($g['total_bank_soal'] == 0 && $g['total_ujian'] == 0): ?>
                                    <a href="<?= base_url('admin/guru/hapus.php?id=' . $g['id']) ?>" class="btn btn-sm btn-danger" title="Hapus" data-confirm="Yakin hapus guru <?= e($g['nama']) ?>?">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa dihapus, masih punya bank soal/ujian">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    <?php render_pagination($pagination, base_url('admin/guru/index.php?search=' . urlencode($search))); ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <p>Belum ada guru<?= $search ? ' yang sesuai pencarian' : '' ?>. <a href="<?= base_url('admin/guru/tambah.php') ?>">Tambah guru</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
