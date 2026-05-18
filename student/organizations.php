<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student', 'Officer');

$userId = getCurrentUserId();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'leave') {
        $orgIdToLeave = $_POST['org_id'];
        try {
            $stmtLeave = $pdo->prepare("DELETE FROM MEMBERSHIP WHERE StudentUserID = ? AND OrgID = ?");
            $stmtLeave->execute([$userId, $orgIdToLeave]);
            $success = "You have successfully left the organization.";
        } catch (PDOException $e) {
            $error = "Error leaving organization: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'join' && isset($_POST['org_id'])) {
        $orgId = $_POST['org_id'];
        $membershipId = generateUuid4();
        try {
            $stmtJoin = $pdo->prepare("INSERT INTO MEMBERSHIP (MembershipID, StudentUserID, OrgID, Status) VALUES (?, ?, ?, 'Pending')");
            $stmtJoin->execute([$membershipId, $userId, $orgId]);
            $success = "Successfully requested to join. Waiting for officer approval.";
        } catch (PDOException $e) {
            $error = "Error joining organization. You might already have a pending or active membership.";
        }
    }
}

try {
    // 1. Approved Organizations
    $stmtMyOrgs = $pdo->prepare("
        SELECT o.* FROM ORGANIZATION o
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Approved'
        ORDER BY o.OrgName ASC
    ");
    $stmtMyOrgs->execute([$userId]);
    $myOrgs = $stmtMyOrgs->fetchAll();

    // 2. Pending Approvals 
    $stmtPendingOrgs = $pdo->prepare("
        SELECT o.* FROM ORGANIZATION o
        JOIN MEMBERSHIP m ON o.OrgID = m.OrgID
        WHERE m.StudentUserID = ? AND m.Status = 'Pending'
        ORDER BY o.OrgName ASC
    ");
    $stmtPendingOrgs->execute([$userId]);
    $pendingOrgs = $stmtPendingOrgs->fetchAll();

    // 3. Available Organizations to Discover
    $stmtAvailableOrgs = $pdo->prepare("
        SELECT o.* FROM ORGANIZATION o
        WHERE o.OrgID NOT IN (SELECT OrgID FROM MEMBERSHIP WHERE StudentUserID = ?)
        ORDER BY o.OrgName ASC
    ");
    $stmtAvailableOrgs->execute([$userId]);
    $availableOrgs = $stmtAvailableOrgs->fetchAll();
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>My Organizations</h3>
            <?php if (empty($myOrgs)): ?>
                <p class="empty-state">You are not an active member of any organizations yet.</p>
            <?php else: ?>
                <div class="guest-grid">
                    <?php foreach ($myOrgs as $org): ?>
                        <div class="guest-card" style="border-top-color: #6B1A22;">
                            <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                            <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?></p>
                            <form method="POST" style="margin-top: 1.5rem;">
                                <input type="hidden" name="action" value="leave">
                                <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['OrgID']) ?>">
                                <button type="submit" style="width: 100%; padding: 0.8rem; background-color: white; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 6px; font-weight: bold; cursor: pointer;">
                                    Leave Organization
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($pendingOrgs)): ?>
            <div class="card" style="margin-bottom: 2rem;">
                <h3>Pending Join Requests</h3>
                <div class="guest-grid">
                    <?php foreach ($pendingOrgs as $org): ?>
                        <div class="guest-card" style="border-top-color: #F1C40F;">
                            <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                            <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?></p>
                            <div style="margin-top: 1rem; text-align: center; background: #FEF9E7; color: #B7950B; padding: 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                Awaiting Officer Verification
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Discover Organizations</h3>
            <?php if (empty($availableOrgs)): ?>
                <p class="empty-state">No new organizations available to join right now.</p>
            <?php else: ?>
                <div class="guest-grid">
                    <?php foreach ($availableOrgs as $org): ?>
                        <div class="guest-card">
                            <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                            <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?></p>
                            <form method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="action" value="join">
                                <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['OrgID']) ?>">
                                <button type="submit" class="btn-primary" style="margin-top: 0;">Request to Join</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>