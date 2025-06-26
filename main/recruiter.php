<?php
session_start();
require_once '../php/db.php';

if (!isset($_SESSION['recid']) || !isset($_SESSION['compid'])) {
    header("Location: login.php");
    exit;
}

$recid = $_SESSION['recid'];
$compid = $_SESSION['compid'];

// Fetch company information
$company_stmt = $conn->prepare("SELECT name, location, about, suspended, suspension_reason FROM company WHERE compid = ?");
$company_stmt->bind_param("i", $compid);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();
$company_stmt->close();

// Fetch job statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_jobs,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_jobs,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_jobs
    FROM `job-post` 
    WHERE compid = ?
");
$stats_stmt->bind_param("i", $compid);
$stats_stmt->execute();
$job_stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Fetch application statistics
$app_stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) as pending_applications,
        SUM(CASE WHEN a.status = 'Reviewed' THEN 1 ELSE 0 END) as reviewed_applications,
        SUM(CASE WHEN a.status = 'Hired' THEN 1 ELSE 0 END) as hired_applications,
        SUM(CASE WHEN a.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_applications
    FROM applied a
    JOIN `job-post` j ON a.jobid = j.jobid
    WHERE j.compid = ?
");
$app_stats_stmt->bind_param("i", $compid);
$app_stats_stmt->execute();
$app_stats = $app_stats_stmt->get_result()->fetch_assoc();
$app_stats_stmt->close();

// Fetch recent applications
$recent_apps_stmt = $conn->prepare("
    SELECT a.*, j.designation, u.username, u.email
    FROM applied a
    JOIN `job-post` j ON a.jobid = j.jobid
    JOIN user u ON a.userid = u.userid
    WHERE j.compid = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$recent_apps_stmt->bind_param("i", $compid);
$recent_apps_stmt->execute();
$recent_applications = $recent_apps_stmt->get_result();
$recent_apps_stmt->close();

// Fetch job performance data for charts
$job_performance_stmt = $conn->prepare("
    SELECT 
        j.designation,
        COUNT(a.jobid) as applications,
        SUM(CASE WHEN a.status = 'Hired' THEN 1 ELSE 0 END) as hires
    FROM `job-post` j
    LEFT JOIN applied a ON j.jobid = a.jobid
    WHERE j.compid = ?
    GROUP BY j.jobid, j.designation
    ORDER BY applications DESC
    LIMIT 10
");
$job_performance_stmt->bind_param("i", $compid);
$job_performance_stmt->execute();
$job_performance = $job_performance_stmt->get_result();
$job_performance_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Recruiter Dashboard - JobPortal</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1rem 1rem 0.5rem 1rem;
            margin-bottom: 1rem;
            max-width: 100%;
            min-height: unset;
        }
        .recent-applications {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .application-item {
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #d1ecf1; color: #0c5460; }
        .status-hired { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .quick-actions {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .action-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            margin-bottom: 1rem;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            text-align: center;
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .action-post { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .action-manage { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
        .action-applicants { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
        .action-settings { background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); }
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
            width: 100vw;
        }
    </style>
</head>
<body>
    <?php include 'header-recruiter.php'; ?>

    <?php if (!empty($company['suspended']) && $company['suspended'] == 1): ?>
        <div class="alert alert-danger text-center" style="font-size:1.1rem; font-weight:600; margin-bottom: 2rem;">
            <i class="fas fa-ban me-2"></i>
            Your company is currently <b>suspended</b>.<br>
            <span>Reason: <?= htmlspecialchars($company['suspension_reason'] ?? 'No reason provided.') ?></span>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Recruiter') ?>!
                    </h1>
                    <p class="mb-0 fs-5">
                        Managing opportunities at <strong><?= htmlspecialchars($company['name'] ?? 'Your Company') ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-column align-items-md-end">
                        <span class="badge bg-light text-primary fs-6 mb-2 px-3 py-2">
                            <i class="fas fa-building me-2"></i>Recruiter Dashboard
                        </span>
                        <small class="text-light">Last updated: <?= date('M j, Y g:i A') ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container main-content">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <p class="stat-number"><?= $job_stats['total_jobs'] ?? 0 ?></p>
                            <p class="stat-label">Total Jobs Posted</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="stat-number"><?= $app_stats['total_applications'] ?? 0 ?></p>
                            <p class="stat-label">Total Applications</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <p class="stat-number"><?= $app_stats['pending_applications'] ?? 0 ?></p>
                            <p class="stat-label">Pending Reviews</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon me-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p class="stat-number"><?= $app_stats['hired_applications'] ?? 0 ?></p>
                            <p class="stat-label">Successful Hires</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Charts Section -->
            <div class="col-lg-8">
                <!-- Application Status Chart -->
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Application Status Distribution
                    </h5>
                    <canvas id="applicationChart" height="60" style="max-height:220px;"></canvas>
                </div>

                <!-- Job Performance Chart -->
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Job Performance Overview
                    </h5>
                    <canvas id="jobPerformanceChart" height="60" style="max-height:220px;"></canvas>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="quick-actions mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-bolt me-2 text-primary"></i>
                        Quick Actions
                    </h5>
                    <a href="post-job.php" class="action-btn action-post">
                        <i class="fas fa-plus me-2"></i>Post New Job
                    </a>
                    <a href="manage-jobs.php" class="action-btn action-manage">
                        <i class="fas fa-cog me-2"></i>Manage Jobs
                    </a>
                    <a href="applicants.php" class="action-btn action-applicants">
                        <i class="fas fa-users me-2"></i>View Applicants
                    </a>
                    <a href="edit-company.php" class="action-btn action-settings">
                        <i class="fas fa-edit me-2"></i>Edit Company
                    </a>
                </div>

                <!-- Recent Applications -->
                <div class="recent-applications">
                    <h5 class="mb-3">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        Recent Applications
                    </h5>
                    <?php if ($recent_applications->num_rows > 0): ?>
                        <?php while ($app = $recent_applications->fetch_assoc()): ?>
                            <div class="application-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-1"><?= htmlspecialchars($app['designation']) ?></h6>
                                    <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                        <?= htmlspecialchars($app['status']) ?>
                                    </span>
                                </div>
                                <p class="mb-1">
                                    <i class="fas fa-user me-1"></i>
                                    <?= htmlspecialchars($app['username']) ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?= htmlspecialchars($app['email']) ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('M j, Y', strtotime($app['applied_at'])) ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No recent applications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer style="width:100vw; background: linear-gradient(90deg, #e3f0ff 0%, #ede7f6 100%); border-top: 1.5px solid #e3f0ff; margin-top:2rem; padding: 1.5rem 0 1rem 0; text-align:center; font-size:1rem; color:#1976d2;">
      <div style="font-weight:600; letter-spacing:-0.5px; font-size:1.2rem;">
        <i class="fas fa-envelope me-2" style="color:#7b1fa2;"></i>Contact us: <a href="mailto:support@jobportal.com" style="color:#1976d2; text-decoration:underline;">support@jobportal.com</a>
      </div>
      <div style="margin-top:0.5rem; color:#7b1fa2; font-size:1rem;">
        <i class="fas fa-phone me-2"></i>+1 (800) 123-4567
      </div>
      <div style="margin-top:0.5rem; color:#1976d2; font-size:0.98rem;">
        &copy; <?= date('Y') ?> <span style="color:#1976d2;">Job</span><span style="color:#7b1fa2;">Portal</span> &mdash; Your gateway to new opportunities
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Application Status Chart
        const applicationCtx = document.getElementById('applicationChart').getContext('2d');
        new Chart(applicationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Reviewed', 'Hired', 'Rejected'],
                datasets: [{
                    data: [
                        <?= $app_stats['pending_applications'] ?? 0 ?>,
                        <?= $app_stats['reviewed_applications'] ?? 0 ?>,
                        <?= $app_stats['hired_applications'] ?? 0 ?>,
                        <?= $app_stats['rejected_applications'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#28a745',
                        '#dc3545'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Job Performance Chart
        const jobPerformanceCtx = document.getElementById('jobPerformanceChart').getContext('2d');
        
        // Prepare data from PHP
        const jobData = {
            labels: [],
            applications: [],
            hires: []
        };
        
        <?php 
        $job_performance->data_seek(0);
        while ($job = $job_performance->fetch_assoc()): 
        ?>
            jobData.labels.push('<?= addslashes($job['designation']) ?>');
            jobData.applications.push(<?= $job['applications'] ?>);
            jobData.hires.push(<?= $job['hires'] ?>);
        <?php endwhile; ?>

        new Chart(jobPerformanceCtx, {
            type: 'bar',
            data: {
                labels: jobData.labels,
                datasets: [{
                    label: 'Applications',
                    data: jobData.applications,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }, {
                    label: 'Hires',
                    data: jobData.hires,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000); // 5 minutes

        // Add loading animations
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>