<?php
session_start();
require_once '../php/db.php';

$username = $_SESSION['username'] ?? 'Recruiter';
$compid = $_SESSION['compid'] ?? null;

// Get recruiter info
$stmt = $conn->prepare("SELECT * FROM recruiter WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$recruiter = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get company info for this recruiter by compid
$company = null;
if ($compid) {
    $stmt = $conn->prepare("SELECT * FROM company WHERE compid = ?");
    $stmt->bind_param("i", $compid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get open jobs for this company
$jobs = [];
if ($company) {
    $stmt = $conn->prepare("SELECT * FROM `job-post` WHERE compid = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $company['compid']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    $stmt->close();
}

// Get recent applicants for this company's jobs
$applicants = [];
if ($company) {
    $stmt = $conn->prepare("
        SELECT a.*, u.username AS applicant_username, u.email AS applicant_email, j.designation AS job_title
        FROM applied a
        JOIN user u ON a.userid = u.userid
        JOIN `job-post` j ON a.jobid = j.jobid
        WHERE j.compid = ?
        ORDER BY a.applied_at DESC LIMIT 5
    ");
    $stmt->bind_param("i", $company['compid']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $applicants[] = $row;
    }
    $stmt->close();
}

// --- DASHBOARD METRICS ---
$total_jobs_stmt = $conn->prepare("SELECT COUNT(*) as count FROM `job-post` WHERE compid = ? AND status = 'Active'");
$total_jobs_stmt->bind_param("i", $compid);
$total_jobs_stmt->execute();
$total_jobs = $total_jobs_stmt->get_result()->fetch_assoc()['count'] ?? 0;
$total_jobs_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Recruiter Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; font-family: 'Inter', sans-serif; }
        .dashboard-header { background: #fff; border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04);}
        .profile-img { width: 90px; height: 90px; object-fit: cover; border-radius: 50%; border: 3px solid #0d6efd; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04);}
        .card-title { font-weight: 600; }
        .cta-btn { background: #0d6efd; color: #fff; border-radius: 2rem; padding: 0.5rem 2rem; font-weight: 600; }
        .cta-btn:hover { background: #0b5ed7; color: #fff; }
        .icon { color: #0d6efd; margin-right: 0.5rem; }
        .job-status { font-size: 0.9rem; }
        .applicant-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        @media (max-width: 767px) {
            .dashboard-header { text-align: center; }
            .profile-img { margin-bottom: 1rem; }
        }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>
<div class="container py-4">
    <!-- Recruiter Profile Summary -->
    <div class="dashboard-header d-flex align-items-center flex-wrap">
        <img src="<?= htmlspecialchars($company['logo'] ?? '/img/company-placeholder.png') ?>" class="profile-img me-4" alt="Company Logo">
        <div>
            <h2 class="mb-1"><?= htmlspecialchars($company['name'] ?? $username) ?></h2>
            <div class="mb-2 text-muted"><?= htmlspecialchars($company['location'] ?? 'Location not set') ?></div>
            <div>
                <i class="fa fa-envelope icon"></i><?= htmlspecialchars($recruiter['email'] ?? 'No email') ?>
                <span class="mx-2">|</span>
                <i class="fa fa-phone-alt icon"></i><?= htmlspecialchars($company['contact'] ?? 'No contact') ?>
                <span class="mx-2">|</span>
                <i class="fa fa-globe icon"></i><?= htmlspecialchars($company['website'] ?? 'No website') ?>
            </div>
        </div>
        <div class="ms-auto">
            <a href="/main/post-job.php" class="cta-btn"><i class="fa fa-plus"></i> Post New Job</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Open Jobs Card -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fa fa-briefcase icon"></i>Open Jobs</h5>
                    <?php if (count($jobs) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($jobs as $job): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($job['designation']) ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars($job['location']) ?></div>
                                    </div>
                                    <span class="badge bg-success job-status"><?= htmlspecialchars($job['status'] ?? 'Active') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3 text-end">
                            <a href="/main/manage-jobs.php" class="btn btn-outline-primary btn-sm">Manage Jobs</a>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">No jobs posted yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Recent Applicants Card -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fa fa-users icon"></i>Recent Applicants</h5>
                    <?php if (count($applicants) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($applicants as $app): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <img src="/img/profile-placeholder.jpg" class="applicant-avatar me-3" alt="Applicant">
                                    <div>
                                        <strong><?= htmlspecialchars($app['applicant_username']) ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars($app['applicant_email']) ?></div>
                                        <div class="small">Applied for: <span class="fw-semibold"><?= htmlspecialchars($app['job_title']) ?></span></div>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <span class="badge bg-info"><?= date('M d, Y', strtotime($app['applied_at'] ?? '')) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3 text-end">
                            <a href="/main/applicants.php" class="btn btn-outline-primary btn-sm">View All Applicants</a>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">No recent applicants.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Company Info Card -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fa fa-building icon"></i>Company Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Company Name:</strong> <?= htmlspecialchars($company['name'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong> <?= htmlspecialchars($recruiter['email'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Location:</strong> <?= htmlspecialchars($company['location'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Contact:</strong> <?= htmlspecialchars($company['contact'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-12 mb-2">
                            <strong>Website:</strong> <?= htmlspecialchars($company['website'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-12 mb-2">
                            <strong>About:</strong>
                            <div class="text-muted"><?= nl2br(htmlspecialchars($company['about'] ?? 'No description provided.')) ?></div>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <a href="/main/edit-company.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-edit"></i> Edit Company Info</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>