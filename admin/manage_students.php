<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Admin');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    $userIdToDelete = $_POST['user_id'];
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM USER WHERE UserID = ? AND UserType IN ('Student', 'Officer')");
        $stmtDelete->execute([$userIdToDelete]);
        $success = "Account successfully deleted from the system.";
    } catch (PDOException $e) {
        $error = "Error deleting student account: " . $e->getMessage();
    }
}

try {
    $stmtStudents = $pdo->query("
        SELECT u.UserID, u.Email, u.UserType, s.StudentID, s.FullName, s.Course, s.YearLevel 
        FROM USER u JOIN STUDENT s ON u.UserID = s.UserID
        WHERE u.UserType IN ('Student', 'Officer') ORDER BY s.FullName ASC
    ");
    $students = $stmtStudents->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>
<style>
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
    .modal-content-card { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); border-top: 6px solid #e74c3c; width: 100%; max-width: 500px; position: relative; }
    .close-modal { position: absolute; top: 15px; right: 20px; font-size: 1.8rem; font-weight: bold; color: #aaa; cursor: pointer; }
    .close-modal:hover { color: #e74c3c; }
</style>

<div class="dashboard-layout">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="card">
            <h3>Registered Students Master List</h3>
            
            <?php if (isset($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 1rem; color: #666; font-size: 0.9rem;">
                Total Registered Members (Students & Officers): <strong><?= count($students) ?></strong>
            </div>

            <?php if (empty($students)): ?>
                <p class="empty-state">No student or officer accounts registered yet.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($students as $student): ?>
                        <li style="align-items: flex-start;">
                            <div class="event-details">
                                <div class="avatar" style="width: 40px; height: 40px; font-size: 1rem; margin: 0; flex-shrink: 0;">
                                    <?= strtoupper(substr($student['FullName'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="title" style="display:block; font-size: 1.1rem;">
                                        <?= htmlspecialchars($student['FullName']) ?>
                                        <?php if ($student['UserType'] === 'Officer'): ?>
                                            <span style="background: #3498db; color: white; font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; margin-left: 5px; font-weight: bold; vertical-align: middle;">Officer</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="date" style="display:block; margin-bottom: 3px;">
                                        <strong>ID:</strong> <?= htmlspecialchars($student['StudentID']) ?> | 
                                        <strong>Course:</strong> <?= htmlspecialchars($student['Course']) ?>-<?= htmlspecialchars($student['YearLevel']) ?>
                                    </span>
                                    <span class="date"><strong>Email:</strong> <?= htmlspecialchars($student['Email']) ?></span>
                                </div>
                            </div>
                            <div style="margin: 0; padding-top: 10px;">
                                <button type="button" onclick="openDeleteModal('<?= htmlspecialchars($student['UserID']) ?>')" style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Delete Account</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content-card">
        <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
        <h3 style="margin-bottom: 1.5rem; text-transform: uppercase; color: #6B1A22; font-size: 1.1rem; letter-spacing: 1px;">Confirm Account Deletion</h3>
        <p style="color: #555; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">Warning: This action will permanently delete the account, structural membership records, and historical attendance.</p>

        <form method="POST" action="manage_students.php" onsubmit="return validateDeleteCode(event)">
            <input type="hidden" name="action" value="delete_student">
            <input type="hidden" name="user_id" id="delete_user_id" value="">
            
            <div class="form-group">
                <label>Type secret code to authorize deletion:</label>
                <input type="text" id="secret_code_input" required placeholder="Enter code here" autocomplete="off">
                <div id="modal-error-message" class="error-message" style="display: none; margin-top: 0.5rem; margin-bottom: 0; padding: 0.6rem;"></div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary" style="margin-top: 0; background-color: #e74c3c;">Confirm Delete</button>
                <button type="button" onclick="closeDeleteModal()" class="btn-secondary" style="margin-top: 0; width: 100%;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteModal(userId) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('secret_code_input').value = '';
    document.getElementById('modal-error-message').style.display = 'none';
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }
function validateDeleteCode(event) {
    if (document.getElementById('secret_code_input').value === "fasterfoster") return true;
    event.preventDefault();
    const err = document.getElementById('modal-error-message');
    err.textContent = "Incorrect secret code! Action safely aborted.";
    err.style.display = 'block';
    return false;
}
</script>
<?php include '../includes/footer.php'; ?>