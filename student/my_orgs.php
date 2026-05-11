<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');

$userId = getCurrentUserId();

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
            <?php if (empty($myOrgs)): ?>
                <p class="empty-state">You are not a member of any organizations yet.</p>
            <?php else: ?>
                <div class="guest-grid">
                    <?php foreach ($myOrgs as $org): ?>
                        <div class="guest-card" style="border-top-color: #6B1A22;">
                            <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                            <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>