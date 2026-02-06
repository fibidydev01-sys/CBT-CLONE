<?php
/**
 * Siswa - List
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Manajemen Siswa';
$active_menu = 'siswa';

// Filter kelas
$filter_kelas = (int)($_GET['kelas'] ?? 0);
$search = trim($_GET['search'] ?? '');

// Build query
$where = [];
$params = [];
$types = '';

if ($filter_kelas > 0) {
    $where[] = "s.kelas_id = ?";
    $params[] = $filter_kelas;
    $types .= 'i';
}
if (!empty($search)) {
    $where[] = "(s.nama LIKE ? OR s.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$count_query = "SELECT COUNT(*) as total FROM siswa s $where_sql";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Pagination
$pagination = paginate($total, 20);

// Get data
$query = "SELECT s.*, k.nama_kelas FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    $where_sql
    ORDER BY k.nama_kelas ASC, s.nama ASC
    LIMIT ? OFFSET ?";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$siswa_list = $stmt->get_result();
$stmt->close();

// Get daftar kelas untuk filter
$kelas_list = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas ASC");

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-user-graduate me-2"></i>Manajemen Siswa</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Siswa</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('admin/siswa/tambah.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tambah Siswa
            </a>
        </div>

        <?php render_flash(); ?>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Filter Kelas</label>
                        <select name="kelas" class="form-select form-select-sm">
                            <option value="0">-- Semua Kelas --</option>
                            <?php while ($k = $kelas_list->fetch_assoc()): ?>
                            <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Cari</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama atau username..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                        <a href="<?= base_url('admin/siswa/index.php') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                Daftar Siswa (<?= $total ?> siswa)
            </div>
            <div class="card-body p-0">
                <?php if ($siswa_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Kelas</th>
                                <th width="80">Status</th>
                                <th width="170">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $pagination['offset'] + 1; while ($s = $siswa_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= e($s['nama']) ?></td>
                                <td><code><?= e($s['username']) ?></code></td>
                                <td><span class="badge bg-info"><?= e($s['nama_kelas']) ?></span></td>
                                <td>
                                    <?php if ($s['is_blocked']): ?>
                                    <span class="badge bg-danger">Blocked</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/siswa/edit.php?id=' . $s['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= base_url('admin/siswa/reset_password.php?id=' . $s['id']) ?>" class="btn btn-sm btn-info" title="Reset Password" data-confirm="Reset password siswa <?= e($s['nama']) ?>?">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <a href="<?= base_url('admin/siswa/hapus.php?id=' . $s['id']) ?>" class="btn btn-sm btn-danger" title="Hapus" data-confirm="Yakin hapus siswa <?= e($s['nama']) ?>? Semua data ujian siswa ini akan ikut terhapus!">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    <?php
                    $base = base_url('admin/siswa/index.php?kelas=' . $filter_kelas . '&search=' . urlencode($search));
                    render_pagination($pagination, $base);
                    ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <p>Belum ada siswa<?= $filter_kelas || $search ? ' yang sesuai filter' : '' ?>. <a href="<?= base_url('admin/siswa/tambah.php') ?>">Tambah siswa</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
