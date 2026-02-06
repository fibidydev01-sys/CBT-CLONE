<?php
/**
 * System Settings Loader
 * CBT Nusantara - Single School Edition
 *
 * Load settings dari database ke array global
 */

/**
 * Get single setting value
 * @param string $key Setting key
 * @param mysqli $conn Database connection
 * @param string $default Default value jika tidak ditemukan
 * @return string
 */
function get_setting($key, $conn, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result ? $result['setting_value'] : $default;
}

/**
 * Update single setting value
 * @param string $key Setting key
 * @param string $value Setting value
 * @param mysqli $conn Database connection
 * @return bool
 */
function update_setting($key, $value, $conn) {
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Load semua settings ke array
 * @param mysqli $conn Database connection
 * @return array Key-value pairs
 */
function load_all_settings($conn) {
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}
