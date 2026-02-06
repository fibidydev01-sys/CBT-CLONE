<?php
/**
 * Helper Functions
 * CBT Nusantara - Single School Edition
 */

/**
 * Sanitize output untuk prevent XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia
 * @param string $date Date string (Y-m-d)
 * @return string
 */
function format_tanggal($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = new DateTime($date);
    return $d->format('d') . ' ' . $bulan[(int)$d->format('m')] . ' ' . $d->format('Y');
}

/**
 * Format waktu (HH:MM)
 */
function format_waktu($time) {
    return date('H:i', strtotime($time));
}

/**
 * Format datetime Indonesia
 */
function format_datetime($datetime) {
    if (!$datetime) return '-';
    return format_tanggal(date('Y-m-d', strtotime($datetime))) . ' ' . date('H:i', strtotime($datetime));
}

/**
 * Generate flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get dan hapus flash message
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message sebagai HTML alert
 */
function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = e($flash['type']);
        $message = e($flash['message']);
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Generate token ujian (6 digit)
 */
function generate_token() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Pagination helper
 * @return array ['offset', 'limit', 'current_page', 'total_pages']
 */
function paginate($total_rows, $per_page = 20) {
    $total_pages = max(1, ceil($total_rows / $per_page));
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $current_page = min($current_page, $total_pages);
    $offset = ($current_page - 1) * $per_page;

    return [
        'offset' => $offset,
        'limit' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'total_rows' => $total_rows
    ];
}

/**
 * Render pagination links
 */
function render_pagination($pagination, $base_url = '?') {
    if ($pagination['total_pages'] <= 1) return;

    $sep = strpos($base_url, '?') !== false ? '&' : '?';

    echo '<nav><ul class="pagination justify-content-center">';

    // Previous
    if ($pagination['current_page'] > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $base_url . $sep . 'page=' . ($pagination['current_page'] - 1) . '">&laquo;</a></li>';
    }

    // Pages
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

    if ($start > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $base_url . $sep . 'page=1">1</a></li>';
        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['current_page'] ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $base_url . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        echo '<li class="page-item"><a class="page-link" href="' . $base_url . $sep . 'page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }

    // Next
    if ($pagination['current_page'] < $pagination['total_pages']) {
        echo '<li class="page-item"><a class="page-link" href="' . $base_url . $sep . 'page=' . ($pagination['current_page'] + 1) . '">&raquo;</a></li>';
    }

    echo '</ul></nav>';
}

/**
 * Redirect dengan flash message
 */
function redirect($url, $type = null, $message = null) {
    if ($type && $message) {
        set_flash($type, $message);
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Check apakah request adalah AJAX
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Get nama role dalam bahasa Indonesia
 */
function role_label($role) {
    $labels = [
        'admin' => 'Administrator',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
        'pengawas' => 'Pengawas'
    ];
    return $labels[$role] ?? $role;
}

/**
 * Format angka untuk nilai
 */
function format_nilai($nilai) {
    if ($nilai === null) return '-';
    return number_format((float)$nilai, 1, ',', '.');
}
