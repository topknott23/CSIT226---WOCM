<?php
session_start();
require_once '../includes/db_connect.php';

// strictly enforce Officer access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Officer') {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // 1. Get Officer and Organization details
    $stmtOfficer = $pdo->prepare("
        SELECT o.Position, org.OrgID, org.OrgName 
        FROM OFFICER o
        JOIN ORGANIZATION org ON o.OrgID = org.OrgID
        WHERE o.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();
    
    $orgId = $officerData['OrgID'];

    // 2. Get Member Statistics (Approved vs Pending)
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

    // 3. Get Organization Events
    $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $stmtEvents->execute([$orgId]);
    $orgEvents = $stmtEvents->fetchAll();

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <h3>Officer View</h3>
            <p class="student-id"><?= htmlspecialchars($officerData['Position']) ?></p>
            <p class="student-id" style="font-weight: bold; margin-top: 5px;">
                <?= htmlspecialchars($officerData['OrgName']) ?>
            </p>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="manage_members.php">
                Member Approvals 
                <?php if($pendingRequests > 0): ?>
                    <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem; float: right;">
                        <?= $pendingRequests ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="manage_events.php">Manage Events</a>
            <a href="attendance_scanner.php">Attendance</a>
        </nav>
        <div class="logout-container">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </aside>

    <section class="main-content">
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Active Members</h4>
                <span class="stat-value"><?= $activeMembers ?></span>
            </div>
            <div class="stat-card" style="border-left-color: #e67e22;">
                <h4>Pending Approvals</h4>
                <span class="stat-value"><?= $pendingRequests ?></span>
            </div>
            <div class="stat-card" style="border-left-color: #3498db;">