<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');

$userId = getCurrentUserId();

// --- NEW: Handle Leaving an Organization ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'leave') {
    $orgIdToLeave = $_POST['org_id'];
    
    try {
        $stmtLeave = $pdo->prepare("DELETE FROM MEMBERSHIP WHERE StudentUserID = ? AND OrgID = ?");
        $stmtLeave->execute([$userId, $orgIdToLeave]);
        $success = "You have successfully left the organization.";
    } catch (PDOException $e) {
        $error = "Error leaving organization: " . $e->getMessage();
    }
}
// -----------------------------------------

try {
    $stmt = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch();

    $stmtOrgs = $pdo->prepare("
        SELECT o.*
        FROM ORGANIZATION o
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved'
    ");
    $stmtOrgs->execute([$userId]);
    $myOrgs = $stmtOrgs->fetchAll();
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
            <a href="my_orgs.php" class="active">My Organization</a>
            <a href="join_org.php">Join Organization</a>
            <a href="events.php">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="card">
            <h3>My Organizations</h3>
            
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($myOrgs)): ?>
                <p class="empty-state">You are not a member of any organizations yet.</p>
            <?php else: ?>
                <div class="guest-grid">
                    <?php foreach ($myOrgs as $org): ?>
                        <div class="guest-card" style="border-top-color: #6B1A22;">
                            <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                            <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?></p>
                            
                            <form method="POST" style="margin-top: 1.5rem;">
                                <input type="hidden" name="action" value="leave">
                                <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['OrgID']) ?>">
                                <button type="submit" style="width: 100%; padding: 0.8rem; background-color: white; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 6px; font-weight: bold; cursor: pointer; transition: all 0.2s;">
                                    Leave Organization
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>