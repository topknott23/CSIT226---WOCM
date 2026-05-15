<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

function generateUuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    $stmtOfficer = $pdo->prepare("
        SELECT o.Position, org.OrgID, org.OrgName 
        FROM OFFICER o
        JOIN ORGANIZATION org ON o.OrgID = org.OrgID
        WHERE o.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();
    $orgId = $officerData['OrgID'];

    $stmtPendingCount = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Pending'");
    $stmtPendingCount->execute([$orgId]);
    $pendingRequests = $stmtPendingCount->fetchColumn();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_id']) && isset($_POST['membership_id'])) {
        $eventId = $_POST['event_id'];
        $membershipId = $_POST['membership_id'];
        $attendanceId = generateUuid4();
        
        $stmtCheck = $pdo->prepare("SELECT AttendanceID FROM ATTENDANCE WHERE EventID = ? AND MembershipID = ?");
        $stmtCheck->execute([$eventId, $membershipId]);
        
        if ($stmtCheck->rowCount() > 0) {
            $error = "This member has already been checked into this event.";
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO ATTENDANCE (AttendanceID, MembershipID, EventID, CheckInTime) VALUES (?, ?, ?, NOW())");
            $stmtInsert->execute([$attendanceId, $membershipId, $eventId]);
            $success = "Attendance recorded successfully.";
        }
    }

    $stmtEvents = $pdo->prepare("SELECT EventID, EventTitle, Date FROM EVENT WHERE OrgID = ? ORDER BY Date DESC");
    $stmtEvents->execute([$orgId]);
    $events = $stmtEvents->fetchAll();

    $stmtMembers = $pdo->prepare("
        SELECT m.MembershipID, s.FullName, s.StudentID 
        FROM MEMBERSHIP m
        JOIN STUDENT s ON m.StudentUserID = s.UserID
        WHERE m.OrgID = ? AND m.Status = 'Approved'
        ORDER BY s.FullName ASC
    ");
    $stmtMembers->execute([$orgId]);
    $members = $stmtMembers->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($officerData['Position'], 0, 1)) ?></div>
            <h3>Officer View</h3>
            <p class="student-id"><?= htmlspecialchars($officerData['Position']) ?></p>
            <p class="student-id" style="font-weight: bold; margin-top: 5px;"><?= htmlspecialchars($officerData['OrgName']) ?></p>
        </div>
        <nav class="side-nav">
            <p class="nav-label">Navigation</p>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_members.php">
                Member Approvals 
                <?php if($pendingRequests > 0): ?>
                    <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem; float: right;"><?= $pendingRequests ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_events.php">Manage Events</a>
            <a href="attendance_scanner.php" class="active">Attendance</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="content-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="card">
                <h3>Manual Attendance Entry</h3>
                <?php if (isset($success)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (empty($events) || empty($members)): ?>
                    <p class="empty-state">You need both active events and approved members to take attendance.</p>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Select Event:</label>
                            <select name="event_id" required>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= htmlspecialchars($event['EventID']) ?>">
                                        <?= htmlspecialchars($event['EventTitle']) ?> (<?= date('M j', strtotime($event['Date'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Select Member:</label>
                            <select name="membership_id" required>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?= htmlspecialchars($member['MembershipID']) ?>">
                                        <?= htmlspecialchars($member['FullName']) ?> - <?= htmlspecialchars($member['StudentID']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Mark as Present</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>System Instructions</h3>
                <ul style="padding-left: 20px; line-height: 1.6; color: #555;">
                    <li>Ensure you have created the event in the <strong>Manage Events</strong> tab before taking attendance.</li>
                    <li>Only students whose membership status is <strong>Approved</strong> will appear in the member dropdown.</li>
                    <li>Students can view their attendance history in real-time from their personal Dashboard.</li>
                    <li>Once an attendance record is linked to an event, that event cannot be deleted from the system (Database Persistence Rule).</li>
                </ul>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>