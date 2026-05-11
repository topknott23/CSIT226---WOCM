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

    // Stats for Dashboard [cite: 49, 50]
    $totalOrgs = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE StudentUserID = ? AND Status = 'Approved'");
    $totalOrgs->execute([$userId]);
    
    $totalAttended = $pdo->prepare("SELECT COUNT(*) FROM ATTENDANCE a JOIN MEMBERSHIP m ON a.MembershipID = m.MembershipID WHERE m.StudentUserID = ?");
    $totalAttended->execute([$userId]);
    
    $attendanceRate = $totalAttended->fetchColumn();
    $totalPossible = 40; // Target from design [cite: 54]
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

include '../includes/header.php'; 
?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($studentData['FullName'], 0, 1)) ?></div>
            <h3><?= htmlspecialchars($studentData['FullName']) ?></h3>
            <p class="student-id"><?= htmlspecialchars($studentData['StudentID']) ?></p>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="my_orgs.php">My Organization</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-stats">
            <div class="stat-box"><span class="stat-label">Total Organizations:</span> <span class="stat-value"><?= $totalOrgs->fetchColumn() ?></span></div>
            <div class="stat-box"><span class="stat-label">Total Events Attended:</span> <span class="stat-value"><?= $attendanceRate ?></span></div>
        </div>

        <div class="card chart-card">
            <h3>ATTENDANCE</h3>
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
                <div class="chart-center-text">
                    <span class="big-num"><?= $attendanceRate ?>/<?= $totalPossible ?></span>
                    <span class="small-text">Events</span>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: { datasets: [{ data: [<?= $attendanceRate ?>, <?= $totalPossible - $attendanceRate ?>], backgroundColor: ['#2ecc71', '#ecf0f1'], cutout: '80%' }] },
        options: { plugins: { legend: { display: false } } }
    });
</script>
<?php include '../includes/footer.php'; ?>