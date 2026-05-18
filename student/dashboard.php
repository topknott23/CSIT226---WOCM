<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // 1. Fetch Student Profile Meta
    $stmt = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();

    // 2. Count Active Approved Organizations
    $stmtOrgs = $pdo->prepare("SELECT COUNT(*) as total FROM MEMBERSHIP WHERE StudentUserID = ? AND Status = 'Approved'");
    $stmtOrgs->execute([$userId]);
    $totalOrgs = $stmtOrgs->fetch()['total'];

    // 3. Count Verified Attendances Only (Status = 'Approved')
    $stmtEvents = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM ATTENDANCE a
        JOIN MEMBERSHIP m ON a.MembershipID = m.MembershipID
        WHERE m.StudentUserID = ? AND a.Status = 'Approved'
    ");
    $stmtEvents->execute([$userId]);
    $totalEventsAttended = $stmtEvents->fetch()['total'];

    // 4. Dynamically Calculate Total Possible Events across all joined Orgs
    $stmtPossible = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM EVENT e
        JOIN MEMBERSHIP m ON e.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved'
    ");
    $stmtPossible->execute([$userId]);
    $totalPossibleEvents = $stmtPossible->fetch()['total'];

    // 5. Calculate missed events safely (prevent negative values if records mismatch)
    $missedEvents = max(0, $totalPossibleEvents - $totalEventsAttended);

    // 6. Gather Up Coming Scheduled Events List
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
    die("Error tracking metrics: " . $e->getMessage());
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
            <a href="organizations.php">Organizations</a>
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
                <span class="stat-value"><?= $totalEventsAttended ?></span>
            </div>
        </div>

        <div class="content-grid">
            <div class="card events-card">
                <h3>Upcoming Events:</h3>
                <?php if (empty($upcomingEvents)): ?>
                    <p class="empty-state">No upcoming events scheduled.</p>
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
                        <span class="big-num"><?= $totalEventsAttended ?>/<?= $totalPossibleEvents ?></span>
                        <span class="small-text">Events</span>
                    </div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item"><span class="box present"></span> Present: <?= $totalEventsAttended ?></div>
                    <div class="legend-item"><span class="box missed"></span> Missed: <?= $missedEvents ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attended = <?= $totalEventsAttended ?>;
    const missed = <?= $missedEvents ?>;

    // Handle edge case: If there are no events at all, render a neutral placeholder circle 
    const finalAttended = (attended === 0 && missed === 0) ? 0 : attended;
    const finalMissed = (attended === 0 && missed === 0) ? 1 : missed;
    const chartColors = (attended === 0 && missed === 0) ? ['#bdc3c7', '#ecf0f1'] : ['#2ecc71', '#e74c3c'];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Missed'],
            datasets: [{
                data: [finalAttended, finalMissed],
                backgroundColor: chartColors,
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: (attended > 0 || missed > 0) }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>