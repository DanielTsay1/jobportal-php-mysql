<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin-login.php');
    exit;
}
require_once '../php/db.php';

// Get filter parameter
$status_filter = $_GET['status'] ?? 'all';

// Build query based on filter
if ($status_filter === 'all') {
    $sql = "SELECT j.*, c.name as company_name FROM `job-post` j JOIN company c ON j.compid = c.compid ORDER BY j.created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT j.*, c.name as company_name FROM `job-post` j JOIN company c ON j.compid = c.compid WHERE j.status = ? ORDER BY j.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $status_filter);
}

$stmt->execute();
$jobs = $stmt->get_result();

// Get counts for statistics
$pending_count = $conn->query("SELECT COUNT(*) as count FROM `job-post` WHERE status = 'Pending'")->fetch_assoc()['count'];
$active_count = $conn->query("SELECT COUNT(*) as count FROM `job-post` WHERE status = 'Active'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM `job-post` WHERE status = 'Rejected'")->fetch_assoc()['count'];
$total_count = $conn->query("SELECT COUNT(*) as count FROM `job-post`")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-purple: #7c3aed;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #dc2626;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .admin-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .admin-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-blue);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .stat-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .filter-tabs {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-btn {
            border: none;
            background: #f1f5f9;
            color: #64748b;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-right: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-blue);
            color: white;
        }
        
        .job-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .job-card.pending {
            border-left-color: var(--warning-orange);
        }
        
        .job-card.active {
            border-left-color: var(--success-green);
        }
        
        .job-card.rejected {
            border-left-color: var(--danger-red);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-approve {
            background: var(--success-green);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-approve:hover {
            background: #059669;
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-reject {
            background: var(--danger-red);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-reject:hover {
            background: #b91c1c;
            color: white;
            transform: translateY(-1px);
        }
        
        .logout-btn {
            background: var(--danger-red);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #b91c1c;
            color: white;
            text-decoration: none;
        }
        
        .no-jobs {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="admin-title">
                        <i class="fas fa-shield-alt me-3"></i>
                        Admin Dashboard
                    </h1>
                    <p class="admin-subtitle">
                        Welcome back, <?= htmlspecialchars($_SESSION['admin_username']) ?> | 
                        Manage job postings and applications
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="/php/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number"><?= $total_count ?></div>
                    <div class="stat-label">Total Jobs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number" style="color: var(--warning-orange);"><?= $pending_count ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number" style="color: var(--success-green);"><?= $active_count ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number" style="color: var(--danger-red);"><?= $rejected_count ?></div>
                    <div class="stat-label">Rejected Jobs</div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">
                <i class="fas fa-list me-2"></i>All Jobs
            </a>
            <a href="?status=Pending" class="filter-btn <?= $status_filter === 'Pending' ? 'active' : '' ?>">
                <i class="fas fa-clock me-2"></i>Pending
            </a>
            <a href="?status=Active" class="filter-btn <?= $status_filter === 'Active' ? 'active' : '' ?>">
                <i class="fas fa-check me-2"></i>Active
            </a>
            <a href="?status=Rejected" class="filter-btn <?= $status_filter === 'Rejected' ? 'active' : '' ?>">
                <i class="fas fa-times me-2"></i>Rejected
            </a>
        </div>

        <!-- Jobs List -->
        <div class="jobs-container">
            <?php if ($jobs->num_rows === 0): ?>
                <div class="no-jobs">
                    <i class="fas fa-inbox fa-3x mb-3" style="color: #cbd5e1;"></i>
                    <h3>No jobs found</h3>
                    <p>There are no jobs matching your current filter.</p>
                </div>
            <?php else: ?>
                <?php while ($job = $jobs->fetch_assoc()): ?>
                    <div class="job-card <?= strtolower($job['status']) ?>">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="mb-1"><?= htmlspecialchars($job['designation']) ?></h5>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-building me-2"></i>
                                    <?= htmlspecialchars($job['company_name']) ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?= htmlspecialchars($job['location']) ?>
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1">
                                    <strong>Salary:</strong> $<?= number_format($job['salary']) ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Posted:</strong> <?= date('M j, Y', strtotime($job['created_at'])) ?>
                                </p>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <span class="status-badge status-<?= strtolower($job['status']) ?>">
                                    <?= htmlspecialchars($job['status']) ?>
                                </span>
                            </div>
                            <div class="col-md-3 text-md-end d-flex flex-column align-items-end gap-2">
                                <?php if ($job['status'] === 'Pending'): ?>
                                    <form method="post" action="/php/approve-job.php" style="display: inline;">
                                        <input type="hidden" name="jobid" value="<?= $job['jobid'] ?>">
                                        <button name="action" value="approve" class="btn-approve me-2">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button name="action" value="reject" class="btn-reject">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                    <a href="applicants.php?jobid=<?= $job['jobid'] ?>" class="btn btn-info btn-sm mt-2"><i class="fas fa-users me-1"></i>View Applicants</a>
                                <?php else: ?>
                                    <div class="mb-1 small text-muted">
                                        <?= $job['status'] === 'Active' ? 'Approved' : 'Rejected' ?>
                                    </div>
                                    <form method="post" action="/php/remove-job.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to move this job to Pending?');">
                                        <input type="hidden" name="jobid" value="<?= $job['jobid'] ?>">
                                        <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-undo me-1"></i>Move to Pending</button>
                                    </form>
                                    <a href="applicants.php?jobid=<?= $job['jobid'] ?>" class="btn btn-info btn-sm mt-2"><i class="fas fa-users me-1"></i>View Applicants</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 