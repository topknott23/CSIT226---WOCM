<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    // Fetch detailed officer profile from both OFFICER and STUDENT tables
    $stmtOfficer = $pdo->prepare("
        SELECT o.Position, org.OrgID, org.OrgName, s.FullName, s.StudentID
        FROM OFFICER o
        JOIN ORGANIZATION org ON o.OrgID = org.OrgID
        JOIN STUDENT s ON o.UserID = s.UserID
        WHERE o.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();

    if (!$officerData) {
        die("Access Denied: Officer data not found or not linked to an organization.");
    }
    $orgId = $officerData['OrgID'];

    // Sidebar badge counts
    $stmtPendingCount = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Pending'");
    $stmtPendingCount->execute([$orgId]);
    $pendingRequests = $stmtPendingCount->fetchColumn();

    // Stats calculations
    $stmtMembers = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as active_members
        FROM MEMBERSHIP 
        WHERE OrgID = ?
    ");
    $stmtMembers->execute([$orgId]);
    $memberStats = $stmtMembers->fetch();
    
    $activeMembers = $memberStats['active_members'] ?? 0;

    $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $stmtEvents->execute([$orgId]);
    $orgEvents = $stmtEvents->fetchAll();

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
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="manage_members.php">
                Member Approvals 
                <?php if($pendingRequests > 0): ?>
                    <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem; float: right;"><?= $pendingRequests ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_events.php">Manage Events</a>
            <a href="attendance_scanner.php">Attendance Approvals</a>
            <a href="profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-stats">
            <div class="stat-box">
                <span class="stat-label">Active Members:</span>
                <span class="stat-value"><?= $activeMembers ?></span>
            </div>
            <div class="stat-box" style="border-left: 5px solid #e67e22;">
                <span class="stat-label">Pending Approvals:</span>
                <span class="stat-value" style="color: #e67e22;"><?= $pendingRequests ?></span>
            </div>
            <div class="stat-box" style="border-left: 5px solid #3498db;">
                <span class="stat-label">Total Events:</span>
                <span class="stat-value" style="color: #3498db;"><?= count($orgEvents) ?></span>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #F8F5F2; padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; border: none; padding: 0;">Organization Events</h3>
            </div>
            
            <?php if (empty($orgEvents)): ?>
                <p class="empty-state">No events scheduled yet.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($orgEvents as $event): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($event['EventTitle']) ?></span>
                                    <span class="date"><?= htmlspecialchars($event['Venue']) ?></span>
                                </div>
                            </div>
                            <span class="date"><?= date('F j, Y', strtotime($event['Date'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>