<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth_functions.php';

$userId = getCurrentUserId();
$stmtOffSid = $pdo->prepare("
    SELECT o.Position, org.OrgID, org.OrgName, s.FullName, s.StudentID
    FROM OFFICER o
    JOIN ORGANIZATION org ON o.OrgID = org.OrgID
    JOIN STUDENT s ON o.UserID = s.UserID
    WHERE o.UserID = ?
");
$stmtOffSid->execute([$userId]);
$officerData = $stmtOffSid->fetch();
$orgId = $officerData['OrgID'] ?? '';

$stmtBadge = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Pending'");
$stmtBadge->execute([$orgId]);
$pendingRequests = $stmtBadge->fetchColumn();

$activePage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="user-info">
        <div class="avatar"><?= strtoupper(substr($officerData['FullName'] ?? 'O', 0, 1)) ?></div>
        <h3><?= htmlspecialchars($officerData['FullName'] ?? 'Officer') ?></h3>
        <p class="student-id"><?= htmlspecialchars($officerData['StudentID'] ?? '') ?></p>
        <p class="student-id"><?= htmlspecialchars($officerData['Position'] ?? '') ?></p>
        <p class="student-id" style="font-weight: bold; margin-top: 5px; color: #6B1A22;"><?= htmlspecialchars($officerData['OrgName'] ?? '') ?></p>
    </div>
    <nav class="side-nav">
        <p class="nav-label">Navigation</p>
        <a href="dashboard.php" class="<?= $activePage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="manage_members.php" class="<?= $activePage === 'manage_members.php' ? 'active' : '' ?>">
            Member Approvals 
            <?php if($pendingRequests > 0): ?>
                <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem; float: right;"><?= $pendingRequests ?></span>
            <?php endif; ?>
        </a>
        <a href="manage_events.php" class="<?= $activePage === 'manage_events.php' ? 'active' : '' ?>">Manage Events</a>
        <a href="attendance_scanner.php" class="<?= $activePage === 'attendance_scanner.php' ? 'active' : '' ?>">Attendance Approvals</a>
        <a href="profile.php" class="<?= $activePage === 'profile.php' ? 'active' : '' ?>">Profile</a>
    </nav>
</aside>