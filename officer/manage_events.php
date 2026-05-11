<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $stmtInsert = $pdo->prepare("INSERT INTO EVENT (EventID, OrgID, EventTitle, Date, Venue) VALUES (UUID(), ?, ?, ?, ?)");
            $stmtInsert->execute([$orgId, $_POST['title'], $_POST['date'], $_POST['venue']]);
            $success = "Event created successfully.";
        } elseif ($_POST['action'] === 'delete') {
            $stmtDelete = $pdo->prepare("DELETE FROM EVENT WHERE EventID = ? AND OrgID = ?");
            $stmtDelete->execute([$_POST['event_id'], $orgId]);
            $success = "Event deleted successfully.";
        }
    }

    $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $stmtEvents->execute([$orgId]);
    $events = $stmtEvents->fetchAll();

} catch (PDOException $e) {
    $error = ($e->getCode() == 23000) ? "Cannot delete event with existing attendance records." : "Error: " . $e->getMessage();
}
?>
<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($officerData['Position'], 0, 1)) ?></div>
            <h3>Officer View</h3>
            <p class="student-id"><?= htmlspecialchars($officerData['OrgName']) ?></p>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_member.php">Member Approvals</a>
            <a href="manage_events.php" class="active">Manage Events</a>
            <a href="attendance_scanner.php">Attendance</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="content-grid" style="grid-template-columns: 1fr 2fr;">
            <div class="card">
                <h3>Create New Event</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group"><label>Title:</label><input type="text" name="title" required></div>
                    <div class="form-group"><label>Date:</label><input type="date" name="date" required></div>
                    <div class="form-group"><label>Venue:</label><input type="text" name="venue" required></div>
                    <button type="submit" class="btn-primary">Create Event</button>
                </form>
            </div>
            <div class="card">
                <h3>Existing Events</h3>
                <ul class="clean-list">
                    <?php foreach ($events as $event): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot"></span>
                                <div>
                                    <span class="title"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                    <span class="date"><?= date('M j, Y', strtotime($event['Date'])) ?></span>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this event?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?= $event['EventID'] ?>">
                                <button type="submit" style="color:#e74c3c; background:none; border:none; cursor:pointer;">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>