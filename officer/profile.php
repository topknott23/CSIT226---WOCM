<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    // Fetch detailed officer profile from both OFFICER and STUDENT tables for the sidebar
    $stmtOfficer = $pdo->prepare("
        SELECT o.Position, org.OrgID, org.OrgName, s.FullName, s.StudentID, s.Course, s.YearLevel, u.Email
        FROM OFFICER o
        JOIN ORGANIZATION org ON o.OrgID = org.OrgID
        JOIN STUDENT s ON o.UserID = s.UserID
        JOIN USER u ON o.UserID = u.UserID
        WHERE o.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();

    if (!$officerData) {
        die("Access Denied: Officer data missing.");
    }
    $orgId = $officerData['OrgID'];

    // Sidebar badge counts
    $stmtPendingCount = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Pending'");
    $stmtPendingCount->execute([$orgId]);
    $pendingRequests = $stmtPendingCount->fetchColumn();

    // Handle Form Submission for Updating Personal Profile Details
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
        $fullName = trim($_POST['fullName']);
        $course = trim($_POST['course']);
        $yearLevel = trim($_POST['yearLevel']);

        $stmtUpdate = $pdo->prepare("UPDATE STUDENT SET FullName = ?, Course = ?, YearLevel = ? WHERE UserID = ?");
        $stmtUpdate->execute([$fullName, $course, $yearLevel, $userId]);
        $success = "Profile details updated successfully!";
        
        // Refresh fresh dataset for immediate rendering
        $stmtOfficer->execute([$userId]);
        $officerData = $stmtOfficer->fetch();
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($officerData['FullName'], 0, 1)) ?></div>
            <h3><?= htmlspecialchars($officerData['FullName']) ?></h3>
            <p class="student-id"><?= htmlspecialchars($officerData['StudentID']) ?></p>
            <p class="student-id"><?= htmlspecialchars($officerData['Position']) ?></p>
            <p class="student-id" style="font-weight: bold; margin-top: 5px; color: #6B1A22;"><?= htmlspecialchars($officerData['OrgName']) ?></p>
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
            <a href="attendance_scanner.php">Attendance Approvals</a>
            <a href="profile.php" class="active">Profile</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h3 style="text-align: center; border-bottom: 2px solid #F8F5F2; padding-bottom: 1rem;">Edit Profile</h3>
            
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                    <?= htmlspecialchars($success) ?>
                </div>
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