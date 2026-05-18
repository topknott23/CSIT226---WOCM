<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'Student') header("Location: student/dashboard.php");
    elseif ($_SESSION['user_type'] === 'Officer') header("Location: officer/dashboard.php");
    elseif ($_SESSION['user_type'] === 'Admin') header("Location: admin/manage_orgs.php");
    exit();
}

require_once 'includes/db_connect.php';

require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = generateUuid4();
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userType = 'Student';
    
    // PROBLEM 7 FIX: Server-side validation of institutional email parameters
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@cit.edu')) {
        $error = "Registration failed: You must use a valid institutional email ending with @cit.edu.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO USER (UserID, Email, Password, UserType) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $email, $hashedPassword, $userType]);

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
            
            // PROBLEM 6 FIX: Log internal error code to database log safely; show safe message to user
            error_log("Critical Registration Transaction Failure: " . $e->getMessage());
            $error = "Registration failed due to an unexpected system error. Please try again later.";
        }
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
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>