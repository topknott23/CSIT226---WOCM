<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');
$userId = getCurrentUserId();

try {
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
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

include '../includes/header.php';
?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="side-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="attendance.php" class="active">Attendance</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="card">
            <h3>Attendance History</h3>
            <ul class="clean-list">
                <?php foreach ($records as $r): ?>
                    <li>
                        <div class="event-details">
                            <span class="dot" style="background-color: #2ecc71;"></span>
                            <div>
                                <span class="title"><?= htmlspecialchars($r['EventTitle']) ?></span>
                                <span class="date"><?= htmlspecialchars($r['OrgName']) ?></span>
                            </div>
                        </div>
                        <span class="date">Checked in: <?= date('M j, Y h:i A', strtotime($r['CheckInTime'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>