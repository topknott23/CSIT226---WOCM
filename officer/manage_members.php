<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
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
        die("Access Denied: Officer data missing.");
    }
    $orgId = $officerData['OrgID'];

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $membershipId = $_POST['membership_id'];
        
        if ($_POST['action'] === 'approve') {
            $stmtUpdate = $pdo->prepare("UPDATE MEMBERSHIP SET Status = 'Approved' WHERE MembershipID = ? AND OrgID = ?");
            $stmtUpdate->execute([$membershipId, $orgId]);
            $success = "Member approved successfully.";
        } elseif ($_POST['action'] === 'reject' || $_POST['action'] === 'remove') {
            $stmtDelete = $pdo->prepare("DELETE FROM MEMBERSHIP WHERE MembershipID = ? AND OrgID = ?");
            $stmtDelete->execute([$membershipId, $orgId]);
            $success = "Member request processed successfully.";
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
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_members.php" class="active">
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
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>Pending Member Approvals</h3>
            <?php if (empty($pendingMembers)): ?>
                <p class="empty-state">No pending membership requests at this time.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($pendingMembers as $member): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: #F1C40F;"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($member['FullName']) ?></span>
                                    <span class="date"><?= htmlspecialchars($member['StudentID']) ?> | <?= htmlspecialchars($member['Course']) ?>-<?= htmlspecialchars($member['YearLevel']) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="membership_id" value="<?= $member['MembershipID'] ?>">
                                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; width: auto; margin: 0; background-color: #2ecc71;">Approve</button>
                                </form>
                                <form method="POST" style="margin: 0;">
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

        <div class="card">
            <h3>Approved Members Master List</h3>
            <?php if (empty($approvedMembers)): ?>
                <p class="empty-state">No approved organization members found.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($approvedMembers as $member): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: #2ecc71;"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($member['FullName']) ?></span>
                                    <span class="date">ID: <?= htmlspecialchars($member['StudentID']) ?> | Course: <?= htmlspecialchars($member['Course']) ?></span>
                                </div>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="membership_id" value="<?= $member['MembershipID'] ?>">
                                <button type="submit" style="background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>