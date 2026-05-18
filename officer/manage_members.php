<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Officer');

$userId = getCurrentUserId();

try {
    $stmtScope = $pdo->prepare("SELECT OrgID FROM OFFICER WHERE UserID = ?");
    $stmtScope->execute([$userId]);
    $orgId = $stmtScope->fetchColumn();

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $membershipId = $_POST['membership_id'];
        
        if ($_POST['action'] === 'approve') {
            $stmtUpdate = $pdo->prepare("UPDATE MEMBERSHIP SET Status = 'Approved' WHERE MembershipID = ? AND OrgID = ?");
            $stmtUpdate->execute([$membershipId, $orgId]);
            $success = "Member approved successfully.";
        } elseif ($_POST['action'] === 'reject' || $_POST['action'] === 'remove') {
            try {
                $stmtCheckSelf = $pdo->prepare("SELECT StudentUserID FROM MEMBERSHIP WHERE MembershipID = ?");
                $stmtCheckSelf->execute([$membershipId]);
                $targetTargetUser = $stmtCheckSelf->fetchColumn();

                if ($targetTargetUser === $userId) {
                    $error = "Action denied: You cannot remove yourself from the organization membership list while serving as an active officer. An Administrator must reassign your role first.";
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM MEMBERSHIP WHERE MembershipID = ? AND OrgID = ?");
                    $stmtDelete->execute([$membershipId, $orgId]);
                    $success = "Member request processed successfully.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    $stmtPending = $pdo->prepare("
        SELECT m.MembershipID, s.FullName, s.StudentID, s.Course, s.YearLevel
        FROM MEMBERSHIP m JOIN STUDENT s ON m.StudentUserID = s.UserID
        WHERE m.OrgID = ? AND m.Status = 'Pending'
    ");
    $stmtPending->execute([$orgId]);
    $pendingMembers = $stmtPending->fetchAll();

    $stmtApproved = $pdo->prepare("
        SELECT m.MembershipID, s.FullName, s.StudentID, s.Course
        FROM MEMBERSHIP m JOIN STUDENT s ON m.StudentUserID = s.UserID
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
    <?php include '../includes/sidebar_officer.php'; ?>

    <main class="main-content">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
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
                                    <input type="hidden" name="membership_id" value="<?= htmlspecialchars($member['MembershipID']) ?>">
                                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; width: auto; margin: 0; background-color: #2ecc71;">Approve</button>
                                </form>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="membership_id" value="<?= htmlspecialchars($member['MembershipID']) ?>">
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
                                <input type="hidden" name="membership_id" value="<?= htmlspecialchars($member['MembershipID']) ?>">
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