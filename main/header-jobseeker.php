<style>
.header-glass-jobseeker {
  position: fixed;
  top: 0; left: 0; width: 100vw;
  height: 68px;
  z-index: 2000;
  background: #ffffff;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  transition: background 0.18s;
}
.header-glass-jobseeker .navbar-brand {
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -1.5px;
  color: #2563eb;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}
.header-glass-jobseeker .navbar-brand .fa {
  color: #3b82f6;
  font-size: 1.3em;
}
.header-glass-jobseeker .navbar-nav .nav-link {
  color: #1f2937;
  font-weight: 500;
  font-size: 1.08rem;
  text-decoration: none;
  padding: 0.3rem 1.1rem;
  border-radius: 18px;
  transition: background 0.18s, color 0.18s;
  opacity: 0.92;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.header-glass-jobseeker .navbar-nav .nav-link.active, .header-glass-jobseeker .navbar-nav .nav-link:focus, .header-glass-jobseeker .navbar-nav .nav-link:hover {
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
  text-decoration: none;
}
.header-glass-jobseeker .navbar-nav .nav-link.logout {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: #fff !important;
  font-weight: 700;
  border-radius: 22px;
  padding: 0.3rem 1.5rem;
  margin-left: 0.5rem;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
  transition: background 0.18s, color 0.18s;
}
.header-glass-jobseeker .navbar-nav .nav-link.logout:hover, .header-glass-jobseeker .navbar-nav .nav-link.logout:focus {
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
  color: #fff;
}
@media (max-width: 900px) {
  .header-glass-jobseeker .navbar-nav .nav-link { font-size: 1rem; padding: 0.3rem 0.7rem; }
  .header-glass-jobseeker .navbar-brand { font-size: 1.2rem; }
}
</style>
<nav class="header-glass-jobseeker navbar navbar-expand-lg">
  <div class="container-fluid px-4" style="height:68px;">
    <a class="navbar-brand" href="job-list.php">
      <i class="fa fa-rocket"></i> Job<span style="color:#3b82f6;">Portal</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarJobseeker" aria-controls="navbarJobseeker" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarJobseeker">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'job-list.php' ? ' active' : '' ?>" href="job-list.php">
            <i class="fa fa-search"></i> Browse Jobs
          </a>
        </li>
        <?php if (isset($_SESSION['userid']) && $_SESSION['user_type'] === 'B'): ?>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-applications.php' ? ' active' : '' ?>" href="my-applications.php">
            <i class="fa fa-file-alt"></i> My Applications
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? ' active' : '' ?>" href="profile.php">
            <i class="fa fa-user"></i> Profile
          </a>
        </li>
        <li class="nav-item">
          <?php include 'notification-component.php'; ?>
        </li>
        <li class="nav-item">
          <a class="nav-link logout" href="/php/logout.php">
            <i class="fa fa-sign-out-alt"></i> Logout
          </a>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link logout" href="login.php">
            <i class="fa fa-sign-in-alt"></i> Login
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>