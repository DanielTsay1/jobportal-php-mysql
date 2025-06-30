<style>
.header-glass-recruiter {
  position: fixed;
  top: 0; left: 0; width: 100vw;
  height: 68px;
  z-index: 2000;
  background: #ffffff;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  transition: all 0.3s ease;
}

.header-glass-recruiter .navbar-brand {
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -1.5px;
  color: #2563eb;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.header-glass-recruiter .navbar-brand .fa {
  color: #3b82f6;
  font-size: 1.3em;
}

.header-glass-recruiter .navbar-nav .nav-link {
  color: #1f2937;
  font-weight: 500;
  font-size: 1rem;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0.25rem;
}

.header-glass-recruiter .navbar-nav .nav-link.active, 
.header-glass-recruiter .navbar-nav .nav-link:focus, 
.header-glass-recruiter .navbar-nav .nav-link:hover {
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
  text-decoration: none;
  transform: translateY(-1px);
}

.header-glass-recruiter .navbar-nav .nav-link.logout {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: #fff !important;
  font-weight: 600;
  border-radius: 12px;
  padding: 0.5rem 1.5rem;
  margin-left: 0.5rem;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
  transition: all 0.3s ease;
}

.header-glass-recruiter .navbar-nav .nav-link.logout:hover, 
.header-glass-recruiter .navbar-nav .nav-link.logout:focus {
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
}

.header-glass-recruiter .navbar-toggler {
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.5rem;
  transition: all 0.3s ease;
}

.header-glass-recruiter .navbar-toggler:focus {
  box-shadow: 0 0 0 2px #2563eb22;
  border-color: #2563eb;
}

.header-glass-recruiter .navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(37, 99, 235, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

@media (max-width: 900px) {
  .header-glass-recruiter .navbar-nav .nav-link { 
    font-size: 0.95rem; 
    padding: 0.4rem 0.8rem; 
    margin: 0.25rem 0;
  }
  .header-glass-recruiter .navbar-brand { 
    font-size: 1.4rem; 
  }
  .header-glass-recruiter .navbar-nav .nav-link.logout {
    margin-left: 0;
    margin-top: 0.5rem;
  }
}

@media (max-width: 576px) {
  .header-glass-recruiter .navbar-brand { 
    font-size: 1.2rem; 
  }
  .header-glass-recruiter .navbar-nav .nav-link { 
    font-size: 0.9rem; 
    padding: 0.3rem 0.6rem; 
  }
}
</style>
<nav class="header-glass-recruiter navbar navbar-expand-lg">
  <div class="container-fluid px-4" style="height:68px;">
    <a class="navbar-brand" href="/main/recruiter.php">
      <i class="fa fa-briefcase"></i> Job<span style="color:#3b82f6;">Portal</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarRecruiter" aria-controls="navbarRecruiter" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarRecruiter">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'recruiter.php' ? ' active' : '' ?>" href="/main/recruiter.php">
            <i class="fa fa-tachometer-alt"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'manage-jobs.php' ? ' active' : '' ?>" href="/main/manage-jobs.php">
            <i class="fa fa-briefcase"></i> Jobs
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'applicants.php' ? ' active' : '' ?>" href="/main/applicants.php">
            <i class="fa fa-users"></i> Applicants
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'edit-company.php' ? ' active' : '' ?>" href="/main/edit-company.php">
            <i class="fa fa-building"></i> Company
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link logout" href="/php/logout.php">
            <i class="fa fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>