<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php'; // Universal UUID and session helpers

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = generateUuid4();
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $userType = 'Student';
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO USER (UserID, Email, Password, UserType) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $email, $password, $userType]);

        if ($userType === 'Student') {
            $studentId = $_POST['studentId'];
            $fullName = $_POST['fullName'];
            $course = $_POST['course'];
            $yearLevel = $_POST['yearLevel'];

            $stmtStudent = $pdo->prepare("INSERT INTO STUDENT (UserID, StudentID, FullName, Course, YearLevel) VALUES (?, ?, ?, ?, ?)");
            $stmtStudent->execute([$userId, $studentId, $fullName, $course, $yearLevel]);
        }

        $pdo->commit();
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <h2>REGISTER</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required pattern=".*@cit\.edu$" title="Must be a valid @cit.edu email">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div id="student-fields">
                <div class="form-group">
                    <label>Student ID:</label>
                    <input type="text" name="studentId" required>
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullName" required>
                </div>
                <div class="form-group">
                    <label>Course:</label>
                    <input type="text" name="course" required>
                </div>
                <div class="form-group">
                    <label>Year Level:</label>
                    <input type="text" name="yearLevel" required>
                </div>
            </div>

            <button type="submit" class="btn-primary">Register</button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Login Here</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>