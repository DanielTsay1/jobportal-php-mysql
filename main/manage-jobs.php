<?php
session_start();
require_once '../php/db.php';

// Check if user is a recruiter
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A' || !isset($_SESSION['compid'])) {
    header('Location: /main/login.php');
    exit;
}

$compid = $_SESSION['compid'];
$recid = $_SESSION['recid'] ?? null;

// Get status from GET param, default to 'All'
$status_filter = $_GET['status'] ?? 'All';

// Get recruiter info
$stmt = $conn->prepare("SELECT * FROM recruiter WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$recruiter = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get company info by compid
$company = null;
if ($compid) {
    $stmt = $conn->prepare("SELECT * FROM company WHERE compid = ?");
    $stmt->bind_param("i", $compid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Check if company is suspended
$company_suspended = !empty($company['suspended']) && $company['suspended'] == 1;

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    $response = ['success' => false, 'message' => '', 'action' => ''];
    $jobid = intval($_POST['jobid'] ?? 0);
    
    if ($jobid > 0) {
        // Get job details for notification
        $job_stmt = $conn->prepare("SELECT designation FROM `job-post` WHERE jobid = ? AND compid = ?");
        $job_stmt->bind_param("ii", $jobid, $compid);
        $job_stmt->execute();
        $job_result = $job_stmt->get_result();
        $job = $job_result->fetch_assoc();
        $job_stmt->close();
        
        if ($job) {
            $job_title = $job['designation'];
            
            switch ($_POST['ajax_action']) {
                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM `job-post` WHERE jobid = ? AND compid = ?");
                    $stmt->bind_param("ii", $jobid, $compid);
                    if ($stmt->execute()) {
                        $response = [
                            'success' => true, 
                            'message' => "Job '$job_title' has been permanently deleted.",
                            'action' => 'delete'
                        ];
                    } else {
                        $response['message'] = "Failed to delete job.";
                    }
                    $stmt->close();
                    break;
                    
                case 'unpost':
                    $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Inactive' WHERE jobid = ? AND compid = ?");
                    $stmt->bind_param("ii", $jobid, $compid);
                    if ($stmt->execute()) {
                        $response = [
                            'success' => true, 
                            'message' => "Job '$job_title' has been unposted (set to inactive).",
                            'action' => 'unpost'
                        ];
                    } else {
                        $response['message'] = "Failed to unpost job.";
                    }
                    $stmt->close();
                    break;
                    
                case 'repost':
                    if (!$company_suspended) {
                        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Active' WHERE jobid = ? AND compid = ?");
                        $stmt->bind_param("ii", $jobid, $compid);
                        if ($stmt->execute()) {
                            $response = [
                                'success' => true, 
                                'message' => "Job '$job_title' has been reposted (set to active).",
                                'action' => 'repost'
                            ];
                        } else {
                            $response['message'] = "Failed to repost job.";
                        }
                        $stmt->close();
                    } else {
                        $response['message'] = "Cannot repost job - company is suspended.";
                    }
                    break;
            }
        } else {
            $response['message'] = "Job not found.";
        }
    } else {
        $response['message'] = "Invalid job ID.";
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle edit (POST) - keep this as is for form submission
if (isset($_POST['edit_jobid']) && !isset($_POST['ajax_action'])) {
    $jobid = intval($_POST['edit_jobid']);
    $designation = $_POST['designation'] ?? '';
    $location = $_POST['location'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $description = $_POST['description'] ?? '';
    $spots = intval($_POST['spots'] ?? 1);
    
    // Get job title for notification
    $job_stmt = $conn->prepare("SELECT designation FROM `job-post` WHERE jobid = ? AND compid = ?");
    $job_stmt->bind_param("ii", $jobid, $compid);
    $job_stmt->execute();
    $job_result = $job_stmt->get_result();
    $job = $job_result->fetch_assoc();
    $job_stmt->close();
    
    $stmt = $conn->prepare("UPDATE `job-post` SET designation=?, location=?, salary=?, description=?, spots=? WHERE jobid=? AND compid=?");
    $stmt->bind_param("ssdsiii", $designation, $location, $salary, $description, $spots, $jobid, $compid);
    if ($stmt->execute()) {
        $edit_message = "Job '" . ($job['designation'] ?? 'Unknown') . "' has been updated successfully.";
    } else {
        $edit_message = "Failed to update job.";
    }
    $stmt->close();
}

// Fetch jobs based on status filter
$jobs = [];
$sql = "SELECT jp.*, (SELECT COUNT(*) FROM applied WHERE jobid = jp.jobid) AS applicant_count 
        FROM `job-post` jp 
        WHERE jp.compid = ?";
$params = [$compid];
$types = 'i';

if ($status_filter !== 'All') {
    $sql .= " AND jp.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$sql .= " ORDER BY jp.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Jobs - JobPortal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

        .page-header {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .filter-section {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .nav-pills .nav-link {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 30px;
            padding: 0.5rem 1.25rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .nav-pills .nav-link:hover, .nav-pills .nav-link.active {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn, .btn-gradient {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .btn:hover, .btn-gradient:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
        }

        .btn-outline:hover {
            background: var(--primary-blue);
            color: #fff;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: #fff;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
        }

        .btn-secondary {
            background: var(--text-light);
            border: none;
        }

        .btn-secondary:hover {
            background: #4b5563;
            color: #fff;
        }

        .job-card, .app-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .job-card:hover, .app-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
        }

        .job-title, .application-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .job-meta, .application-meta {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .job-description {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-suspended {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .badge-applicants, .badge-spots {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 0.5rem 1rem;
            color: var(--text-dark);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .badge-applicants {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-blue);
        }

        .badge-spots {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .empty-state {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary-blue);
            opacity: 0.5;
        }

        .empty-state h3, .empty-state h4 {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .empty-state .btn {
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
            margin: 0 auto;
            display: inline-block;
        }

        .form-control {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            font-size: 1rem;
            color: var(--text-dark);
            transition: border 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            background: var(--bg-white);
            outline: none;
            box-shadow: 0 0 0 2px #2563eb22;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
        }

        .alert-danger, .alert-danger-glass {
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

        /* Notification Styles */
        .notification-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border-light);
            padding: 1rem;
            z-index: 9999;
            min-width: 300px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .notification-popup.show {
            transform: translateX(0);
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .notification-popup.success .notification-icon {
            color: #10b981;
        }

        .notification-popup.error .notification-icon {
            color: #ef4444;
        }

        .notification-message {
            flex: 1;
            font-weight: 500;
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .notification-close:hover {
            background: var(--bg-light);
            color: var(--text-dark);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9998;
        }

        .loading-spinner {
            background: var(--bg-white);
            border-radius: 18px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-md);
        }

        .loading-spinner i {
            font-size: 2rem;
            color: var(--primary-blue);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Footer Styling */
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
            .page-title {
                font-size: 1.5rem;
            }
            
            .job-card, .app-card {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-btn {
                text-align: center;
            }

            .nav-pills {
                flex-wrap: wrap;
            }

            .nav-pills .nav-link {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
            <div>
                <h1 class="page-title">Manage Jobs</h1>
                <p class="page-subtitle">Manage your job postings and track applications</p>
            </div>
            <a href="post-job.php" class="btn btn-gradient px-4">
                <i class="fas fa-plus me-2"></i>Post New Job
            </a>
        </div>
    </div>

    <!-- Error Display -->
    <div id="ajaxErrorBox" style="display:none; max-width:900px; margin:0 auto 1.5rem auto;">
        <div class="alert alert-danger d-flex align-items-center justify-content-between">
            <span id="ajaxErrorMsg"></span>
            <button type="button" class="btn-close ms-3" aria-label="Close" onclick="document.getElementById('ajaxErrorBox').style.display='none';"></button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="d-flex justify-content-center">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?= $status_filter === 'Active' ? 'active' : '' ?>" href="?status=Active">
                        <i class="fas fa-check-circle me-2"></i>Active
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status_filter === 'Pending' ? 'active' : '' ?>" href="?status=Pending">
                        <i class="fas fa-clock me-2"></i>Pending
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status_filter === 'Inactive' ? 'active' : '' ?>" href="?status=Inactive">
                        <i class="fas fa-pause-circle me-2"></i>Inactive
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status_filter === 'Suspended' ? 'active' : '' ?>" href="?status=Suspended">
                        <i class="fas fa-ban me-2"></i>Suspended
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status_filter === 'All' ? 'active' : '' ?>" href="?status=All">
                        <i class="fas fa-list me-2"></i>All
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Jobs List -->
    <?php if (empty($jobs)): ?>
        <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <h4>No jobs found</h4>
            <p>No jobs found for the selected filter.</p>
            <a href="post-job.php" class="btn btn-gradient mt-2">
                <i class="fas fa-plus me-2"></i>Create New Job
            </a>
        </div>
    <?php else: ?>
        <div class="jobs-grid">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card" data-jobid="<?= $job['jobid'] ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h3 class="job-title"><?= htmlspecialchars($job['designation']) ?></h3>
                            <div class="job-meta">
                                <i class="fas fa-building me-1"></i><?= htmlspecialchars($company['name'] ?? '') ?>
                                <span class="ms-3"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($job['location']) ?></span>
                                <span class="ms-3"><i class="fas fa-calendar me-1"></i><?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                            </div>
                        </div>
                        <span class="status-badge status-<?= strtolower($job['status']) ?> text-uppercase">
                            <?= htmlspecialchars($job['status']) ?>
                        </span>
                    </div>
                    
                    <div class="d-flex gap-2 mb-3">
                        <span class="badge-applicants">
                            <i class="fas fa-users me-1"></i><?= $job['applicant_count'] ?> Applicants
                        </span>
                        <span class="badge-spots">
                            <?= max(0, $job['spots'] - $job['applicant_count']) ?>/<?= $job['spots'] ?> Spots
                        </span>
                    </div>
                    
                    <p class="job-description">
                        <?= htmlspecialchars(substr($job['description'], 0, 150)) ?>...
                    </p>
                    
                    <div class="action-buttons">
                        <a href="applicants.php?jobid=<?= $job['jobid'] ?>" class="btn btn-gradient btn-sm">
                            <i class="fas fa-users me-1"></i>View Applicants
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['jobid'] ?>">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <?php if ($job['status'] === 'Active'): ?>
                            <button type="button" class="btn btn-warning btn-sm" onclick="performAction('unpost', <?= $job['jobid'] ?>, <?= json_encode($job['designation']) ?>)">
                                <i class="fas fa-eye-slash me-1"></i>Unpost
                            </button>
                        <?php elseif ($job['status'] === 'Inactive' && !$company_suspended): ?>
                            <button type="button" class="btn btn-success btn-sm" onclick="performAction('repost', <?= $job['jobid'] ?>, <?= json_encode($job['designation']) ?>)">
                                <i class="fas fa-undo me-1"></i>Repost
                            </button>
                        <?php elseif ($job['status'] === 'Suspended'): ?>
                            <span class="btn btn-secondary btn-sm disabled" title="Job suspended due to company suspension">
                                <i class="fas fa-ban me-1"></i>Suspended
                            </span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger btn-sm" onclick="performAction('delete', <?= $job['jobid'] ?>, <?= json_encode($job['designation']) ?>)">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global JavaScript Functions
window.performAction = function(action, jobId, jobTitle) {
    console.log('performAction called:', action, jobId, jobTitle);
    let confirmMessage = '';
    let confirmAction = false;
    
    switch(action) {
        case 'delete':
            confirmMessage = `Are you sure you want to permanently delete the job "${jobTitle}" and all its applications?`;
            confirmAction = confirm(confirmMessage);
            break;
        case 'unpost':
            confirmMessage = `Are you sure you want to unpost the job "${jobTitle}"?`;
            confirmAction = confirm(confirmMessage);
            break;
        case 'repost':
            confirmMessage = `Are you sure you want to repost the job "${jobTitle}"?`;
            confirmAction = confirm(confirmMessage);
            break;
    }
    
    if (confirmAction) {
        executeAction(action, jobId, jobTitle);
    }
};

window.executeAction = function(action, jobId, jobTitle) {
    console.log('executeAction called:', action, jobId, jobTitle);
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.style.display = 'flex';
    
    const formData = new FormData();
    formData.append('ajax_action', action);
    formData.append('jobid', jobId);
    
    fetch('manage-jobs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingOverlay.style.display = 'none';
        
        if (data.success) {
            showNotification(data.message, 'success');
            
            // Remove the job card from DOM if it was deleted
            if (action === 'delete') {
                const jobCard = document.querySelector(`[data-jobid="${jobId}"]`);
                if (jobCard) {
                    jobCard.style.opacity = '0';
                    jobCard.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        jobCard.remove();
                    }, 300);
                }
            } else {
                // Refresh the page to update job statuses
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        } else {
            showNotification(data.message || 'Action failed. Please try again.', 'error');
            showAjaxError(data.message || 'Action failed. Please try again.');
        }
    })
    .catch(error => {
        loadingOverlay.style.display = 'none';
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        showAjaxError('AJAX error: ' + error);
    });
};

window.showNotification = function(message, type = 'success') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = `notification-popup ${type}`;
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                 type === 'error' ? 'fas fa-exclamation-circle' : 
                 'fas fa-exclamation-triangle';
    
    notification.innerHTML = `
        <div class="notification-content">
            <i class="notification-icon ${icon}"></i>
            <div class="notification-message">${message}</div>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
};

window.showAjaxError = function(message) {
    var box = document.getElementById('ajaxErrorBox');
    var msg = document.getElementById('ajaxErrorMsg');
    msg.textContent = message;
    box.style.display = 'block';
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Show edit notification if exists
    <?php if (isset($edit_message)): ?>
        showNotification('<?= addslashes($edit_message) ?>', 'success');
    <?php endif; ?>
    
    // Handle edit form submission
    const editForms = document.querySelectorAll('form[method="post"]');
    editForms.forEach(form => {
        if (form.querySelector('input[name="edit_jobid"]')) {
            form.addEventListener('submit', function(e) {
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.style.display = 'flex';
                
                // Form will submit normally, but we'll show loading
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 1000);
            });
        }
    });
});
</script>

<!-- Notification Container -->
<div id="notificationContainer"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner"></i>
        <p class="mt-2 mb-0">Processing...</p>
    </div>
</div>

<?php foreach ($jobs as $job): ?>
<!-- Edit Modal -->
<div class="modal fade" id="editModal<?= $job['jobid'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $job['jobid'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel<?= $job['jobid'] ?>">
                    <i class="fas fa-edit me-2"></i>Edit Job: <?= htmlspecialchars($job['designation']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_jobid" value="<?= $job['jobid'] ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Job Title</label>
                        <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($job['designation']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($job['location']) ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Salary</label>
                        <input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($job['salary']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Spots Available</label>
                        <input type="number" name="spots" class="form-control" value="<?= htmlspecialchars($job['spots']) ?>" min="1" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($job['description']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
</body>
</html>