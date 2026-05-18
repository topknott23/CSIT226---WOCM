<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    function requireRole($role) {
        if (!isLoggedIn() || $_SESSION['user_type'] !== $role) {
            header("Location: /wocm/login.php");
            exit();
        }
    }

    function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    function generateUuid4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
?>