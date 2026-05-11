<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');
$userId = getCurrentUserId();

try {
    $student = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $student->execute([$userId]);
    $studentData = $student->fetch();

    $stmtEvents = $pdo->prepare("
        SELECT e.*, o.OrgName FROM EVENT e
        JOIN ORGANIZATION o ON e.OrgID = o.OrgID
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved'
        ORDER BY e.Date ASC
    ");
    $stmtEvents->execute([$userId]);
    $events = $stmtEvents->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

include '../includes/header.php'; 
?>
<div class="dashboard-layout">
    <main class="main-content">
        <div class="card">
            <h3>Upcoming Organization Events</h3>
            <ul class="clean-list">
                <?php foreach ($events as $ev): ?>
                    <li>
                        <div class="event-details">
                            <span class="dot"></span>
                            <div>
                                <span class="title"><?= htmlspecialchars($ev['EventTitle']) ?></span>
                                <span class="date"><?= htmlspecialchars($ev['OrgName']) ?> | <?= htmlspecialchars($ev['Venue']) ?></span>
                            </div>
                        </div>
                        <span class="date"><?= date('F j, Y', strtotime($ev['Date'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>