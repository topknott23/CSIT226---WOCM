<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth_functions.php';
requireRole('Admin');

// --- Handle Deleting a Student ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    $userIdToDelete = $_POST['user_id'];
    
    try {
        // Because schema.sql uses ON DELETE CASCADE, deleting from USER 
        // automatically deletes from STUDENT, MEMBERSHIP, and ATTENDANCE tables.
        $stmtDelete = $pdo->prepare("DELETE FROM USER WHERE UserID = ? AND UserType = 'Student'");
        $stmtDelete->execute([$userIdToDelete]);
        $success = "Student account successfully deleted from the system.";
    } catch (PDOException $e) {
        $error = "Error deleting student: " . $e->getMessage();
    }
}
// ---------------------------------

try {
    // Fetch all students and their emails
    $stmtStudents = $pdo->query("
        SELECT u.UserID, u.Email, s.StudentID, s.FullName, s.Course, s.YearLevel 
        FROM USER u
        JOIN STUDENT s ON u.UserID = s.UserID
        WHERE u.UserType = 'Student'
        ORDER BY s.FullName ASC
    ");
    $students = $stmtStudents->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
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
            <a href="manage_orgs.php">Manage Organizations</a>
            <a href="manage_users.php">Assign Officers</a>
            <a href="manage_students.php" class="active">Manage Students</a>
        </nav>
    </aside>

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
                Total Registered Students: <strong><?= count($students) ?></strong>
            </div>

            <?php if (empty($students)): ?>
                <p class="empty-state">No students have registered yet.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach ($students as $student): ?>
                        <li style="align-items: flex-start;">
                            <div class="event-details">
                                <div class="avatar" style="width: 40px; height: 40px; font-size: 1rem; margin: 0; flex-shrink: 0;">
                                    <?= strtoupper(substr($student['FullName'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="title" style="display:block; font-size: 1.1rem;"><?= htmlspecialchars($student['FullName']) ?></span>
                                    <span class="date" style="display:block; margin-bottom: 3px;">
                                        <strong>ID:</strong> <?= htmlspecialchars($student['StudentID']) ?> | 
                                        <strong>Course:</strong> <?= htmlspecialchars($student['Course']) ?>-<?= htmlspecialchars($student['YearLevel']) ?>
                                    </span>
                                    <span class="date"><strong>Email:</strong> <?= htmlspecialchars($student['Email']) ?></span>
                                </div>
                            </div>
                            
                            <form method="POST" style="margin: 0; padding-top: 10px;">
                                <input type="hidden" name="action" value="delete_student">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($student['UserID']) ?>">
                                <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                    Delete Account
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include '../includes/footer.php'; ?>