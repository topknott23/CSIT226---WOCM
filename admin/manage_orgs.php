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

        if ($action === 'delete') {
            $orgId = $_POST['org_id'];
            
            $stmtCheckOfficers = $pdo->prepare("SELECT * FROM OFFICER WHERE OrgID = ?");
            $stmtCheckOfficers->execute([$orgId]);
            
            if ($stmtCheckOfficers->rowCount() > 0) {
                $error = "Cannot delete: Please unassign all officers from this organization first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM ORGANIZATION WHERE OrgID = ?");
                $stmt->execute([$orgId]);
                $success = "Organization deleted successfully!";
            }
        }
    }

    $stmtOrgs = $pdo->query("SELECT * FROM ORGANIZATION ORDER BY OrgName ASC");
    $organizations = $stmtOrgs->fetchAll();

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
            <a href="manage_orgs.php" class="active">Manage Organizations</a>
            <a href="manage_users.php">Assign Officers</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="card">
            <h3>Register New Organization</h3>
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Org ID:</label>
                    <input type="text" name="org_id" placeholder="e.g., ORG-01" required>
                </div>
                <div class="form-group">
                    <label>Org Name:</label>
                    <input type="text" name="org_name" required>
                </div>
                <div class="form-group">
                    <label>Category:</label>
                    <input type="text" name="category" required>
                </div>
                <div class="form-group">
                    <label>Date Established:</label>
                    <input type="date" name="date_established" required>
                </div>
                <button type="submit" class="btn-primary">Add Organization</button>
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
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to delete this organization?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="org_id" value="<?= htmlspecialchars($org['OrgID']) ?>">
                                <button type="submit" style="background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>