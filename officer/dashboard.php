<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    // 1. Fetch Officer Data safely
    $stmtOfficer = $pdo->prepare("
        SELECT o.Position, org.OrgID, org.OrgName 
        FROM OFFICER o
        JOIN ORGANIZATION org ON o.OrgID = org.OrgID
        WHERE o.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();

    // THE FIX: Provide a fallback if the officer hasn't been assigned an org yet
    if (!$officerData) {
        $officerData = [
            'Position' => 'Unassigned',
            'OrgID' => null,
            'OrgName' => 'No Organization Linked'
        ];
        $orgId = null;
    } else {
        $orgId = $officerData['OrgID'];
    }

    // Initialize default values to prevent undefined variable errors
    $activeMembers = 0;
    $pendingRequests = 0;
    $orgEvents = [];

    // Only run these queries if an OrgID actually exists
    if ($orgId) {
        $stmtMembers = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as pending_requests
            FROM MEMBERSHIP 
            WHERE OrgID = ?
        ");
        $stmtMembers->execute([$orgId]);
        $memberStats = $stmtMembers->fetch();
        
        $activeMembers = $memberStats['active_members'] ?? 0;
        $pendingRequests = $memberStats['pending_requests'] ?? 0;

        $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
        $stmtEvents->execute([$orgId]);
        $orgEvents = $stmtEvents->fetchAll();
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($officerData['Position'], 0, 1)) ?></div>
            <h3>Officer View</h3>
            <p class="student-id"><?= htmlspecialchars($officerData['Position']) ?></p>
            <p class="student-id" style="font-weight: bold; margin-top: 5px;"><?= htmlspecialchars($officerData['OrgName']) ?></p>
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
            <a href="attendance_scanner.php">Attendance</a>
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
                <a href="manage_events.php" class="btn-primary" style="width: auto; margin: 0; padding: 0.5rem 1.5rem; font-size: 0.85rem;">+ Manage Events</a>
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