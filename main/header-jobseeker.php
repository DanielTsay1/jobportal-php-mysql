<style>
.header-glass-jobseeker {
  position: fixed;
  top: 0; left: 0; width: 100vw;
  height: 68px;
  z-index: 2000;
  background: rgba(30, 30, 50, 0.38);
  backdrop-filter: blur(18px) saturate(1.2);
  box-shadow: 0 2px 16px rgba(30,20,60,0.10);
  border-bottom: 1.5px solid rgba(255,255,255,0.10);
  display: flex;
  align-items: center;
  transition: background 0.18s;
}
.header-glass-jobseeker .navbar-brand {
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -1.5px;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.header-glass-jobseeker .navbar-brand .fa {
  color: #00e0d6;
  font-size: 1.3em;
}
.header-glass-jobseeker .navbar-nav .nav-link {
  color: #f3f3fa;
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
  background: rgba(0,224,214,0.10);
  color: #00e0d6;
  text-decoration: none;
}
.header-glass-jobseeker .navbar-nav .nav-link.logout {
  background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
  color: #fff !important;
  font-weight: 700;
  border-radius: 22px;
  padding: 0.3rem 1.5rem;
  margin-left: 0.5rem;
  box-shadow: 0 2px 8px rgba(0,224,214,0.10);
  transition: background 0.18s, color 0.18s;
}
.header-glass-jobseeker .navbar-nav .nav-link.logout:hover, .header-glass-jobseeker .navbar-nav .nav-link.logout:focus {
  background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
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
      <i class="fa fa-rocket"></i> Job<span style="color:#00e0d6;">Portal</span>
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
        <?php if (isset($_SESSION['userid'])): ?>
        <li class="nav-item">
          <?php include 'notification-component.php'; ?>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link logout" href="/php/logout.php">
            <i class="fa fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>