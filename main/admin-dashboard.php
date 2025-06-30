<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin-login.php');
    exit;
}
require_once '../php/db.php';

// Get current tab
$current_tab = $_GET['tab'] ?? 'dashboard';

// Get filter parameter for jobs
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

// Get user statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM user")->fetch_assoc()['count'];
$jobseekers = $conn->query("SELECT COUNT(*) as count FROM user WHERE user_type = 'J'")->fetch_assoc()['count'];
$recruiters = $conn->query("SELECT COUNT(*) as count FROM user WHERE user_type = 'A'")->fetch_assoc()['count'];
$total_companies = $conn->query("SELECT COUNT(*) as count FROM company")->fetch_assoc()['count'];
$total_applications = $conn->query("SELECT COUNT(*) as count FROM applied")->fetch_assoc()['count'];

// Get recent users
$recent_users = $conn->query("SELECT username, email, user_type, created_at FROM user ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get recent companies
$recent_companies = $conn->query("SELECT name, email, created_at FROM company ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get all users for user management tab
$users = $conn->query("SELECT userid, username, email, user_type, created_at, last_login, suspended FROM user ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get all companies for company management tab
$companies = $conn->query("SELECT compid, name, email, industry, created_at, suspended, suspension_reason FROM company ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
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
        
        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 8px 8px 0 0;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary-blue);
            background: #f1f5f9;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-blue);
            background: white;
            border-bottom: 3px solid var(--primary-blue);
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-blue);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
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
        
        .job-card, .user-card, .company-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .job-card:hover, .user-card:hover, .company-card:hover {
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
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        
        .user-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .user-type-jobseeker {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .user-type-recruiter {
            background: #fef3c7;
            color: #92400e;
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: #374151;
        }
        
        .table tbody tr:hover {
            background: #f1f5f9;
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
                        Manage your job portal system
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="index.php" class="btn btn-secondary me-2"><i class="fas fa-arrow-left me-1"></i>Back to Site</a>
                    <a href="/php/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $current_tab === 'dashboard' ? 'active' : '' ?>" 
                   href="?tab=dashboard" role="tab">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $current_tab === 'jobs' ? 'active' : '' ?>" 
                   href="?tab=jobs" role="tab">
                    <i class="fas fa-briefcase me-2"></i>Job Management
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $current_tab === 'users' ? 'active' : '' ?>" 
                   href="?tab=users" role="tab">
                    <i class="fas fa-users me-2"></i>User Management
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $current_tab === 'companies' ? 'active' : '' ?>" 
                   href="?tab=companies" role="tab">
                    <i class="fas fa-building me-2"></i>Company Management
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $current_tab === 'applications' ? 'active' : '' ?>" 
                   href="?tab=applications" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Applications
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="admin-settings.php" role="tab">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
            </li>
        </ul>

        <!-- Dashboard Tab -->
        <?php if ($current_tab === 'dashboard'): ?>
        <div class="tab-content">
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

            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?= $total_users ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?= $jobseekers ?></div>
                        <div class="stat-label">Job Seekers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?= $recruiters ?></div>
                        <div class="stat-label">Recruiters</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?= $total_companies ?></div>
                        <div class="stat-label">Companies</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-users me-2"></i>Recent Users</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_users)): ?>
                                <p class="text-muted">No users registered yet.</p>
                            <?php else: ?>
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                                            <span class="user-type-badge user-type-<?= $user['user_type'] === 'J' ? 'jobseeker' : 'recruiter' ?> ms-2">
                                                <?= $user['user_type'] === 'J' ? 'Job Seeker' : 'Recruiter' ?>
                                            </span>
                                        </div>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-building me-2"></i>Recent Companies</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_companies)): ?>
                                <p class="text-muted">No companies registered yet.</p>
                            <?php else: ?>
                                <?php foreach ($recent_companies as $company): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?= htmlspecialchars($company['name']) ?></strong>
                                        </div>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($company['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Jobs Tab -->
        <?php if ($current_tab === 'jobs'): ?>
        <div class="tab-content">
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?tab=jobs&status=all" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list me-2"></i>All Jobs
                </a>
                <a href="?tab=jobs&status=Pending" class="filter-btn <?= $status_filter === 'Pending' ? 'active' : '' ?>">
                    <i class="fas fa-clock me-2"></i>Pending
                </a>
                <a href="?tab=jobs&status=Active" class="filter-btn <?= $status_filter === 'Active' ? 'active' : '' ?>">
                    <i class="fas fa-check me-2"></i>Active
                </a>
                <a href="?tab=jobs&status=Rejected" class="filter-btn <?= $status_filter === 'Rejected' ? 'active' : '' ?>">
                    <i class="fas fa-times me-2"></i>Rejected
                </a>
            </div>

            <!-- Jobs List -->
            <div class="jobs-container">
                <?php if ($jobs->num_rows === 0): ?>
                    <div class="no-data">
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
        <?php endif; ?>

        <!-- Users Tab -->
        <?php if ($current_tab === 'users'): ?>
        <div class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users me-2"></i>User Management</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="user-type-badge user-type-<?= $user['user_type'] === 'J' ? 'jobseeker' : 'recruiter' ?>">
                                                <?= $user['user_type'] === 'J' ? 'Job Seeker' : 'Recruiter' ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                        <td><?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                                        <td>
                                            <?php if (!empty($user['suspended']) && $user['suspended'] == 1): ?>
                                                <span class="badge bg-danger">Suspended</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewUser(<?= $user['userid'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!empty($user['suspended']) && $user['suspended'] == 1): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="unsuspendUser(<?= $user['userid'] ?>)"><i class="fas fa-undo"></i> Unsuspend</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="suspendUser(<?= $user['userid'] ?>)"><i class="fas fa-ban"></i> Suspend</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Companies Tab -->
        <?php if ($current_tab === 'companies'): ?>
        <div class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-building me-2"></i>Company Management</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Email</th>
                                    <th>Industry</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Suspension Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($company['name'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($company['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($company['industry'] ?? 'N/A') ?></td>
                                        <td><?= date('M j, Y', strtotime($company['created_at'])) ?></td>
                                        <td>
                                            <?php if (!empty($company['suspended']) && $company['suspended'] == 1): ?>
                                                <span class="badge bg-danger">Suspended</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($company['suspended']) && $company['suspended'] == 1): ?>
                                                <?= htmlspecialchars($company['suspension_reason'] ?? 'No reason provided.') ?>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewCompany(<?= $company['compid'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!empty($company['suspended']) && $company['suspended'] == 1): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="unsuspendCompany(<?= $company['compid'] ?>)"><i class="fas fa-undo"></i> Unsuspend</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="suspendCompany(<?= $company['compid'] ?>)"><i class="fas fa-ban"></i> Suspend</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Applications Tab -->
        <?php if ($current_tab === 'applications'): ?>
        <div class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-file-alt me-2"></i>Application Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stat-number"><?= $total_applications ?></div>
                                <div class="stat-label">Total Applications</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <p class="text-muted">
                                To view detailed applications for specific jobs, go to the Job Management tab and click "View Applicants" for any job posting.
                            </p>
                            <a href="?tab=jobs" class="btn btn-primary">
                                <i class="fas fa-briefcase me-2"></i>Go to Job Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- User Details Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userModalBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Company Details Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Company Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="companyModalBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add a modal for suspension reason -->
    <div class="modal fade" id="suspendUserReasonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Suspend User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="suspendUserReasonInput" class="form-label">Reason for suspension</label>
                        <textarea class="form-control" id="suspendUserReasonInput" rows="3" placeholder="Enter reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmSuspendUserBtn">Suspend User</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let suspendUserId = null;
        function viewUser(userId) {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            const modalBody = document.getElementById('userModalBody');
            modal.show();
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            fetch(`/php/admin-user-management.php?action=get_user_details&userid=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        const isSuspended = user.suspended == 1;
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Basic Information</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>Username:</strong></td><td>${user.username}</td></tr>
                                        <tr><td><strong>Email:</strong></td><td>${user.email}</td></tr>
                                        <tr><td><strong>Type:</strong></td><td>
                                            <span class="user-type-badge user-type-${user.user_type === 'J' ? 'jobseeker' : 'recruiter'}">
                                                ${user.user_type === 'J' ? 'Job Seeker' : 'Recruiter'}
                                            </span>
                                        </td></tr>
                                        <tr><td><strong>Joined:</strong></td><td>${new Date(user.created_at).toLocaleDateString()}</td></tr>
                                        <tr><td><strong>Last Login:</strong></td><td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td></tr>
                                        <tr><td><strong>Status:</strong></td><td>${isSuspended ? '<span class="badge bg-danger">Suspended</span>' : '<span class="badge bg-success">Active</span>'}</td></tr>
                                        ${isSuspended && user.suspension_reason ? `<tr><td><strong>Suspension Reason:</strong></td><td>${user.suspension_reason}</td></tr>` : ''}
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Statistics</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="stats-card">
                                                <div class="stat-number">${user.total_applications || 0}</div>
                                                <div class="stat-label">Applications</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-card">
                                                <div class="stat-number">${user.total_jobs_posted || 0}</div>
                                                <div class="stat-label">Jobs Posted</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button id="suspendUserBtn" class="btn btn-${isSuspended ? 'success' : 'warning'} w-100">
                                            <i class="fas fa-${isSuspended ? 'undo' : 'ban'}"></i> ${isSuspended ? 'Unsuspend' : 'Suspend'} User
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h6>Recent Activity</h6>
                                ${user.recent_activity && user.recent_activity.length > 0 ? 
                                    `<div class="list-group list-group-flush">
                                        ${user.recent_activity.map(activity => `
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-${activity.type === 'application' ? 'file-alt' : 'briefcase'} me-2"></i>
                                                    ${activity.title}
                                                </div>
                                                <small class="text-muted">${new Date(activity.date).toLocaleDateString()}</small>
                                            </div>
                                        `).join('')}
                                    </div>` : 
                                    '<p class="text-muted">No recent activity</p>'
                                }
                            </div>
                        `;
                        setTimeout(() => {
                            const btn = document.getElementById('suspendUserBtn');
                            if (btn) {
                                btn.onclick = function() {
                                    if (isSuspended) {
                                        if (confirm('Are you sure you want to unsuspend this user?')) {
                                            updateUserStatus(userId, 'activate_user');
                                        }
                                    } else {
                                        suspendUserId = userId;
                                        document.getElementById('suspendUserReasonInput').value = '';
                                        const suspendModal = new bootstrap.Modal(document.getElementById('suspendUserReasonModal'));
                                        suspendModal.show();
                                    }
                                };
                            }
                        }, 100);
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error loading user details: ${error.message}</div>`;
                });
        }

        document.getElementById('confirmSuspendUserBtn').onclick = function() {
            const reason = document.getElementById('suspendUserReasonInput').value.trim();
            if (!reason) {
                alert('Please provide a reason for suspension.');
                return;
            }
            updateUserStatus(suspendUserId, 'suspend_user', reason);
            bootstrap.Modal.getInstance(document.getElementById('suspendUserReasonModal')).hide();
        };

        function updateUserStatus(userId, action, reason = '') {
            const modalBody = document.getElementById('userModalBody');
            const formData = new FormData();
            formData.append('action', action);
            formData.append('userid', userId);
            if (action === 'suspend_user') {
                formData.append('reason', reason);
            }
            fetch('/php/admin-user-management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh modal content
                    viewUser(userId);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    modalBody.innerHTML += `<div class='alert alert-danger mt-2'>${data.error}</div>`;
                }
            })
            .catch(error => {
                modalBody.innerHTML += `<div class='alert alert-danger mt-2'>Error: ${error.message}</div>`;
            });
        }

        function suspendUser(userId) {
            const reason = prompt('Enter a reason for suspension:');
            if (reason && reason.trim() !== '') {
                const formData = new FormData();
                formData.append('action', 'suspend_user');
                formData.append('userid', userId);
                formData.append('reason', reason);
                fetch('/php/admin-user-management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User suspended successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            } else {
                alert('Suspension reason is required.');
            }
        }
        
        function unsuspendUser(userId) {
            if (confirm('Are you sure you want to unsuspend this user?')) {
                const formData = new FormData();
                formData.append('action', 'activate_user');
                formData.append('userid', userId);
                fetch('/php/admin-user-management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User unsuspended successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
        
        function viewCompany(companyId) {
            const modal = new bootstrap.Modal(document.getElementById('companyModal'));
            const modalBody = document.getElementById('companyModalBody');
            
            modal.show();
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            fetch(`/php/admin-company-management.php?action=get_company_details&compid=${companyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const company = data.company;
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Company Information</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>Name:</strong></td><td>${company.name}</td></tr>
                                        <tr><td><strong>Email:</strong></td><td>${company.email}</td></tr>
                                        <tr><td><strong>Industry:</strong></td><td>${company.industry || 'N/A'}</td></tr>
                                        <tr><td><strong>Registered:</strong></td><td>${new Date(company.created_at).toLocaleDateString()}</td></tr>
                                        <tr><td><strong>Phone:</strong></td><td>${company.phone || 'N/A'}</td></tr>
                                        <tr><td><strong>Website:</strong></td><td>${company.website || 'N/A'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Statistics</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="stats-card">
                                                <div class="stat-number">${company.total_jobs_posted || 0}</div>
                                                <div class="stat-label">Jobs Posted</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-card">
                                                <div class="stat-number">${company.total_applications_received || 0}</div>
                                                <div class="stat-label">Applications</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h6>Recent Job Postings</h6>
                                ${company.recent_jobs && company.recent_jobs.length > 0 ? 
                                    `<div class="list-group list-group-flush">
                                        ${company.recent_jobs.map(job => `
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-briefcase me-2"></i>
                                                    ${job.designation}
                                                    <span class="status-badge status-${job.status.toLowerCase()} ms-2">${job.status}</span>
                                                </div>
                                                <div>
                                                    <small class="text-muted">${job.applications_count} applications</small>
                                                    <small class="text-muted ms-2">${new Date(job.created_at).toLocaleDateString()}</small>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>` : 
                                    '<p class="text-muted">No job postings yet</p>'
                                }
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error loading company details: ${error.message}</div>`;
                });
        }
        
        function suspendCompany(companyId) {
            const reason = prompt('Enter a reason for suspension:');
            if (reason && reason.trim() !== '') {
                const formData = new FormData();
                formData.append('action', 'suspend_company');
                formData.append('compid', companyId);
                formData.append('reason', reason);
                fetch('/php/admin-company-management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Company suspended successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            } else {
                alert('Suspension reason is required.');
            }
        }
        
        function unsuspendCompany(companyId) {
            if (confirm('Are you sure you want to unsuspend this company?')) {
                const formData = new FormData();
                formData.append('action', 'activate_company');
                formData.append('compid', companyId);
                fetch('/php/admin-company-management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Company unsuspended successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
    </script>
</body>
</html> 