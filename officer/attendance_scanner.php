<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    $stmtScope = $pdo->prepare("SELECT OrgID FROM OFFICER WHERE UserID = ?");
    $stmtScope->execute([$userId]);
    $orgId = $stmtScope->fetchColumn();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $attendanceId = $_POST['attendance_id'];
        
        if ($_POST['action'] === 'approve_attendance') {
            $stmtUpdate = $pdo->prepare("UPDATE ATTENDANCE SET Status = 'Approved' WHERE AttendanceID = ?");
            $stmtUpdate->execute([$attendanceId]);
            $success = "Attendance request verified and approved.";
        } elseif ($_POST['action'] === 'reject_attendance') {
            $stmtDelete = $pdo->prepare("DELETE FROM ATTENDANCE WHERE AttendanceID = ?");
            $stmtDelete->execute([$attendanceId]);
            $success = "Attendance request successfully rejected and cleared.";
        }
    }

    $stmtPendingAtt = $pdo->prepare("
        SELECT a.AttendanceID, a.CheckInTime, e.EventTitle, s.FullName, s.StudentID, s.Course
        FROM ATTENDANCE a
        JOIN EVENT e ON a.EventID = e.EventID
        JOIN MEMBERSHIP m ON a.MembershipID = m.MembershipID
        JOIN STUDENT s ON m.StudentUserID = s.UserID
        WHERE e.OrgID = ? AND a.Status = 'Pending'
        ORDER BY a.CheckInTime DESC
    ");
    $stmtPendingAtt->execute([$orgId]);
    $pendingAttendance = $stmtPendingAtt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_officer.php'; ?>

    <main class="main-content">
        <div class="card">
            <h3>Pending Attendance Submissions</h3>
            
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($pendingAttendance)): ?>
                <p class="empty-state">No pending attendance confirmation requests found from members.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($pendingAttendance as $att): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: #e67e22;"></span>
                                <div>
                                    <span class="title" style="display:block; font-weight:600;"><?= htmlspecialchars($att['FullName']) ?> — <span style="color:#555; font-size:0.9rem; font-weight:normal;"><?= htmlspecialchars($att['EventTitle']) ?></span></span>
                                    <span class="date">ID: <?= htmlspecialchars($att['StudentID']) ?> | Course: <?= htmlspecialchars($att['Course']) ?></span>
                                    <span class="date" style="display:block; font-size:0.75rem;">Submitted: <?= date('M j, Y h:i A', strtotime($att['CheckInTime'])) ?></span>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="approve_attendance">
                                    <input type="hidden" name="attendance_id" value="<?= htmlspecialchars($att['AttendanceID']) ?>">
                                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; width: auto; margin: 0; background-color: #2ecc71;">Approve</button>
                                </form>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="reject_attendance">
                                    <input type="hidden" name="attendance_id" value="<?= htmlspecialchars($att['AttendanceID']) ?>">
                                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; width: auto; margin: 0; background-color: #e74c3c;">Reject</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>