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
    if(!$officerData) {
        die("Officer data not found.");
    }
    $orgId = $officerData['OrgID'];

    $stmtPendingCount = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Pending'");
    $stmtPendingCount->execute([$orgId]);
    $pendingRequests = $stmtPendingCount->fetchColumn();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $eventId = generateUuid4();
            $title = $_POST['title'];
            $date = $_POST['date'];
            $venue = $_POST['venue'];

            $stmtInsert = $pdo->prepare("INSERT INTO EVENT (EventID, OrgID, EventTitle, Date, Venue) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$eventId, $orgId, $title, $date, $venue]);
            $success = "Event created successfully.";
        } elseif ($_POST['action'] === 'delete') {
            $eventIdToDelete = $_POST['event_id'];
            $stmtDelete = $pdo->prepare("DELETE FROM EVENT WHERE EventID = ? AND OrgID = ?");
            $stmtDelete->execute([$eventIdToDelete, $orgId]);
            $success = "Event deleted successfully.";
        }
    }

    $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $stmtEvents->execute([$orgId]);
    $events = $stmtEvents->fetchAll();

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { 
        $error = "Cannot delete this event. There are attendance records attached to it.";
    } else {
        $error = "Database error: " . $e->getMessage();
    }
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
            <a href="manage_events.php" class="active">Manage Events</a>
            <a href="attendance_scanner.php">Attendance</a>
        </nav>
    </aside>

    <main class="main-content">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="content-grid" style="grid-template-columns: 1fr 2fr;">
            <div class="card">
                <h3>Create New Event</h3>
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label>Event Title:</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label>Venue:</label>
                        <input type="text" name="venue" required>
                    </div>
                    <button type="submit" class="btn-primary">Create Event</button>
                </form>
            </div>

            <div class="card">
                <h3>Existing Events</h3>
                <?php if (empty($events)): ?>
                    <p class="empty-state">No events found. Create one to get started.</p>
                <?php else: ?>
                    <ul class="clean-list">
                        <?php foreach ($events as $event): ?>
                            <li>
                                <div class="event-details">
                                    <span class="dot"></span>
                                    <div>
                                        <span class="title" style="display:block;"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                        <span class="date"><?= date('F j, Y', strtotime($event['Date'])) ?> | <?= htmlspecialchars($event['Venue']) ?></span>
                                    </div>
                                </div>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['EventID']) ?>">
                                    <button type="submit" style="background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>