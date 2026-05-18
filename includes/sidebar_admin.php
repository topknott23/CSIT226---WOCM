<?php
$activePage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="user-info">
        <div class="avatar">A</div>
        <h3>Administrator</h3>
        <p class="student-id">System Admin</p>
    </div>
    <nav class="side-nav">
        <p class="nav-label">Navigation</p>
        <a href="manage_orgs.php" class="<?= $activePage === 'manage_orgs.php' ? 'active' : '' ?>">Manage Organizations</a>
        <a href="manage_users.php" class="<?= $activePage === 'manage_users.php' ? 'active' : '' ?>">Assign Officers</a>
        <a href="manage_students.php" class="<?= $activePage === 'manage_students.php' ? 'active' : '' ?>">Manage Students</a>
    </nav>
</aside>