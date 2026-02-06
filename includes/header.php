<?php
/**
 * Header Template
 * CBT Nusantara - Single School Edition
 *
 * Variables yang bisa di-set sebelum include:
 * - $page_title : Judul halaman
 * - $extra_css   : Array CSS tambahan
 * - $hide_navbar : true untuk hide navbar (misal di halaman ujian)
 */

if (!isset($page_title)) $page_title = 'CBT Nusantara';
if (!isset($extra_css)) $extra_css = [];
if (!isset($hide_navbar)) $hide_navbar = false;

// Load settings untuk school name & logo
require_once __DIR__ . '/../config/settings.php';
$school_name = get_setting('school_name', $conn, 'CBT Nusantara');
$school_logo = get_setting('school_logo', $conn, 'assets/img/logo.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= e($page_title) ?> - <?= e($school_name) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Global CSS -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/responsive.css') ?>" rel="stylesheet">

    <?php foreach ($extra_css as $css): ?>
    <link href="<?= base_url($css) ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body>
<?php if (!$hide_navbar): ?>
<?php include __DIR__ . '/navbar.php'; ?>
<?php endif; ?>

<div class="main-wrapper">
