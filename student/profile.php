<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');

$userId = getCurrentUserId();

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.Email 
        FROM STUDENT s
        JOIN USER u ON s.UserID = u.UserID
        WHERE s.UserID = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($profile['FullName'], 0, 1)) ?></div>
            <h3><?= htmlspecialchars($profile['FullName']) ?></h3>
            <p class="student-id"><?= htmlspecialchars($profile['StudentID']) ?></p>
        </div>
        <nav class="side-nav">
            <p class="nav-label">Navigation</p>
            <a href="dashboard.php">Dashboard</a>
            <a href="my_orgs.php">My Organization</a>
            <a href="join_org.php">Join Organization</a>
            <a href="events.php">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php" class="active">Profile</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h3 style="text-align: center; border-bottom: 2px solid #F8F5F2; padding-bottom: 1rem;">Student Profile</h3>
            <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                    <span style="color: #666; font-weight: 500;">Full Name</span>
                    <span style="font-weight: bold; color: #333;"><?= htmlspecialchars($profile['FullName']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                    <span style="color: #666; font-weight: 500;">Student ID</span>
                    <span style="font-weight: bold; color: #333;"><?= htmlspecialchars($profile['StudentID']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                    <span style="color: #666; font-weight: 500;">Email Address</span>
                    <span style="font-weight: bold; color: #333;"><?= htmlspecialchars($profile['Email']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                    <span style="color: #666; font-weight: 500;">Course</span>
                    <span style="font-weight: bold; color: #333;"><?= htmlspecialchars($profile['Course']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">
                    <span style="color: #666; font-weight: 500;">Year Level</span>
                    <span style="font-weight: bold; color: #333;"><?= htmlspecialchars($profile['YearLevel']) ?></span>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>