<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';

requireRole('Student');

$userId = getCurrentUserId();

function generateUuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['org_id'])) {
    $orgId = $_POST['org_id'];
    $membershipId = generateUuid4();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO MEMBERSHIP (MembershipID, StudentUserID, OrgID, Status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$membershipId, $userId, $orgId]);
        $success = "Successfully requested to join. Waiting for officer approval.";
    } catch (PDOException $e) {
        $error = "Error joining organization. You might already have a pending or active membership.";
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT o.* FROM ORGANIZATION o
        WHERE o.OrgID NOT IN (
            SELECT OrgID FROM MEMBERSHIP WHERE StudentUserID = ?
        )
    ");
    $stmt->execute([$userId]);
    $availableOrgs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching organizations: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <nav class="side-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="my_orgs.php">My Organizations</a>
            <a href="join_org.php" class="active">Join Organization</a>
            <a href="events.php">Events</a>
            <a href="attendance.php">Attendance</a>
            <a href="profile.php">Profile</a>
        </nav>
        <div class="logout-container">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </aside>

    <section class="main-content">
        <h3>Join an Organization</h3>
        
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="guest-grid">
            <?php if (empty($availableOrgs)): ?>
                <p>No new organizations available to join.</p>
            <?php else: ?>
                <?php foreach ($availableOrgs as $org): ?>
                    <div class="guest-card">
                        <h4><?= htmlspecialchars($org['OrgName']) ?></h4>
                        <p class="meta-text">Category: <?= htmlspecialchars($org['Category']) ?></p>
                        <form method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['OrgID']) ?>">
                            <button type="submit" class="btn-primary" style="margin-top: 0;">Request to Join</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>