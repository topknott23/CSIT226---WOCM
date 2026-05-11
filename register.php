<?php
require_once 'includes/db_connect.php';

function generateUuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = generateUuid4();
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $userType = $_POST['userType'];

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
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
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

            <div class="form-group">
                <label>Register As:</label>
                <select name="userType" id="userType" required>
                    <option value="Student">Student</option>
                    <option value="Officer">Officer</option>
                </select>
            </div>

            <div id="student-fields">
                <div class="form-group">
                    <label>Student ID:</label>
                    <input type="text" name="studentId" placeholder="24-4701-389">
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullName">
                </div>
                <div class="form-group">
                    <label>Course:</label>
                    <input type="text" name="course">
                </div>
                <div class="form-group">
                    <label>Year Level:</label>
                    <input type="text" name="yearLevel">
                </div>
            </div>

            <button type="submit" class="btn-primary">Register</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>