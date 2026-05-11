<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');
$userId = getCurrentUserId();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("INSERT INTO ATTENDANCE (AttendanceID, MembershipID, EventID, CheckInTime) VALUES (UUID(), ?, ?, NOW())");
    $stmt->execute([$_POST['membership_id'], $_POST['event_id']]);
    $success = "Attendance recorded.";
}

// Fetch events and approved members for the dropdowns
include '../includes/header.php';
?>
<div class="dashboard-layout">
    <main class="main-content">
        <div class="card">
            <h3>Manual Attendance Entry</h3>
            <form method="POST">
                <div class="form-group"><label>Select Event:</label><select name="event_id" required>...</select></div>
                <div class="form-group"><label>Select Member:</label><select name="membership_id" required>...</select></div>
                <button type="submit" class="btn-primary">Mark as Present</button>
            </form>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>