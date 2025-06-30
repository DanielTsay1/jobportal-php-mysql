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
        :root {
            --primary-blue: #2563eb;
            --primary-blue-dark: #1d4ed8;
            --accent-blue: #3b82f6;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-light: #e5e7eb;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: 0.01em;
            margin-bottom: 1rem;
        }

        .section-divider {
            border: none;
            border-top: 2px solid var(--border-light);
            width: 60px;
            margin-left: 0;
            margin-bottom: 1.2rem;
            opacity: 0.8;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: block;
            margin-bottom: 12px;
            text-align: center;
            text-decoration: none !important;
            padding: 0.75rem 1.5rem;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            color: #fff;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
            transform: translateY(-1px);
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            padding: 1.5rem 1.2rem;
            margin-bottom: 1.2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
        }

        .stat-number {
            font-size: 2.1rem;
            font-weight: 700;
            margin: 0 0 0.2rem 0;
            color: var(--primary-blue);
        }

        .stat-label {
            color: var(--text-light);
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
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            box-shadow: var(--shadow-md);
        }

        .quick-actions {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
        }

        .quick-actions h5 {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .recent-applications {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            padding: 1.5rem;
        }

        .recent-applications h5 {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .application-item {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid var(--border-light);
        }

        .application-item:last-child {
            margin-bottom: 0;
        }

        .application-title {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .application-meta {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-reviewed {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
        }

        .status-hired {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .chart-container {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-container h5 {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Chart sizing fixes */
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
            margin: 1rem 0;
        }

        .chart-wrapper canvas {
            max-height: 100% !important;
            max-width: 100% !important;
        }

        /* Responsive chart sizing */
        @media (max-width: 768px) {
            .chart-wrapper {
                height: 250px;
            }
        }

        @media (max-width: 576px) {
            .chart-wrapper {
                height: 200px;
            }
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #d97706;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        .border-secondary {
            border-color: var(--border-light) !important;
        }

        .text-secondary {
            color: var(--text-light) !important;
        }

        /* Additional classes needed for the page */
        .apply-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .text-gradient-company {
            color: var(--primary-blue);
        }

        .text-gradient {
            color: var(--primary-blue);
        }

        .text-light {
            color: var(--text-light) !important;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-blue);
            opacity: 0.5;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .bg-light {
            background: var(--bg-light) !important;
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .fw-bold {
            font-weight: 700 !important;
        }

        .fw-semibold {
            font-weight: 600 !important;
        }

        .small {
            font-size: 0.875rem;
        }

        .fs-5 {
            font-size: 1.25rem !important;
        }

        .fs-6 {
            font-size: 1rem !important;
        }

        .mb-1 {
            margin-bottom: 0.25rem !important;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .me-1 {
            margin-right: 0.25rem !important;
        }

        .me-2 {
            margin-right: 0.5rem !important;
        }

        .me-3 {
            margin-right: 1rem !important;
        }

        .d-block {
            display: block !important;
        }

        .py-4 {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }

        .p-3 {
            padding: 1rem !important;
        }

        .p-4 {
            padding: 1.5rem !important;
        }

        .g-4 {
            --bs-gutter-x: 1.5rem;
            --bs-gutter-y: 1.5rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: calc(var(--bs-gutter-x) * -.5);
            margin-left: calc(var(--bs-gutter-x) * -.5);
        }

        .col-lg-8, .col-lg-4, .col-md-3, .col-md-12, .col-lg-10 {
            position: relative;
            width: 100%;
            padding-right: calc(var(--bs-gutter-x) * .5);
            padding-left: calc(var(--bs-gutter-x) * .5);
        }

        .col-lg-8 {
            flex: 0 0 auto;
            width: 66.66666667%;
        }

        .col-lg-4 {
            flex: 0 0 auto;
            width: 33.33333333%;
        }

        .col-lg-10 {
            flex: 0 0 auto;
            width: 83.33333333%;
        }

        .col-md-3 {
            flex: 0 0 auto;
            width: 25%;
        }

        .col-md-12 {
            flex: 0 0 auto;
            width: 100%;
        }

        .justify-content-center {
            justify-content: center !important;
        }

        /* Footer Styling - Same as job-list.php */
        .footer {
            background: var(--bg-white);
            border-top: 1px solid var(--border-light);
            padding: 3rem 0 2rem;
            margin-top: 4rem;
            text-align: center;
            box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.05);
        }
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }
        .footer-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-blue);
        }
        .footer p {
            color: var(--text-light);
        }
        .footer a {
            color: var(--primary-blue);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: var(--primary-blue-dark);
        }
        .footer i {
            color: var(--primary-blue);
        }
        .admin-link {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-top: 1rem;
            display: inline-block;
        }
        .admin-link:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }

            .col-lg-8, .col-lg-4, .col-lg-10 {
                width: 100%;
            }

            .col-md-3 {
                width: 50%;
            }
        }

        @media (max-width: 576px) {
            .col-md-3 {
                width: 100%;
            }
        }

        /* Floating Chat Button */
        #floatingChatBtn {
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 99999;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(37,99,235,0.18);
            font-size: 2rem;
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            border: none;
            outline: none;
            cursor: pointer;
            text-decoration: none;
        }
        #floatingChatBtn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            box-shadow: 0 12px 32px rgba(37,99,235,0.25);
            transform: translateY(-2px) scale(1.07);
            color: #fff;
            text-decoration: none;
        }
        #floatingChatBtn:active {
            transform: scale(0.97);
        }
        #floatingChatBtn i {
            pointer-events: none;
        }
        @media (max-width: 600px) {
            #floatingChatBtn {
                right: 16px;
                bottom: 16px;
                width: 48px;
                height: 48px;
                font-size: 1.4rem;
            }
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
                                <div class="chart-wrapper">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                            <div class="apply-card mb-4 p-3">
                                <h5 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-gradient"></i>Job Performance Overview</h5>
                                <div class="chart-wrapper">
                                    <canvas id="jobPerformanceChart"></canvas>
                                </div>
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

    <!-- Floating Chat Button -->
    <a href="https://www.stack-ai.com/chat/68623c004fe0ebb9c4eaeec8-6jBGBvdYxWKz2625u0mQhn" target="_blank" rel="noopener" id="floatingChatBtn" title="Chat with JobPortal AI Agent">
        <i class="fas fa-comments"></i>
    </a>

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
                        '#f59e0b', // Orange for pending
                        '#3b82f6', // Blue for reviewed
                        '#10b981', // Green for hired
                        '#ef4444'  // Red for rejected
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#1f2937',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12
                            },
                            padding: 15
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
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
                    backgroundColor: 'rgba(37, 99, 235, 0.8)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Hires',
                    data: jobData.hires,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e5e7eb'
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: '#e5e7eb'
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            },
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#1f2937',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12
                            },
                            padding: 15
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
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