<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['user_type'] !== $role) {
        header("Location: ../login.php");
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>