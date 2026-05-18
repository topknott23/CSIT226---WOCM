<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Admin');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'create') {
            $name = $_POST['org_name'];
            $cat = $_POST['category'];
            $date = $_POST['date_established'];

            // Auto-increment OrgID manually matching your VARCHAR setup
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM ORGANIZATION");
            $nextNum = $stmtCount->fetchColumn() + 1;
            $orgId = 'ORG-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT);
            
            // Loop until absolute uniqueness to prevent gaps or duplicate overrides
            $stmtCheck = $pdo->prepare("SELECT OrgID FROM ORGANIZATION WHERE OrgID = ?");
            while (true) {
                $stmtCheck->execute([$orgId]);
                if ($stmtCheck->rowCount() == 0) break;
                $nextNum++;
                $orgId = 'ORG-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT);
            }

            $stmt = $pdo->prepare("INSERT INTO ORGANIZATION (OrgID, OrgName, Category, DateEstablished) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orgId, $name, $cat, $date]);
            $success = "Organization created successfully with auto-generated ID: " . $orgId;
        }

        if ($action === 'update') {
            $orgId = $_POST['org_id'];
            $name = $_POST['org_name'];
            $cat = $_POST['category'];
            $date = $_POST['date_established'];

            $stmt = $pdo->prepare("UPDATE ORGANIZATION SET OrgName = ?, Category = ?, DateEstablished = ? WHERE OrgID = ?");
            $stmt->execute([$name, $cat, $date, $orgId]);
            $success = "Organization updated successfully!";
        }

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

    // Check if we are in Edit Mode
    $editOrg = null;
    if (isset($_GET['edit'])) {
        $stmtEdit = $pdo->prepare("SELECT * FROM ORGANIZATION WHERE OrgID = ?");
        $stmtEdit->execute([$_GET['edit']]);
        $editOrg = $stmtEdit->fetch();
    }

} catch (PDOException $e) {
    $error = "System Error: " . $e->getMessage();
}
?>

<?php include '../includes/header.php'; ?>
<style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .modal-content-card {
        background: white;
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        border-top: 6px solid #6B1A22;
        width: 100%;
        max-width: 500px;
        position: relative;
    }
    .close-modal {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 1.8rem;
        font-weight: bold;
        color: #aaa;
        cursor: pointer;
    }
    .close-modal:hover {
        color: #6B1A22;
    }
</style>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="user-info">
            <div class="avatar">A</div>
            <h3>Administrator</h3>
            <p class="student-id">System Admin</p>
        </div>
        <nav class="side-nav">
            <p class="nav-label">Navigation</p>
            <a href="manage_orgs.php" class="active">Manage Organizations</a>
            <a href="manage_users.php">Assign Officers</a>
            <a href="manage_students.php">Manage Students</a>
        </nav>
    </aside>

    <main class="main-content">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #F8F5F2; padding-bottom: 0.5rem;">
                <h3 style="margin: 0; border: none; padding: 0;">Current Organizations</h3>
                <button onclick="openOrgModal()" class="btn-primary" style="width: auto; margin: 0; padding: 0.5rem 1.5rem; font-size: 0.9rem;">+ Create Organization</button>
            </div>

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

<div id="orgModal" class="modal" style="<?= $editOrg ? 'display: flex;' : 'display: none;' ?>">
    <div class="modal-content-card">
        <span class="close-modal" onclick="closeOrgModal()">&times;</span>
        <h3 style="margin-bottom: 1.5rem; text-transform: uppercase; color: #6B1A22; font-size: 1.1rem; letter-spacing: 1px;">
            <?= $editOrg ? 'Edit Organization' : 'Register New Organization' ?>
        </h3>
        
        <form method="POST" action="manage_orgs.php">
            <input type="hidden" name="action" value="<?= $editOrg ? 'update' : 'create' ?>">
            
            <?php if ($editOrg): ?>
                <div class="form-group">
                    <label>Org ID (Read-only):</label>
                    <input type="text" name="org_id" value="<?= htmlspecialchars($editOrg['OrgID']) ?>" readonly style="background-color: #eee; cursor: not-allowed;">
                </div>
            <?php endif; ?>
            
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
            
            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary" style="margin-top: 0;"><?= $editOrg ? 'Save Changes' : 'Add Organization' ?></button>
                <?php if ($editOrg): ?>
                    <a href="manage_orgs.php" class="btn-secondary" style="text-align: center; width: 100%; box-sizing: border-box; display: inline-block; padding: 0.9rem 0;">Cancel Edit</a>
                <?php else: ?>
                    <button type="button" onclick="closeOrgModal()" class="btn-secondary" style="margin-top: 0; width: 100%;">Cancel</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function openOrgModal() {
    document.getElementById('orgModal').style.display = 'flex';
}
function closeOrgModal() {
    document.getElementById('orgModal').style.display = 'none';
    if(window.location.search.includes('edit')) {
        window.location.href = 'manage_orgs.php';
    }
}
</script>

<?php include '../includes/footer.php'; ?>