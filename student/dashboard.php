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

    $totalPossibleEvents = 40; 
    $attendanceRate = $totalEvents;

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
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="my_orgs.php">My Organization</a>
            <a href="join_org.php">Join Organization</a>
            <a href="events.php">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>

    <div class="main-content">
        <div class="top-stats">
            <div class="stat-box">
                <span class="stat-label">Total Organizations:</span>
                <span class="stat-value"><?= $totalOrgs ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Total Events Attended:</span>
                <span class="stat-value"><?= $totalEvents ?></span>
            </div>
        </div>

        <div class="content-grid">
            <div class="card events-card">
                <h3>Upcoming Events:</h3>
                <?php if (empty($upcomingEvents)): ?>
                    <p class="empty-state">No upcoming events.</p>
                <?php else: ?>
                    <ul class="clean-list">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <li>
                                <div class="event-details">
                                    <span class="dot"></span>
                                    <span class="title"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                </div>
                                <span class="date"><?= date('m/d/Y', strtotime($event['Date'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card chart-card">
                <h3>ATTENDANCE</h3>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                    <div class="chart-center-text">
                        <span class="big-num"><?= $attendanceRate ?>/<?= $totalPossibleEvents ?></span>
                        <span class="small-text">Events</span>
                    </div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item"><span class="box present"></span> Present: <?= $attendanceRate ?></div>
                    <div class="legend-item"><span class="box missed"></span> Missed: <?= $totalPossibleEvents - $attendanceRate ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attended = <?= $attendanceRate ?>;
    const total = <?= $totalPossibleEvents ?>;
    const missed = total - attended;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Missed'],
            datasets: [{
                data: [attended, missed],
                backgroundColor: ['#2ecc71', '#ecf0f1'],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>