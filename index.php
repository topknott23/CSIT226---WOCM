<?php
session_start();
require_once 'includes/db_connect.php';
include 'includes/header.php';
?>


<style>
    main{background-image:none;}
    body {
        background-image: url('assets/img/backdrop.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-repeat: no-repeat;
    }
</style>

<div class="guest-container">
    <div class="guest-header">
        <h2>Welcome to Wildcat Org-Connect</h2>
        <p>Discover campus organizations and join upcoming events.</p>
        <div class="guest-actions">
            <a href="login.php" class="btn-secondary">Login</a>
            <a href="register.php" class="btn-primary" style="width: auto; margin-top: 0;">Join Now!</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>