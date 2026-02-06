<?php
/**
 * Kelas - List
 * Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
check_role(['admin']);

$page_title = 'Manajemen Kelas';
$active_menu = 'kelas';

// Get semua kelas dengan jumlah siswa
$query = "SELECT k.*,
    (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) as jumlah_siswa
    FROM kelas k ORDER BY k.nama_kelas ASC";
$kelas_list = $conn->query($query);

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-with-sidebar">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-area">
        <div class="page-header">
            <div>
                <h4><i class="fas fa-school me-2"></i>Manajemen Kelas</h4>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Kelas</li>
                    </ol>
                </nav>
            </div>
            <a href="<?= base_url('admin/kelas/tambah.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tambah Kelas
            </a>
        </div>

        <?php render_flash(); ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Kelas (<?= $kelas_list->num_rows ?> kelas)</span>
                <input type="text" id="tableSearch" class="form-control form-control-sm" style="width:200px" placeholder="Cari kelas...">
            </div>
            <div class="card-body p-0">
                <?php if ($kelas_list->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Nama Kelas</th>
                                <th width="120">Jumlah Siswa</th>
                                <th width="160">Dibuat</th>
                                <th width="130">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($k = $kelas_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="fw-semibold"><?= e($k['nama_kelas']) ?></span></td>
                                <td>
                                    <span class="badge bg-info"><?= $k['jumlah_siswa'] ?> siswa</span>
                                </td>
                                <td class="small text-muted"><?= format_datetime($k['created_at']) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/kelas/edit.php?id=' . $k['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($k['jumlah_siswa'] == 0): ?>
                                    <a href="<?= base_url('admin/kelas/hapus.php?id=' . $k['id']) ?>" class="btn btn-sm btn-danger" data-confirm="Yakin hapus kelas <?= e($k['nama_kelas']) ?>?" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa dihapus, masih ada siswa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-school"></i>
                    <p>Belum ada kelas. <a href="<?= base_url('admin/kelas/tambah.php') ?>">Tambah kelas pertama</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
