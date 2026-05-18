<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Standardized login for EVERYONE (Admin, Officer, Student)
        $stmt = $pdo->prepare("SELECT * FROM USER WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify the hashed password
        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['user_type'] = $user['UserType'];
            
            // Route users to their correct dashboards based on UserType
            if ($user['UserType'] === 'Student') {
                header("Location: student/dashboard.php");
            } elseif ($user['UserType'] === 'Officer') {
                header("Location: officer/dashboard.php");
            } elseif ($user['UserType'] === 'Admin') {
                header("Location: admin/manage_orgs.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Login error: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <h2>LOGIN</h2>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>