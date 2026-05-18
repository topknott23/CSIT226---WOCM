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
        if ($_POST['action'] === 'create') {
            $eventId = generateUuid4();
            $title = $_POST['title'];
            $date = $_POST['date'];
            $venue = $_POST['venue'];

            $stmtInsert = $pdo->prepare("INSERT INTO EVENT (EventID, OrgID, EventTitle, Date, Venue) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$eventId, $orgId, $title, $date, $venue]);
            $success = "Event created successfully.";
        } elseif ($_POST['action'] === 'update') {
            $eventId = $_POST['event_id'];
            $title = $_POST['title'];
            $date = $_POST['date'];
            $venue = $_POST['venue'];

            $stmtUpdate = $pdo->prepare("UPDATE EVENT SET EventTitle = ?, Date = ?, Venue = ? WHERE EventID = ? AND OrgID = ?");
            $stmtUpdate->execute([$title, $date, $venue, $eventId, $orgId]);
            $success = "Event details updated successfully.";
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

    $editEvent = null;
    if (isset($_GET['edit'])) {
        $stmtEdit = $pdo->prepare("SELECT * FROM EVENT WHERE EventID = ? AND OrgID = ?");
        $stmtEdit->execute([$_GET['edit'], $orgId]);
        $editEvent = $stmtEdit->fetch();
    }
} catch (PDOException $e) {
    $error = ($e->getCode() == 23000) ? "Cannot process action. There are active attendance parameters attached." : "Database error: " . $e->getMessage();
}
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_officer.php'; ?>

    <main class="main-content">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="content-grid" style="grid-template-columns: 1fr 2fr;">
            <div class="card">
                <h3><?= $editEvent ? 'Edit Event Details' : 'Create New Event' ?></h3>
                <form method="POST" action="manage_events.php">
                    <input type="hidden" name="action" value="<?= $editEvent ? 'update' : 'create' ?>">
                    <?php if ($editEvent): ?>
                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($editEvent['EventID']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Event Title:</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($editEvent['EventTitle'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($editEvent['Date'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Venue:</label>
                        <input type="text" name="venue" value="<?= htmlspecialchars($editEvent['Venue'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn-primary"><?= $editEvent ? 'Save Changes' : 'Create Event' ?></button>
                    <?php if ($editEvent): ?>
                        <a href="manage_events.php" class="btn-secondary" style="display:block; text-align:center; margin-top:0.5rem; padding:0.8rem; text-decoration:none; font-size:0.9rem;">Cancel Edit</a>
                    <?php endif; ?>
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
                                <div style="display: flex; gap: 8px; margin: 0;">
                                    <a href="?edit=<?= htmlspecialchars($event['EventID']) ?>" style="background: transparent; color: #3498db; border: 1px solid #3498db; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">Edit</a>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['EventID']) ?>">
                                        <button type="submit" style="background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Delete</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>