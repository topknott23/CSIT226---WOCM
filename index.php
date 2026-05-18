<?php

session_start();
require_once 'includes/db_connect.php';

try {
    // 1. Fetch all active organizations
    $stmtOrgs = $pdo->query("SELECT OrgName, Category, DateEstablished FROM ORGANIZATION ORDER BY OrgName ASC");
    $organizations = $stmtOrgs->fetchAll();

    // 2. Fetch upcoming public events
    $stmtEvents = $pdo->query("
        SELECT e.EventTitle, e.Date, e.Venue, o.OrgName 
        FROM EVENT e
        JOIN ORGANIZATION o ON e.OrgID = o.OrgID
        WHERE e.Date >= CURDATE()
        ORDER BY e.Date ASC 
        LIMIT 10
    ");
    $events = $stmtEvents->fetchAll();

} catch (PDOException $e) {
    die("Error fetching public data: " . $e->getMessage());
}
?>

<?php include 'includes/header.php'; ?>

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