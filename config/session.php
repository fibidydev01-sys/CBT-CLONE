<?php
/**
 * Session Management
 * CBT Nusantara - Single School Edition
 */

if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set('session.gc_maxlifetime', 7200); // 2 jam
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

/**
 * Check apakah user sudah login
 */
function check_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: /cbt/login.php');
        exit();
    }
}

/**
 * Check role user - apakah diizinkan akses halaman ini
 * @param array $allowed_roles Array role yang diizinkan
 */
function check_role($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: /cbt/403.php');
        exit();
    }
}

/**
 * Lock session siswa (prevent double login)
 */
function lock_student_session($siswa_id, $conn) {
    $session_id = session_id();
    $stmt = $conn->prepare("UPDATE siswa SET session_id = ? WHERE id = ?");
    $stmt->bind_param("si", $session_id, $siswa_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Verify session siswa masih valid (belum di-hijack / double login)
 */
function verify_student_session($siswa_id, $conn) {
    $current_session = session_id();
    $stmt = $conn->prepare("SELECT session_id FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result && $result['session_id'] !== $current_session) {
        session_destroy();
        header('Location: /cbt/login.php?error=session_hijacked');
        exit();
    }
}

/**
 * Get base URL for the application
 */
function base_url($path = '') {
    return '/cbt/' . ltrim($path, '/');
}
