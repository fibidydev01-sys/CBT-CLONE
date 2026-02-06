<?php
/**
 * Bank Soal - List
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Bank Soal';
$active_menu = 'bank_soal';

$search = trim($_GET['search'] ?? '');

$where_sql = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_sql = "WHERE (bs.kode_soal LIKE ? OR bs.nama_bank LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types = 'ss';
}

// Count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bank_soal bs $where_sql");
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$pagination = paginate($total, 20);

// Get data dengan jumlah soal dan jumlah ujian
$query = "SELECT bs.*,
    (SELECT COUNT(*) FROM soal s WHERE s.bank_soal_id = bs.id) as jumlah_soal,
    (SELECT COUNT(*) FROM ujian u WHERE u.bank_soal_id = bs.id) as jumlah_ujian
    FROM bank_soal bs $where_sql
    ORDER BY bs.created_at DESC
    LIMIT ? OFFSET ?";

$params[] = $pagination['limit'];
$params[] = $pagination['offset'];
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bank_list = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-database me-2"></i>Bank Soal</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Bank Soal</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('admin/bank_soal/buat.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Buat Bank Soal
            </a>
        </div>

        <?php render_flash(); ?>

        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Cari</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Kode atau nama bank soal..." value="<?= e($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Cari</button>
                        <a href="<?= base_url('admin/bank_soal/index.php') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times me-1"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Daftar Bank Soal (<?= $total ?>)</div>
            <div class="card-body p-0">
                <?php if ($bank_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th width="130">Kode</th>
                                <th>Nama Bank Soal</th>
                                <th width="90">Soal</th>
                                <th width="80">Ujian</th>
                                <th width="100">Pembuat</th>
                                <th width="150">Dibuat</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $pagination['offset'] + 1; while ($b = $bank_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code class="fw-semibold"><?= e($b['kode_soal']) ?></code></td>
                                <td class="fw-semibold"><?= e($b['nama_bank']) ?></td>
                                <td><span class="badge bg-primary"><?= $b['jumlah_soal'] ?> soal</span></td>
                                <td><span class="badge bg-success"><?= $b['jumlah_ujian'] ?></span></td>
                                <td><span class="badge bg-secondary"><?= e(ucfirst($b['created_by_role'] ?? '-')) ?></span></td>
                                <td class="small text-muted"><?= format_datetime($b['created_at']) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/soal/index.php?bank_id=' . $b['id']) ?>" class="btn btn-sm btn-primary" title="Kelola Soal">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <a href="<?= base_url('admin/bank_soal/edit.php?id=' . $b['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($b['jumlah_ujian'] == 0): ?>
                                    <a href="<?= base_url('admin/bank_soal/hapus.php?id=' . $b['id']) ?>" class="btn btn-sm btn-danger" title="Hapus" data-confirm="Yakin hapus bank soal '<?= e($b['kode_soal']) ?>'? Semua soal di dalamnya akan ikut terhapus!">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa dihapus, sudah dipakai ujian">
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
                    <?php render_pagination($pagination, base_url('admin/bank_soal/index.php?search=' . urlencode($search))); ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-database"></i>
                    <p>Belum ada bank soal<?= $search ? ' yang sesuai pencarian' : '' ?>. <a href="<?= base_url('admin/bank_soal/buat.php') ?>">Buat bank soal</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
