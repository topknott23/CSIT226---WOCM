<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();

    $stmtOrgs = $pdo->prepare("SELECT COUNT(*) as total FROM MEMBERSHIP WHERE StudentUserID = ? AND Status = 'Approved'");
    $stmtOrgs->execute([$userId]);
    $totalOrgs = $stmtOrgs->fetch()['total'];

    $stmtEvents = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM ATTENDANCE a
        JOIN MEMBERSHIP m ON a.MembershipID = m.MembershipID
        WHERE m.StudentUserID = ?
    ");
    $stmtEvents->execute([$userId]);
    $totalEvents = $stmtEvents->fetch()['total'];

    $stmtUpcoming = $pdo->prepare("
        SELECT e.EventTitle, e.Date, o.OrgName 
        FROM EVENT e
        JOIN ORGANIZATION o ON e.OrgID = o.OrgID
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved' AND e.Date >= CURDATE()
        ORDER BY e.Date ASC LIMIT 5
    ");
    $stmtUpcoming->execute([$userId]);
    $upcomingEvents = $stmtUpcoming->fetchAll();

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <h3><?= htmlspecialchars($student['FullName']) ?></h3>
            <p class="student-id"><?= htmlspecialchars($student['StudentID']) ?></p>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="my_orgs.php">My Organizations</a>
            <a href="events.php">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
        <div class="logout-container">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </aside>

    <section class="main-content">
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Organizations</h4>
                <span class="stat-value"><?= $totalOrgs ?></span>
            </div>
            <div class="stat-card">
                <h4>Total Events Attended</h4>
                <span class="stat-value"><?= $totalEvents ?></span>
            </div>
        </div>

        <div class="dashboard-section">
            <h3>Upcoming Events</h3>
            <?php if (empty($upcomingEvents)): ?>
                <p class="empty-state">No upcoming events found.</p>
            <?php else: ?>
                <ul class="event-list">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <li class="event-item">
                            <span class="event-title"><?= htmlspecialchars($event['EventTitle']) ?></span>
                            <span class="event-org"><?= htmlspecialchars($event['OrgName']) ?></span>
                            <span class="event-date"><?= date('F j, Y', strtotime($event['Date'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>