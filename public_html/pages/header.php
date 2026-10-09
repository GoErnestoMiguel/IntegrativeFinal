<?php
require_once 'config.php';
$page_title = $page_title ?? 'Home';
$current_page = basename($_SERVER['SCRIPT_NAME']);
$navigation = [
    'index.php' => 'Home',

    'tupad.php' => 'TUPAD',
    'fourps.php' => '4Ps',
    'checker.php' => 'Initial check',
    'register.php' => 'Apply',
    'calendar.php' => 'Calendar',
    'announcements.php' => 'Announcements',
    'help.php' => 'Help & FAQ'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | <?= e($site_name) ?></title>
    <link rel="stylesheet" href="../style/style.css"><link rel="stylesheet" href="../style/modern.css?v=20261009-import"><link rel="stylesheet" href="../style/refined.css?v=20261009-workflow">
    <script src="../javascript/script.js?v=20261002-email" defer></script><script src="../javascript/application.js?v=images2" defer></script>
<script src="../javascript/calendar-picker.js?v=20260929" defer></script><script src="../javascript/ui.js?v=20260929" defer></script>
<script src="../javascript/applicant-picker.js?v=1" defer></script><script src="../javascript/documents.js?v=images2" defer></script></head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="topbar">
    <div class="container"><?= e($barangay) ?> <span>Community assistance</span></div>
</div>
<header class="site-header">
    <div class="container header-row">
        <a href="index.php" class="brand"><span class="agency-logos"><img class="dole-logo" src="../images/dole-logo.png" alt="DOLE"><img class="dswd-logo" src="../images/dswd-logo.png" alt="DSWD"></span><span><?= e($site_name) ?><small>TUPAD & 4Ps community desk</small></span></a>
        <button type="button" class="menu-button" aria-expanded="false" aria-controls="main-nav">Menu</button>
        <nav id="main-nav" aria-label="Main navigation">
            <?php foreach ($navigation as $file => $label): ?>
                <a href="<?= e($file) ?>" <?= $current_page === $file ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
            <a href="profile.php" class="nav-button" <?= $current_page === 'profile.php' ? 'aria-current="page"' : '' ?>>My profile</a>
        </nav>
    </div>
</header>

<main id="main">










