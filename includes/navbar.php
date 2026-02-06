<?php
/**
 * Navbar Template - Role-based
 * CBT Nusantara
 */
$current_role = $_SESSION['role'] ?? '';
$current_nama = $_SESSION['nama'] ?? '';
$current_username = $_SESSION['username'] ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?= base_url() ?>">
            <i class="fas fa-laptop-code me-2"></i>
            <span class="fw-bold"><?= e($school_name ?? 'CBT Nusantara') ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <?php if ($current_role): ?>
            <ul class="navbar-nav me-auto">
                <?php if ($current_role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/index.php') ?>"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-users me-1"></i> Users</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= base_url('admin/siswa/index.php') ?>"><i class="fas fa-user-graduate me-2"></i>Siswa</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('admin/guru/index.php') ?>"><i class="fas fa-chalkboard-teacher me-2"></i>Guru</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('admin/pengawas/index.php') ?>"><i class="fas fa-user-shield me-2"></i>Pengawas</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('admin/kelas/index.php') ?>"><i class="fas fa-school me-2"></i>Kelas</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-book me-1"></i> Soal & Ujian</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= base_url('admin/bank_soal/index.php') ?>"><i class="fas fa-database me-2"></i>Bank Soal</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('admin/ujian/index.php') ?>"><i class="fas fa-file-alt me-2"></i>Ujian</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= base_url('admin/token/generate.php') ?>"><i class="fas fa-key me-2"></i>Token</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-chart-bar me-1"></i> Laporan</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= base_url('admin/laporan/index.php') ?>"><i class="fas fa-chart-pie me-2"></i>Hasil Ujian</a></li>
                        <li><a class="dropdown-item" href="<?= base_url('admin/ujian/monitoring.php') ?>"><i class="fas fa-desktop me-2"></i>Monitoring</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/settings/index.php') ?>"><i class="fas fa-cog me-1"></i> Settings</a>
                </li>

                <?php elseif ($current_role === 'guru'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('guru/index.php') ?>"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('guru/bank_soal/index.php') ?>"><i class="fas fa-database me-1"></i> Bank Soal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('guru/ujian/index.php') ?>"><i class="fas fa-file-alt me-1"></i> Ujian</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('guru/laporan/index.php') ?>"><i class="fas fa-chart-bar me-1"></i> Laporan</a>
                </li>

                <?php elseif ($current_role === 'siswa'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('siswa/index.php') ?>"><i class="fas fa-home me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('siswa/ujian/index.php') ?>"><i class="fas fa-file-alt me-1"></i> Ujian</a>
                </li>

                <?php elseif ($current_role === 'pengawas'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('pengawas/index.php') ?>"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('pengawas/monitoring.php') ?>"><i class="fas fa-desktop me-1"></i> Monitoring</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('pengawas/log_aktivitas.php') ?>"><i class="fas fa-history me-1"></i> Log</a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <span class="d-none d-md-inline"><?= e($current_nama) ?></span>
                        <span class="badge bg-light text-primary ms-1"><?= e(ucfirst($current_role)) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= e($current_username) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
