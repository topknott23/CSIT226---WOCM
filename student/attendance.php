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
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_student.php'; ?>
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