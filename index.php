<?php
session_start();
require_once 'includes/db_connect.php';

try {
    // Fetch active organizations and upcoming events [cite: 16, 19]
    $organizations = $pdo->query("SELECT OrgName, Category, DateEstablished FROM ORGANIZATION ORDER BY OrgName ASC")->fetchAll();
    $events = $pdo->query("SELECT e.EventTitle, e.Date, e.Venue, o.OrgName FROM EVENT e JOIN ORGANIZATION o ON e.OrgID = o.OrgID WHERE e.Date >= CURDATE() ORDER BY e.Date ASC LIMIT 10")->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

include 'includes/header.php'; 
?>
<div class="guest-container">
    <div class="guest-header">
        <h2>Welcome to Wildcat Org-Connect</h2>
        <p>Discover campus organizations and join upcoming events.</p>
        <div class="guest-actions">
            <a href="login.php" class="btn-secondary">Login</a>
            <a href="register.php" class="btn-primary" style="width: auto; margin-top: 0;">Join Now!</a>
        </div>
    </div>

    <div class="guest-grid">
        <div class="guest-card">
            <h3>View Organizations</h3>
            <div class="scrollable-list">
                <?php foreach ($organizations as $org): ?>
                    <div class="list-item">
                        <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                        <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?> | Est: <?= date('Y', strtotime($org['DateEstablished'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="guest-card">
            <h3>View Events</h3>
            <div class="scrollable-list">
                <?php foreach ($events as $event): ?>
                    <div class="list-item">
                        <h4><?= htmlspecialchars($event['EventTitle']) ?></h4>
                        <p class="meta-text">By: <?= htmlspecialchars($event['OrgName']) ?></p>
                        <p class="meta-text"><?= date('M j, Y', strtotime($event['Date'])) ?> | <?= htmlspecialchars($event['Venue']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>