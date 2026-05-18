<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Admin');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'create') {
            $orgId = $_POST['org_id'];
            $name = $_POST['org_name'];
            $cat = $_POST['category'];
            $date = $_POST['date_established'];

            $stmtCheck = $pdo->prepare("SELECT OrgID FROM ORGANIZATION WHERE OrgID = ?");
            $stmtCheck->execute([$orgId]);
            
            if ($stmtCheck->rowCount() > 0) {
                $error = "That Org ID already exists. Please use a unique ID (e.g., ORG-03).";
            } else {
                $stmt = $pdo->prepare("INSERT INTO ORGANIZATION (OrgID, OrgName, Category, DateEstablished) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orgId, $name, $cat, $date]);
                $success = "Organization created successfully!";
            }
        }

        // --- NEW: Update Logic ---
        if ($action === 'update') {
            $orgId = $_POST['org_id'];
            $name = $_POST['org_name'];
            $cat = $_POST['category'];
            $date = $_POST['date_established'];

            $stmt = $pdo->prepare("UPDATE ORGANIZATION SET OrgName = ?, Category = ?, DateEstablished = ? WHERE OrgID = ?");
            $stmt->execute([$name, $cat, $date, $orgId]);
            $success = "Organization updated successfully!";
        }
        // -------------------------

        if ($action === 'delete') {
            $orgId = $_POST['org_id'];
            
            try {
                $pdo->beginTransaction();
                
                // Demote all officers of this organization back to 'Student'
                $stmtDemote = $pdo->prepare("
                    UPDATE USER 
                    SET UserType = 'Student' 
                    WHERE UserID IN (SELECT UserID FROM OFFICER WHERE OrgID = ?)
                ");
                $stmtDemote->execute([$orgId]);
                
                // Delete the organization
                $stmtDelete = $pdo->prepare("DELETE FROM ORGANIZATION WHERE OrgID = ?");
                $stmtDelete->execute([$orgId]);
                
                $pdo->commit();
                $success = "Organization forcefully deleted. Associated officers have been reverted to students.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Failed to delete organization: " . $e->getMessage();
            }
        }
    }

    $stmtOrgs = $pdo->query("SELECT * FROM ORGANIZATION ORDER BY OrgName ASC");
    $organizations = $stmtOrgs->fetchAll();

    // --- NEW: Check if we are in Edit Mode ---
    $editOrg = null;
    if (isset($_GET['edit'])) {
        $stmtEdit = $pdo->prepare("SELECT * FROM ORGANIZATION WHERE OrgID = ?");
        $stmtEdit->execute([$_GET['edit']]);
        $editOrg = $stmtEdit->fetch();
    }
    // -----------------------------------------

} catch (PDOException $e) {
    $error = "System Error: " . $e->getMessage();
}
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar">A</div>
            <h3>Administrator</h3>
            <p class="student-id">System Admin</p>
        </div>
        <nav class="side-nav">
            <p class="nav-label">Navigation</p>
            <a href="manage_orgs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_orgs.php' ? 'active' : '' ?>">Manage Organizations</a>
            <a href="manage_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">Assign Officers</a>
            <a href="manage_students.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : '' ?>">Manage Students</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="card">
            <h3><?= $editOrg ? 'Edit Organization' : 'Register New Organization' ?></h3>
            
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="manage_orgs.php">
                <input type="hidden" name="action" value="<?= $editOrg ? 'update' : 'create' ?>">
                
                <div class="form-group">
                    <label>Org ID:</label>
                    <input type="text" name="org_id" value="<?= htmlspecialchars($editOrg['OrgID'] ?? '') ?>" <?= $editOrg ? 'readonly style="background-color: #eee; cursor: not-allowed;"' : 'placeholder="e.g., ORG-01" required' ?>>
                </div>
                <div class="form-group">
                    <label>Org Name:</label>
                    <input type="text" name="org_name" value="<?= htmlspecialchars($editOrg['OrgName'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Category:</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($editOrg['Category'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Date Established:</label>
                    <input type="date" name="date_established" value="<?= htmlspecialchars($editOrg['DateEstablished'] ?? '') ?>" required>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary"><?= $editOrg ? 'Save Changes' : 'Add Organization' ?></button>
                    <?php if ($editOrg): ?>
                        <a href="manage_orgs.php" class="btn-secondary" style="margin-top: 1rem; text-align: center; width: 100%; box-sizing: border-box;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <h3>Current Organizations</h3>
            <?php if (empty($organizations)): ?>
                <p class="empty-state">No organizations registered yet.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($organizations as $org): ?>
                        <li>
                            <div class="event-details">
                                <span class="dot"></span>
                                <div>
                                    <span class="title" style="display:block;"><?= htmlspecialchars($org['OrgName']) ?></span>
                                    <span class="date"><?= htmlspecialchars($org['OrgID']) ?> | <?= htmlspecialchars($org['Category']) ?></span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="?edit=<?= htmlspecialchars($org['OrgID']) ?>" style="background: transparent; color: #3498db; border: 1px solid #3498db; padding: 5px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">Edit</a>
                                
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['OrgID']) ?>">
                                    <button type="submit" style="background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Delete</button>
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