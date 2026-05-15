<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');

$userId = getCurrentUserId();

try {
    $stmt = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();

    $stmtEvents = $pdo->prepare("
        SELECT e.*, o.OrgName 
        FROM EVENT e
        JOIN ORGANIZATION o ON e.OrgID = o.OrgID
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved'
        ORDER BY e.Date ASC
    ");
    $stmtEvents->execute([$userId]);
    $events = $stmtEvents->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($student['FullName'], 0, 1)) ?></div>
            <h3><?= htmlspecialchars($student['FullName']) ?></h3>
            <p class="student-id"><?= htmlspecialchars($student['StudentID']) ?></p>
        </div>
        <nav class="side-nav">
            <p class="nav-label">Navigation</p>
            <a href="dashboard.php">Dashboard</a>
            <a href="my_orgs.php">My Organization</a>
            <a href="join_org.php">Join Organization</a>
            <a href="events.php" class="active">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="card">
            <h3>All Organization Events</h3>
            <?php if (empty($events)): ?>
                <p class="empty-state">No events found for your organizations.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($events as $event): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                    <span class="date"><?= htmlspecialchars($event['OrgName']) ?> | <?= htmlspecialchars($event['Venue']) ?></span>
                                </div>
                            </div>
                            <span class="date"><?= date('F j, Y', strtotime($event['Date'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>