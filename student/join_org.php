<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Student');
$userId = getCurrentUserId();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['org_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO MEMBERSHIP (MembershipID, StudentUserID, OrgID, Status) VALUES (UUID(), ?, ?, 'Pending')");
        $stmt->execute([$userId, $_POST['org_id']]);
        $success = "Request sent! Waiting for approval.";
    } catch (PDOException $e) { $error = "You already have a request for this organization."; }
}

try {
    $student = $pdo->prepare("SELECT * FROM STUDENT WHERE UserID = ?");
    $student->execute([$userId]);
    $studentData = $student->fetch();

    // Show only orgs the student hasn't joined or requested yet
    $stmt = $pdo->prepare("SELECT * FROM ORGANIZATION WHERE OrgID NOT IN (SELECT OrgID FROM MEMBERSHIP WHERE StudentUserID = ?)");
    $stmt->execute([$userId]);
    $availableOrgs = $stmt->fetchAll();
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

include '../includes/header.php'; 
?>
<div class="dashboard-layout">
    <aside class="sidebar">
        </aside>
    <main class="main-content">
        <div class="card">
            <h3>Join an Organization</h3>
            <?php if (isset($success)): ?><div class="success-message" style="color:green; margin-bottom:10px;"><?= $success ?></div><?php endif; ?>
            <div class="guest-grid">
                <?php foreach ($availableOrgs as $org): ?>
                    <div class="guest-card">
                        <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                        <form method="POST">
                            <input type="hidden" name="org_id" value="<?= $org['OrgID'] ?>">
                            <button type="submit" class="btn-primary">Request to Join</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>