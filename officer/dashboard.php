<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    // Shared global query to extract the org scope context
    $stmtScope = $pdo->prepare("SELECT OrgID FROM OFFICER WHERE UserID = ?");
    $stmtScope->execute([$userId]);
    $orgId = $stmtScope->fetchColumn();

    if (!$orgId) {
        die("Access Denied: Officer data missing.");
    }

    // 1. Metric Counts
    $stmtMembers = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Approved'");
    $stmtMembers->execute([$orgId]);
    $activeMembers = $stmtMembers->fetchColumn();

    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM MEMBERSHIP WHERE OrgID = ? AND Status = 'Pending'");
    $stmtPending->execute([$orgId]);
    $pendingCount = $stmtPending->fetchColumn();

    // 2. Events List Collection
    $stmtEvents = $pdo->prepare("SELECT * FROM EVENT WHERE OrgID = ? ORDER BY Date ASC");
    $stmtEvents->execute([$orgId]);
    $orgEvents = $stmtEvents->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_officer.php'; ?>

    <main class="main-content">
        <div class="top-stats">
            <div class="stat-box">
                <span class="stat-label">Active Members:</span>
                <span class="stat-value"><?= $activeMembers ?></span>
            </div>
            <div class="stat-box" style="border-left: 5px solid #e67e22;">
                <span class="stat-label">Pending Approvals:</span>
                <span class="stat-value" style="color: #e67e22;"><?= $pendingCount ?></span>
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