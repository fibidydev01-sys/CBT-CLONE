<?php
/**
 * Sidebar Template - untuk halaman admin/guru/pengawas
 * CBT Nusantara
 *
 * Set $active_menu sebelum include untuk highlight menu aktif
 */
$active_menu = $active_menu ?? '';
$current_role = $_SESSION['role'] ?? '';

// Menu items per role
$menus = [];

if ($current_role === 'admin') {
    $menus = [
        ['url' => 'admin/index.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['divider' => true, 'label' => 'MANAJEMEN USER'],
        ['url' => 'admin/siswa/index.php', 'icon' => 'fas fa-user-graduate', 'label' => 'Siswa', 'key' => 'siswa'],
        ['url' => 'admin/guru/index.php', 'icon' => 'fas fa-chalkboard-teacher', 'label' => 'Guru', 'key' => 'guru'],
        ['url' => 'admin/pengawas/index.php', 'icon' => 'fas fa-user-shield', 'label' => 'Pengawas', 'key' => 'pengawas'],
        ['url' => 'admin/kelas/index.php', 'icon' => 'fas fa-school', 'label' => 'Kelas', 'key' => 'kelas'],
        ['divider' => true, 'label' => 'SOAL & UJIAN'],
        ['url' => 'admin/bank_soal/index.php', 'icon' => 'fas fa-database', 'label' => 'Bank Soal', 'key' => 'bank_soal'],
        ['url' => 'admin/ujian/index.php', 'icon' => 'fas fa-file-alt', 'label' => 'Ujian', 'key' => 'ujian'],
        ['url' => 'admin/token/generate.php', 'icon' => 'fas fa-key', 'label' => 'Token', 'key' => 'token'],
        ['divider' => true, 'label' => 'MONITORING & LAPORAN'],
        ['url' => 'admin/ujian/monitoring.php', 'icon' => 'fas fa-desktop', 'label' => 'Monitoring', 'key' => 'monitoring'],
        ['url' => 'admin/laporan/index.php', 'icon' => 'fas fa-chart-pie', 'label' => 'Laporan', 'key' => 'laporan'],
        ['divider' => true, 'label' => 'SISTEM'],
        ['url' => 'admin/settings/index.php', 'icon' => 'fas fa-cog', 'label' => 'Pengaturan', 'key' => 'settings'],
    ];
} elseif ($current_role === 'guru') {
    $menus = [
        ['url' => 'guru/index.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['divider' => true, 'label' => 'SOAL & UJIAN'],
        ['url' => 'guru/bank_soal/index.php', 'icon' => 'fas fa-database', 'label' => 'Bank Soal', 'key' => 'bank_soal'],
        ['url' => 'guru/ujian/index.php', 'icon' => 'fas fa-file-alt', 'label' => 'Ujian', 'key' => 'ujian'],
        ['divider' => true, 'label' => 'LAPORAN'],
        ['url' => 'guru/laporan/index.php', 'icon' => 'fas fa-chart-pie', 'label' => 'Laporan', 'key' => 'laporan'],
    ];
} elseif ($current_role === 'pengawas') {
    $menus = [
        ['url' => 'pengawas/index.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['divider' => true, 'label' => 'UJIAN'],
        ['url' => 'pengawas/token.php', 'icon' => 'fas fa-key', 'label' => 'Token', 'key' => 'token'],
        ['url' => 'pengawas/monitoring.php', 'icon' => 'fas fa-desktop', 'label' => 'Monitoring', 'key' => 'monitoring'],
        ['divider' => true, 'label' => 'LOG'],
        ['url' => 'pengawas/log_aktivitas.php', 'icon' => 'fas fa-history', 'label' => 'Log Aktivitas', 'key' => 'log'],
    ];
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h6 class="text-uppercase text-muted small mb-0">Menu <?= e(ucfirst($current_role)) ?></h6>
    </div>
    <ul class="sidebar-menu">
        <?php foreach ($menus as $menu): ?>
            <?php if (isset($menu['divider'])): ?>
                <li class="sidebar-divider">
                    <span><?= e($menu['label']) ?></span>
                </li>
            <?php else: ?>
                <li class="sidebar-item <?= $active_menu === $menu['key'] ? 'active' : '' ?>">
                    <a href="<?= base_url($menu['url']) ?>">
                        <i class="<?= $menu['icon'] ?>"></i>
                        <span><?= e($menu['label']) ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
