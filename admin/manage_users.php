<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Admin');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign_officer') {
        $userIdToPromote = $_POST['user_id'];
        $orgId = $_POST['org_id'];
        $position = $_POST['position'];

        try {
            // NEW VALIDATION LOGIC: Check if the position is already taken within this specific organization
            $stmtCheckPos = $pdo->prepare("SELECT COUNT(*) FROM OFFICER WHERE OrgID = ? AND Position = ?");
            $stmtCheckPos->execute([$orgId, $position]);
            
            if ($stmtCheckPos->fetchColumn() > 0) {
                $error = "The position of '$position' is already taken in this organization.";
            } else {
                // Proceed with promotion if position is available
                $pdo->beginTransaction();

                $stmtUpdateRole = $pdo->prepare("UPDATE USER SET UserType = 'Officer' WHERE UserID = ?");
                $stmtUpdateRole->execute([$userIdToPromote]);

                $stmtInsertOfficer = $pdo->prepare("INSERT INTO OFFICER (UserID, OrgID, Position) VALUES (?, ?, ?)");
                $stmtInsertOfficer->execute([$userIdToPromote, $orgId, $position]);

                // --- THE FIX: Automatically give them an Approved Membership ---
                $newMemId = generateUuid4();
                $stmtCheckMem = $pdo->prepare("SELECT MembershipID FROM MEMBERSHIP WHERE StudentUserID = ? AND OrgID = ?");
                $stmtCheckMem->execute([$userIdToPromote, $orgId]);
                
                if ($stmtCheckMem->rowCount() == 0) {
                    $stmtInsertMem = $pdo->prepare("INSERT INTO MEMBERSHIP (MembershipID, StudentUserID, OrgID, Status) VALUES (?, ?, ?, 'Approved')");
                    $stmtInsertMem->execute([$newMemId, $userIdToPromote, $orgId]);
                } else {
                    $stmtUpdateMem = $pdo->prepare("UPDATE MEMBERSHIP SET Status = 'Approved' WHERE StudentUserID = ? AND OrgID = ?");
                    $stmtUpdateMem->execute([$userIdToPromote, $orgId]);
                }
                // ---------------------------------------------------------------

                $pdo->commit();
                $success = "User successfully promoted and automatically enrolled as a member!";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() == 23000) {
                $error = "This user is already an officer for an organization.";
            } else {
                $error = "System Error: " . $e->getMessage();
            }
        }
    }
    
    if ($_POST['action'] === 'remove_officer') {
        $userIdToRemove = $_POST['user_id'];
        $orgIdToRemove = $_POST['org_id'];
        
        try {
            $pdo->beginTransaction();
            
            $stmtDeleteOfficer = $pdo->prepare("DELETE FROM OFFICER WHERE UserID = ? AND OrgID = ?");
            $stmtDeleteOfficer->execute([$userIdToRemove, $orgIdToRemove]);
            
            $stmtRevertRole = $pdo->prepare("UPDATE USER SET UserType = 'Student' WHERE UserID = ?");
            $stmtRevertRole->execute([$userIdToRemove]);
            
            $pdo->commit();
            $success = "Officer removed and reverted to Student status.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error removing officer: " . $e->getMessage();
        }
    }
}

$stmtUsers = $pdo->query("
    SELECT u.UserID, u.Email, s.FullName 
    FROM USER u
    LEFT JOIN STUDENT s ON u.UserID = s.UserID
    WHERE u.UserType != 'Admin' AND u.UserID NOT IN (SELECT UserID FROM OFFICER)
    ORDER BY s.FullName ASC
");
$availableUsers = $stmtUsers->fetchAll();

$stmtOrgs = $pdo->query("SELECT OrgID, OrgName FROM ORGANIZATION ORDER BY OrgName ASC");
$orgs = $stmtOrgs->fetchAll();

$stmtOfficers = $pdo->query("
    SELECT o.UserID, s.FullName, o.Position, org.OrgID, org.OrgName, u.Email
    FROM OFFICER o
    JOIN STUDENT s ON o.UserID = s.UserID
    JOIN ORGANIZATION org ON o.OrgID = org.OrgID
    JOIN USER u ON o.UserID = u.UserID
    ORDER BY org.OrgName ASC
");
$officers = $stmtOfficers->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="card" style="margin-bottom: 2rem;">
            <h3>Assign Organization Leader</h3>
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($availableUsers) || empty($orgs)): ?>
                <p class="empty-state">You need both registered students and created organizations to assign an officer.</p>
            <?php else: ?>
                <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; align-items: end;">
                    <input type="hidden" name="action" value="assign_officer">
                    
                    <div class="form-group" style="margin: 0;">
                        <label>Select Student:</label>
                        <select name="user_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?= htmlspecialchars($user['UserID']) ?>">
                                    <?= htmlspecialchars($user['FullName'] ?? $user['Email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label>Select Organization:</label>
                        <select name="org_id" required>
                            <option value="">-- Choose Org --</option>
                            <?php foreach ($orgs as $org): ?>
                                <option value="<?= htmlspecialchars($org['OrgID']) ?>">
                                    <?= htmlspecialchars($org['OrgName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label>Position:</label>
                        <select name="position" required>
                            <option value="">-- Choose Position --</option>
                            <option value="President">President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Auditor">Auditor</option>
                            <option value="P.R.O.">P.R.O.</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary" style="grid-column: span 3;">Connect Officer to Org</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Current Officers</h3>
            <?php if (empty($officers)): ?>
                <p class="empty-state">No officers have been assigned yet.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($officers as $off): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot" style="background-color: #3498db;"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($off['FullName']) ?> (<?= htmlspecialchars($off['Position']) ?>)</span>
                                    <span class="date"><?= htmlspecialchars($off['OrgName']) ?> | <?= htmlspecialchars($off['Email']) ?></span>
                                </div>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="remove_officer">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($off['UserID']) ?>">
                                <input type="hidden" name="org_id" value="<?= htmlspecialchars($off['OrgID']) ?>">
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