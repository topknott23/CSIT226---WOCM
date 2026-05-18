<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['fullName']);
    $course = trim($_POST['course']);
    $yearLevel = trim($_POST['yearLevel']);
    try {
        $stmtUpdate = $pdo->prepare("UPDATE STUDENT SET FullName = ?, Course = ?, YearLevel = ? WHERE UserID = ?");
        $stmtUpdate->execute([$fullName, $course, $yearLevel, $userId]);
        $success = "Profile details updated successfully!";
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

try {
    $stmtOfficer = $pdo->prepare("
        SELECT s.FullName, s.StudentID, s.Course, s.YearLevel, u.Email
        FROM STUDENT s JOIN USER u ON s.UserID = u.UserID WHERE s.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_officer.php'; ?>

    <main class="main-content">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h3 style="text-align: center; border-bottom: 2px solid #F8F5F2; padding-bottom: 1rem;">Edit Profile</h3>
            
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="profile.php" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Email Address (Read-only)</label>
                    <input type="email" value="<?= htmlspecialchars($officerData['Email']) ?>" disabled style="background-color: #eee; cursor: not-allowed;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Student ID (Read-only)</label>
                    <input type="text" value="<?= htmlspecialchars($officerData['StudentID']) ?>" disabled style="background-color: #eee; cursor: not-allowed;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Full Name</label>
                    <input type="text" name="fullName" value="<?= htmlspecialchars($officerData['FullName']) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Course</label>
                    <input type="text" name="course" value="<?= htmlspecialchars($officerData['Course']) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Year Level</label>
                    <input type="text" name="yearLevel" value="<?= htmlspecialchars($officerData['YearLevel']) ?>" required>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 1rem;">Save Changes</button>
            </form>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>