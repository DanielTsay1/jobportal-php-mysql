<?php
require_once '../php/db.php';
$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : 0;
$stmt = $conn->prepare("SELECT jp.*, c.compid AS company_id, c.name AS company_name, c.location AS company_location, c.website AS company_website, c.logo AS company_logo 
    FROM `job-post` jp 
    JOIN company c ON jp.compid = c.compid 
    WHERE jp.jobid = ? AND jp.status = 'Active' LIMIT 1");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $job ? htmlspecialchars($job['designation']) . ' at ' . htmlspecialchars($job['company_name']) : 'Job Not Found' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        .company-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .job-header {
            border-bottom: 1px solid #eee;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
        }
        .job-apply-btn {
            font-size: 1.2rem;
            padding: 0.75rem 2rem;
        }
        .company-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
    </style>
</head>
<body>
<?php include 'header-jobseeker.php'; ?>
<div class="container py-4">
    <?php if ($job): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="job-header d-flex align-items-center mb-4">
                    <?php if (!empty($job['company_logo'])): ?>
                        <img src="<?= htmlspecialchars($job['company_logo']) ?>" alt="Logo" class="company-logo me-3">
                    <?php else: ?>
                        <div class="company-logo d-flex align-items-center justify-content-center me-3 bg-light">
                            <i class="fa fa-building fa-2x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h2 class="mb-1"><?= htmlspecialchars($job['designation']) ?></h2>
                        <h5 class="text-muted mb-0"><?= htmlspecialchars($job['company_name']) ?></h5>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="badge bg-primary me-2"><i class="fa fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                    <span class="badge bg-success me-2"><i class="fa fa-dollar-sign"></i> <?= htmlspecialchars($job['salary']) ?></span>
                    <span class="badge bg-info text-dark"><i class="fa fa-calendar"></i> Posted <?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                </div>
                <div class="mb-4">
                    <h5 class="mb-2"><i class="fa fa-align-left"></i> Job Description</h5>
                    <p style="white-space: pre-line;"><?= htmlspecialchars($job['description']) ?></p>
                </div>
                <a href="apply.php?jobid=<?= urlencode($job['jobid']) ?>" class="btn btn-success job-apply-btn mb-3">
                    <i class="fa fa-paper-plane"></i> Apply Now
                </a>
                <a href="job-list.php" class="btn btn-outline-secondary mb-3 ms-2">
                    <i class="fa fa-arrow-left"></i> Back to Jobs
                </a>
            </div>
            <div class="col-lg-4">
                <div class="company-card">
                    <h5 class="mb-3"><i class="fa fa-building"></i> About the Company</h5>
                    <p class="mb-2"><strong><?= htmlspecialchars($job['company_name']) ?></strong></p>
                    <p class="mb-2"><i class="fa fa-id-badge text-secondary"></i> <strong>Company ID:</strong> <?= htmlspecialchars($job['company_id']) ?></p>
                    <?php if (!empty($job['company_location'])): ?>
                        <p class="mb-2"><i class="fa fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($job['company_location']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($job['company_website'])): ?>
                        <p class="mb-2"><i class="fa fa-globe"></i> <a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank"><?= htmlspecialchars($job['company_website']) ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Job not found or is no longer available.</div>
        <a href="job-list.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Jobs</a>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>