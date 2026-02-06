<?php
/**
 * Database Connection Configuration
 * CBT Nusantara - Single School Edition
 *
 * Ubah kredensial sesuai server Anda
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cbt_pkbm_albarakah');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('<div style="text-align:center;padding:50px;font-family:sans-serif;">
        <h2>Koneksi Database Gagal</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Pastikan MySQL sudah berjalan dan database <b>' . DB_NAME . '</b> sudah dibuat.</p>
    </div>');
}

// Set charset
$conn->set_charset('utf8mb4');

// Set timezone
$conn->query("SET time_zone = '+07:00'");
