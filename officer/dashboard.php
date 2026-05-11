<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');
$userId = getCurrentUserId();

try {
    $officer = $pdo->prepare("SELECT o.*, org.OrgName FROM OFFICER o JOIN ORGANIZATION org ON o.OrgID = org.OrgID WHERE o.UserID = ?");
    $officer->execute([$userId]);
    $offData = $officer->fetch();
    
    $events = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $events->execute([$offData['OrgID']]);
    $eventList = $events->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

include '../includes/header.php'; 
?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($offData['Position'], 0, 1)) ?></div>
            <h3>Officer View</h3>
            <p class="student-id"><?= htmlspecialchars($offData['OrgName']) ?></p>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="manage_member.php">Member Approvals</a>
            <a href="manage_events.php">Manage Events</a>
            <a href="attendance_scanner.php">Attendance</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="card">
            <h3>Organization Events</h3>
            <ul class="clean-list">
                <?php foreach ($eventList as $ev): ?>
                    <li>
                        <div class="event-details">
                            <span class="dot"></span>
                            <div>
                                <span class="title"><?= htmlspecialchars($ev['EventTitle']) ?></span>
                                <span class="date"><?= htmlspecialchars($ev['Venue']) ?></span>
                            </div>
                        </div>
                        <span class="date"><?= date('M j, Y', strtotime($ev['Date'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>