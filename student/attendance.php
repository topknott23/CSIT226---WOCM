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

    $stmtAtt = $pdo->prepare("
        SELECT a.CheckInTime, e.EventTitle, o.OrgName
        FROM ATTENDANCE a
        JOIN EVENT e ON a.EventID = e.EventID
        JOIN MEMBERSHIP m ON a.MembershipID = m.MembershipID
        JOIN ORGANIZATION o ON m.OrgID = o.OrgID
        WHERE m.StudentUserID = ?
        ORDER BY a.CheckInTime DESC
    ");
    $stmtAtt->execute([$userId]);
    $records = $stmtAtt->fetchAll();
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
            <a href="organizations.php">Organizations</a>
            <a href="events.php">Events</a>
            <a href="attendance.php" class="active">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="card">
            <h3>Attendance History</h3>
            <?php if (empty($records)): ?>
                <p class="empty-state">No attendance records found.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($records as $record): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: #2ecc71;"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($record['EventTitle']) ?></span>
                                    <span class="date"><?= htmlspecialchars($record['OrgName']) ?></span>
                                </div>
                            </div>
                            <span class="date">Checked in: <?= date('M j, Y h:i A', strtotime($record['CheckInTime'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>