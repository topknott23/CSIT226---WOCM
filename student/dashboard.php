<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student', 'Officer');

$userId = getCurrentUserId();

try {
    // 1. Count Active Approved Organizations
    $stmtOrgs = $pdo->prepare("SELECT COUNT(*) as total FROM MEMBERSHIP WHERE StudentUserID = ? AND Status = 'Approved'");
    $stmtOrgs->execute([$userId]);
    $totalOrgs = $stmtOrgs->fetch()['total'];

    // 2. Count Verified Attendances Only
    $stmtEvents = $pdo->prepare("
        SELECT COUNT(*) as total FROM ATTENDANCE a
        JOIN MEMBERSHIP m ON a.MembershipID = m.MembershipID
        WHERE m.StudentUserID = ? AND a.Status = 'Approved'
    ");
    //connects attendance record to membership records to count how many 
    // approved event check ins a specific student has
    $stmtEvents->execute([$userId]);
    $totalEventsAttended = $stmtEvents->fetch()['total'];

    // 3. Calculate Total Possible Events across joined Orgs
    $stmtPossible = $pdo->prepare("
        SELECT COUNT(*) as total FROM EVENT e
        JOIN MEMBERSHIP m ON e.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved' AND e.Date <= CURDATE()
    ");
    //matches events with the clubs a student has approved memberships in 
    // to calculate the total number of events they could have attended
    $stmtPossible->execute([$userId]);
    $totalPossibleEvents = $stmtPossible->fetch()['total'];

    if ($totalEventsAttended > $totalPossibleEvents) {
        $totalPossibleEvents = $totalEventsAttended;
    }
    $missedEvents = max(0, $totalPossibleEvents - $totalEventsAttended);

    // 4. Gather Upcoming Scheduled Events List
    $stmtUpcoming = $pdo->prepare("
        SELECT e.EventTitle, e.Date, o.OrgName FROM EVENT e
        JOIN ORGANIZATION o ON e.OrgID = o.OrgID
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved' AND e.Date >= CURDATE()
        ORDER BY e.Date ASC LIMIT 5
    ");
    //links events, organization, and memberships
    //  together to display a timeline of upcoming events
    //  for the clubs the student belongs to.
    $stmtUpcoming->execute([$userId]);
    $upcomingEvents = $stmtUpcoming->fetchAll();

} catch (PDOException $e) {
    die("Error tracking metrics: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <?php include '../includes/sidebar_student.php'; ?>

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
            plugins: { legend: { display: false }, tooltip: { enabled: (attended > 0 || missed > 0) } }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>