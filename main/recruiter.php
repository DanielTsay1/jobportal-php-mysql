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
        body {
            background: linear-gradient(135deg, #181828 0%, #23233a 100%);
            color: #f3f3fa;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px; /* Prevent content under header */
        }
        .apply-card {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.18);
            border: 1.5px solid rgba(120,130,255,0.13);
            color: #f3f3fa;
            margin-bottom: 2rem;
        }
        .dashboard-title {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.01em;
            text-shadow: 0 2px 8px rgba(102,126,234,0.13);
        }
        .section-divider {
            border: none;
            border-top: 2px solid rgba(120,130,255,0.13);
            width: 60px;
            margin-left: 0;
            margin-bottom: 1.2rem;
            opacity: 0.8;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #00e0d6 0%, #7b3fe4 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.18);
            display: block;
            margin-bottom: 12px;
            text-align: center;
            text-decoration: none !important;
        }
        .btn-gradient:hover {
            background: linear-gradient(90deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }
        .stat-card {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(30,20,60,0.13);
            border: 1.5px solid rgba(120,130,255,0.10);
            color: #f3f3fa;
            padding: 1.5rem 1.2rem;
            margin-bottom: 1.2rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2.1rem;
            font-weight: 700;
            margin: 0 0 0.2rem 0;
            color: #00e0d6;
        }
        .stat-label {
            color: #b3b3c6;
            font-size: 1rem;
            margin: 0;
            font-weight: 600;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            margin: 0 auto 0.7rem auto;
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            box-shadow: 0 2px 8px rgba(0,224,214,0.10);
        }
        .quick-actions {
            background: none;
            border: none;
            box-shadow: none;
            padding: 0;
        }
        .quick-actions h5 {
            font-weight: 700;
            color: #e8eaf6;
            margin-bottom: 1rem;
        }
        .recent-applications {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(30,20,60,0.13);
            border: 1.5px solid rgba(120,130,255,0.10);
            color: #f3f3fa;
            padding: 1.2rem 1rem;
            margin-bottom: 1.2rem;
        }
        .application-item {
            border-left: 4px solid #00e0d6;
            padding: 1rem 0.7rem 0.7rem 1.2rem;
            margin-bottom: 1rem;
            background: rgba(255,255,255,0.03);
            border-radius: 0 10px 10px 0;
        }
        .status-badge {
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            background: #ffd700;
            color: #23233a;
        }
        .empty-state, .no-data {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 18px;
            color: #b3b3c6;
            text-align: center;
            padding: 2rem 1rem;
            margin: 2rem auto;
            box-shadow: 0 4px 20px rgba(30,20,60,0.13);
        }
        .dashboard-header {
            background: none;
            border: none;
            box-shadow: none;
            padding: 0;
            margin-bottom: 0;
        }
        /* Responsive chart containers */
        .apply-card canvas {
            width: 100% !important;
            max-width: 100% !important;
            min-height: 220px !important;
            max-height: 340px !important;
            display: block;
            margin: 0 auto;
        }
        .apply-card .chart-container, .apply-card .chart-card {
            min-height: 260px;
            max-width: 100%;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 900px) {
            .stat-card { padding: 1rem 0.7rem; }
            .recent-applications { padding: 1rem 0.5rem; }
            .apply-card .chart-container, .apply-card .chart-card { min-height: 180px; }
        }
        @media (max-width: 700px) {
            .dashboard-title { font-size: 1.3rem; }
            .apply-card { padding: 1.2rem 0.5rem 1.2rem 0.5rem; }
            .apply-card .chart-container, .apply-card .chart-card { min-height: 120px; }
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

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="apply-card mb-4 p-4">
                    <div class="mb-4">
                        <span class="dashboard-title"><i class="fas fa-tachometer-alt me-3"></i>Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Recruiter') ?>!</span>
                        <hr class="section-divider mb-3">
                        <div class="fs-5 mb-2">Managing opportunities at <span class="text-gradient-company fw-bold"><?= htmlspecialchars($company['name'] ?? 'Your Company') ?></span></div>
                        <span class="badge bg-light text-primary fs-6 mb-2 px-3 py-2">
                            <i class="fas fa-building me-2"></i>Recruiter Dashboard
                        </span>
                        <small class="text-light d-block mb-2">Last updated: <?= date('M j, Y g:i A') ?></small>
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon mb-2"><i class="fas fa-briefcase"></i></div>
                                <div class="stat-number"><?= $job_stats['total_jobs'] ?? 0 ?></div>
                                <div class="stat-label">Total Jobs Posted</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon mb-2"><i class="fas fa-users"></i></div>
                                <div class="stat-number"><?= $app_stats['total_applications'] ?? 0 ?></div>
                                <div class="stat-label">Total Applications</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon mb-2"><i class="fas fa-clock"></i></div>
                                <div class="stat-number"><?= $app_stats['pending_applications'] ?? 0 ?></div>
                                <div class="stat-label">Pending Reviews</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon mb-2"><i class="fas fa-user-check"></i></div>
                                <div class="stat-number"><?= $app_stats['hired_applications'] ?? 0 ?></div>
                                <div class="stat-label">Successful Hires</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="apply-card mb-4 p-3">
                                <h5 class="fw-bold mb-3"><i class="fas fa-chart-pie me-2 text-gradient"></i>Application Status Distribution</h5>
                                <canvas id="statusChart" height="120"></canvas>
                            </div>
                            <div class="apply-card mb-4 p-3">
                                <h5 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-gradient"></i>Job Performance Overview</h5>
                                <canvas id="jobPerformanceChart" height="120"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="apply-card mb-4 p-3 quick-actions">
                                <h5 class="fw-bold mb-3"><i class="fas fa-bolt me-2 text-gradient"></i>Quick Actions</h5>
                                <a href="post-job.php" class="btn-gradient"><i class="fas fa-plus me-2"></i>Post New Job</a>
                                <a href="manage-jobs.php" class="btn-gradient"><i class="fas fa-cog me-2"></i>Manage Jobs</a>
                                <a href="applicants.php" class="btn-gradient"><i class="fas fa-users me-2"></i>View Applicants</a>
                                <a href="edit-company.php" class="btn-gradient"><i class="fas fa-edit me-2"></i>Edit Company</a>
                            </div>
                            <div class="apply-card recent-applications p-3">
                                <h5 class="fw-bold mb-3"><i class="fas fa-clock me-2 text-gradient"></i>Recent Applications</h5>
                                <?php if ($recent_applications && $recent_applications->num_rows > 0): ?>
                                    <?php while ($app = $recent_applications->fetch_assoc()): ?>
                                        <div class="application-item mb-2">
                                            <div class="fw-semibold mb-1"><?= htmlspecialchars($app['designation']) ?></div>
                                            <div class="small text-muted mb-1"><i class="fas fa-user me-1"></i><?= htmlspecialchars($app['username']) ?></div>
                                            <div class="small text-muted mb-1"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($app['email']) ?></div>
                                            <span class="status-badge"><?= htmlspecialchars($app['status']) ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="empty-state">No recent applications found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Application Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
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