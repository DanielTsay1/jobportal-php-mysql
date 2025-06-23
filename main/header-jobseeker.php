<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="job-list.php">
      <i class="fa fa-briefcase"></i> JobPortal
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
        <li class="nav-item">
          <a class="nav-link" href="/php/logout.php">
            <i class="fa fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>