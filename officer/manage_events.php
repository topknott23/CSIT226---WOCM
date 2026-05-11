<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

requireRole('Officer');

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

try {
    $stmtOfficer = $pdo->prepare("SELECT OrgID FROM OFFICER WHERE UserID = ?");
    $stmtOfficer->execute([$userId]);
    $orgId = $stmtOfficer->fetchColumn();

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
            $success = "Event deleted.";
        }
    }

    $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $stmtEvents->execute([$orgId]);
    $events = $stmtEvents->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="side-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_members.php">Member Approvals</a>
            <a href="manage_events.php" class="active">Manage Events</a>
            <a href="attendance_scanner.php">Attendance</a>
        </nav>
        <div class="logout-container">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </aside>

    <section class="main-content">
        <h3>Manage Events</h3>

        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="guest-card" style="margin-bottom: 2rem;">
            <h4>Create New Event</h4>
            <form method="POST">
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

        <div class="guest-card">
            <h4>Existing Events</h4>
            <?php if (empty($events)): ?>
                <p>No events found.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <tr style="border-bottom: 2px solid #6B1A22; text-align: left;">
                        <th style="padding: 0.5rem;">Title</th>
                        <th style="padding: 0.5rem;">Date</th>
                        <th style="padding: 0.5rem;">Venue</th>
                        <th style="padding: 0.5rem;">Action</th>
                    </tr>
                    <?php foreach ($events as $event): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($event['EventTitle']) ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($event['Date']) ?></td>
                            <td style="padding: 0.5rem;"><?= htmlspecialchars($event['Venue']) ?></td>
                            <td style="padding: 0.5rem;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? Cannot delete if attendance exists.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['EventID']) ?>">
                                    <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>