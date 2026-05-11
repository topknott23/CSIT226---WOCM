<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    $stmtOfficer = $pdo->prepare("
        SELECT o.Position, org.OrgID, org.OrgName 
        FROM OFFICER o
        JOIN ORGANIZATION org ON o.OrgID = org.OrgID
        WHERE o.UserID = ?
    ");
    $stmtOfficer->execute([$userId]);
    $officerData = $stmtOfficer->fetch();

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

    $pendingMembers = [];
    $approvedMembers = [];
    $pendingRequests = 0;

    if ($orgId) {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
            $membershipId = $_POST['membership_id'];
            
            if ($_POST['action'] === 'approve') {
                $stmtUpdate = $pdo->prepare("UPDATE MEMBERSHIP SET Status = 'Approved' WHERE MembershipID = ? AND OrgID = ?");
                $stmtUpdate->execute([$membershipId, $orgId]);
                $success = "Member approved successfully.";
            } elseif ($_POST['action'] === 'reject' || $_POST['action'] === 'remove') {
                $stmtDelete = $pdo->prepare("DELETE FROM MEMBERSHIP WHERE MembershipID = ? AND OrgID = ?");
                $stmtDelete->execute([$membershipId, $orgId]);
                $success = "Member removed successfully.";
            }
        }

        $stmtPending = $pdo->prepare("
            SELECT m.MembershipID, s.FullName, s.StudentID, s.Course, s.YearLevel
            FROM MEMBERSHIP m
            JOIN STUDENT s ON m.StudentUserID = s.UserID
            WHERE m.OrgID = ? AND m.Status = 'Pending'
        ");
        $stmtPending->execute([$orgId]);
        $pendingMembers = $stmtPending->fetchAll();
        $pendingRequests = count($pendingMembers);

        $stmtApproved = $pdo->prepare("
            SELECT m.MembershipID, s.FullName, s.StudentID, s.Course
            FROM MEMBERSHIP m
            JOIN STUDENT s ON m.StudentUserID = s.UserID
            WHERE m.OrgID = ? AND m.Status = 'Approved'
        ");
        $stmtApproved->execute([$orgId]);
        $approvedMembers = $stmtApproved->fetchAll();
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
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_members.php" class="active">
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
        <div class="card">
            <h3>Member Approvals</h3>
            <?php if (!$orgId): ?>
                <p class="empty-state">Please link your account to an organization to manage members.</p>
            <?php elseif (empty($pendingMembers)): ?>
                <p class="empty-state">No pending membership requests at this time.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($pendingMembers as $member): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: #F1C40F;"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($member['FullName']) ?></span>
                                    <span class="date"><?= htmlspecialchars($member['StudentID']) ?> | <?= htmlspecialchars($member['Course']) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <form method="POST">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="membership_id" value="<?= $member['MembershipID'] ?>">
                                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; width: auto; margin: 0; background-color: #2ecc71;">Approve</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="membership_id" value="<?= $member['MembershipID'] ?>">
                                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; width: auto; margin: 0; background-color: #e74c3c;">Reject</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>