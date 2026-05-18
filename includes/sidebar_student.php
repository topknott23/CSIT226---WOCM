<?php
// wocm/includes/sidebar_student.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth_functions.php';

$userId = getCurrentUserId();
try {
    $stmtSid = $pdo->prepare("SELECT FullName, StudentID FROM STUDENT WHERE UserID = ?");
    $stmtSid->execute([$userId]);
    $student = $stmtSid->fetch();
} catch (PDOException $e) {
    error_log("Sidebar Student Error: " . $e->getMessage()); // Problem 6 Fix
    $student = [];
}

$activePage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="user-info">
        <div class="avatar"><?= strtoupper(substr($student['FullName'] ?? 'U', 0, 1)) ?></div>
        <h3><?= htmlspecialchars($student['FullName'] ?? 'User') ?></h3>
        <p class="student-id"><?= htmlspecialchars($student['StudentID'] ?? '') ?></p>
    </div>
    <nav class="side-nav">
        <p class="nav-label">Navigation</p>
        <a href="dashboard.php" class="<?= $activePage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="organizations.php" class="<?= $activePage === 'organizations.php' ? 'active' : '' ?>">Organizations</a>
        <a href="events.php" class="<?= $activePage === 'events.php' ? 'active' : '' ?>">Events</a>
        <a href="attendance.php" class="<?= $activePage === 'attendance.php' ? 'active' : '' ?>">Attendance</a>
        <a href="profile.php" class="<?= $activePage === 'profile.php' ? 'active' : '' ?>">Profile</a>
        
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Officer'): ?>
            <div style="padding: 1rem 2rem 0 2rem; border-top: 1px solid #f0f0f0; margin-top: 1rem;">
                <a href="/wocm/officer/dashboard.php" style="padding: 0.5rem 0; color: #3498db; font-weight: bold; border-left: none; display: flex; align-items: center; gap: 8px; background: transparent;">
                    <span>💼</span> Switch to Officer Panel
                </a>
            </div>
        <?php endif; ?>
    </nav>
</aside>