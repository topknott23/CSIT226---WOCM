<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth_functions.php';

$userId = getCurrentUserId();
$stmtSid = $pdo->prepare("SELECT FullName, StudentID FROM STUDENT WHERE UserID = ?");
$stmtSid->execute([$userId]);
$student = $stmtSid->fetch();

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
    </nav>
</aside>