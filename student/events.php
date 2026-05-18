<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student', 'Officer');

$userId = getCurrentUserId();

// --- Handle Attendance Intake Request Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'request_attendance') {
    $eventId = $_POST['event_id'];
    $membershipId = $_POST['membership_id'];
    $attendanceId = generateUuid4(); // Calls global centralized utility
    
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
    <?php include '../includes/sidebar_student.php'; ?>
    
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
                <p class="empty-state">No events scheduled for your active organizations yet.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($events as $event): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: <?= $event['AttendanceStatus'] === 'Approved' ? '#2ecc71' : ($event['AttendanceStatus'] === 'Pending' ? '#F1C40F' : '#EBCF1E') ?>;"></span>
                                <div>
                                    <span class="title" style="display:block; font-weight: 600;"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                    <span class="date" style="display:block; color:#666; font-size:0.85rem; margin-top: 2px;">
                                        <strong>Org:</strong> <?= htmlspecialchars($event['OrgName']) ?> | <strong>Venue:</strong> <?= htmlspecialchars($event['Venue']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span class="date"><?= date('M j, Y', strtotime($event['Date'])) ?></span>
                                
                                <?php if (empty($event['AttendanceStatus'])): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="request_attendance">
                                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['EventID']) ?>">
                                        <input type="hidden" name="membership_id" value="<?= htmlspecialchars($event['MembershipID']) ?>">
                                        <button type="submit" class="btn-primary" style="margin-top: 0; padding: 0.5rem 1rem; width: auto; font-size: 0.85rem;">Request Attendance</button>
                                    </form>
                                <?php elseif ($event['AttendanceStatus'] === 'Pending'): ?>
                                    <span style="background: #FEF9E7; color: #B7950B; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">Pending Approval</span>
                                <?php elseif ($event['AttendanceStatus'] === 'Approved'): ?>
                                    <span style="background: #d4edda; color: #155724; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">✓ Attended</span>
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