<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');

$userId = getCurrentUserId();

function generateUuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// --- Handle Attendance Intake Request Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'request_attendance') {
    $eventId = $_POST['event_id'];
    $membershipId = $_POST['membership_id'];
    $attendanceId = generateUuid4();
    
    try {
        $stmtCheck = $pdo->prepare("SELECT AttendanceID FROM ATTENDANCE WHERE EventID = ? AND MembershipID = ?");
        $stmtCheck->execute([$eventId, $membershipId]);
        
        if ($stmtCheck->rowCount() > 0) {
            $error = "You have already filed an attendance application for this event.";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO ATTENDANCE (AttendanceID, MembershipID, EventID, CheckInTime, Status) VALUES (?, ?, ?, NOW(), 'Pending')");
            $stmtInsert->execute([$attendanceId, $membershipId, $eventId]);
            $success = "Attendance request logged! Waiting for an organization officer to approve.";
        }
    } catch (PDOException $e) {
        $error = "System Error: " . $e->getMessage();
    }
}

try {
    $stmtStudent = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $stmtStudent->execute([$userId]);
    $student = $stmtStudent->fetch();

    // Query gathers events for joined orgs alongside custom left-joined validation status parameters
    $stmtEvents = $pdo->prepare("
        SELECT e.*, o.OrgName, m.MembershipID, a.Status AS AttendanceStatus
        FROM EVENT e
        JOIN ORGANIZATION o ON e.OrgID = o.OrgID
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        LEFT JOIN ATTENDANCE a ON e.EventID = a.EventID AND m.MembershipID = a.MembershipID
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
            <a href="organizations.php">Organizations</a>
            <a href="events.php" class="active">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>
    <main class="main-content">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>All Organization Events</h3>
            <?php if (empty($events)): ?>
                <p class="empty-state">No active events scheduled for your organizations.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($events as $event): ?>
                        <li style="align-items: center; justify-content: space-between; display: flex;">
                            <div class="event-details">
                                <span class="dot"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                    <span class="date"><?= htmlspecialchars($event['OrgName']) ?> | <?= htmlspecialchars($event['Venue']) ?></span>
                                    <span class="date" style="display:block; margin-top: 2px; font-weight: bold; color: #555;"><?= date('F j, Y', strtotime($event['Date'])) ?></span>
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <?php if (empty($event['AttendanceStatus'])): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="request_attendance">
                                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['EventID']) ?>">
                                        <input type="hidden" name="membership_id" value="<?= htmlspecialchars($event['MembershipID']) ?>">
                                        <button type="submit" class="btn-primary" style="margin-top: 0; padding: 0.5rem 1.2rem; width: auto; font-size: 0.85rem;">Attended</button>
                                    </form>
                                <?php elseif ($event['AttendanceStatus'] === 'Pending'): ?>
                                    <span style="background: #e67e22; color: white; padding: 5px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; display: inline-block;">Pending Officer Approval</span>
                                <?php elseif ($event['AttendanceStatus'] === 'Approved'): ?>
                                    <span style="background: #2ecc71; color: white; padding: 5px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; display: inline-block;">✓ Attended</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>