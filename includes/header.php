<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = '/wocm'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WOCM - Wildcat Org-Connect</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="<?= isset($_SESSION['user_id']) ? 'dashboard-body' : '' ?>">

<?php if (isset($_SESSION['user_id'])): ?>
    <header class="app-header">
        <div class="logo-container">
            <h1>WOCM</h1>
        </div>
        <div class="header-profile">
            <span><?= htmlspecialchars($_SESSION['user_type']) ?></span>
            <a href="<?= $baseUrl ?>/logout.php" class="btn-logout-small">Logout</a>
        </div>
    </header>
    <?php else: ?>
    <header class="app-header">
        <div class="logo-container">
            <img src="<?= $baseUrl ?>/assets/img/cit-logo.png" alt="CIT-U Logo" class="logo" onerror="this.style.display='none'">
            <h1>WOCM</h1>
        </div>
    </header>
    <main> <?php endif; ?>