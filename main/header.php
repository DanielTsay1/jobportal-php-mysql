<!-- filepath: c:\Users\mandy\jobportal-php-mysql\main\header.php -->
<?php
session_start();
$username = $_SESSION['username'] ?? null;
?>

<nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
    <a href="/main/index.php" class="navbar-brand d-flex align-items-center text-center py-0 px-4 px-lg-5">
        <h1 class="m-0 text-primary">JobPortal</h1>
    </a>
    <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto p-4 p-lg-0">
            <a href="/main/index.php" class="nav-item nav-link active">Home</a>
            <a href="/main/job-list.php" class="nav-item nav-link">Find Jobs</a>
            <a href="/main/profile.php" class="nav-item nav-link">Profile</a>
        </div>
        <?php if ($username): ?>
        <div class="d-flex align-items-center">
            <span class="navbar-text px-lg-3 d-none d-lg-block">Welcome, <?= htmlspecialchars($username) ?></span>
            <a href="/php/logout.php" class="btn btn-danger rounded-0 py-2 px-lg-4">Logout<i class="fa fa-sign-out-alt ms-2"></i></a>
        </div>
        <?php else: ?>
        <a href="/main/login.html" class="btn btn-primary rounded-0 py-2 px-lg-4 d-none d-lg-block">Login<i class="fa fa-arrow-right ms-2"></i></a>
        <?php endif; ?>
    </div>
</nav>