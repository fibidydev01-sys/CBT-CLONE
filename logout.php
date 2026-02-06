<?php
/**
 * Logout Handler
 * CBT Nusantara - Single School Edition
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

// Clear session siswa di database
if (isset($_SESSION['role']) && $_SESSION['role'] === 'siswa' && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE siswa SET session_id = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Destroy session
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: /cbt/login.php');
exit();
