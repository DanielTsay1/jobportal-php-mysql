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
    <!-- Add Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, h6, .btn, .nav, .badge, .form-label, .form-control {
            font-family: 'Poppins', 'Inter', Arial, sans-serif !important;
        }
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .job-card h5 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .job-meta, .job-meta span, .text-muted, .empty-state, .badge-modern {
            font-family: 'Poppins', 'Inter', Arial, sans-serif !important;
        }
        .btn, .btn-modern {
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        .nav-pills .nav-link {
            font-weight: 500;
            letter-spacing: 0.01em;
        }
        .form-label {
            font-weight: 500;
        }
        :root {
            --primary: #1E90FF;
            --secondary: #FFD700;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #F8F9FA;
            --dark: #2B3940;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .page-header {
            background: linear-gradient(90deg, #1976d2 0%, #1E90FF 100%);
            color: #fff;
            padding: 0.85rem 0 0.6rem 0;
            margin-bottom: 1.5rem;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 2px 10px rgba(30, 144, 255, 0.13);
            opacity: 0;
            transform: translateY(-30px);
            animation: fadeSlideDown 0.7s cubic-bezier(.4,1.4,.6,1) 0.1s forwards;
        }
        
        .page-header h1 {
            font-weight: 700;
            font-size: 1.45rem;
            margin-bottom: 0;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        
        .page-header p {
            margin-bottom: 0;
            font-size: 1.05rem;
            opacity: 0.95;
            text-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        
        .page-header .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        
        .page-header .header-row .btn {
            font-size: 1rem;
            padding: 0.45rem 1.25rem;
            border-radius: 20px;
            background: #fff;
            color: #1976d2;
            border: 2px solid #1976d2;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(30, 144, 255, 0.07);
            transition: background 0.2s, color 0.2s, border 0.2s;
        }
        
        .page-header .header-row .btn:hover {
            background: #e3f0ff;
            color: #0d47a1;
            border-color: #0d47a1;
        }
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .job-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            opacity: 0;
            transform: translateY(40px);
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .job-card.inactive {
            border-left-color: #6c757d;
            opacity: 0.8;
        }
        
        .job-card.suspended {
            border-left-color: #dc3545;
            opacity: 0.6;
        }
        
        .job-card.animated {
            animation: fadeSlideIn 0.7s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        
        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeSlideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .nav-pills .nav-link {
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            margin: 0 0.25rem;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link:hover {
            background: rgba(30, 144, 255, 0.1);
            transform: translateY(-1px);
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);
        }
        
        .btn-modern {
            border-radius: 25px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: #212529;
        }
        
        .badge-modern {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .badge-applicants {
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
        }
        
        .badge-spots {
            background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%);
        }
        
        .badge-active {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
        }
        
        .badge-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .badge-suspended {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }
        
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px 16px 0 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(30, 144, 255, 0.15);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .job-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .job-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .suspension-notice {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .suspension-notice i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .job-actions {
                flex-direction: column;
            }
            
            .job-actions .btn {
                width: 100%;
            }
            .page-header {
                padding: 0.5rem 0 0.3rem 0;
            }
            .page-header h1 {
                font-size: 1.1rem;
            }
            .page-header .header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        
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
        
        /* Notification Popup Styles */
        .notification-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Poppins', sans-serif;
        }
        
        .notification-popup.show {
            transform: translateX(0);
        }
        
        .notification-popup.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-left: 4px solid #155724;
        }
        
        .notification-popup.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-left: 4px solid #721c24;
        }
        
        .notification-popup.warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            border-left: 4px solid #856404;
        }
        
        .notification-content {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .notification-icon {
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }
        
        .notification-message {
            flex: 1;
            font-size: 0.95rem;
            line-height: 1.4;
            font-weight: 500;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0;
            margin-left: 0.5rem;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        /* Loading overlay for actions */
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
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }
        
        .loading-spinner i {
            font-size: 2rem;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .card-body input.form-control,
        .card-body textarea.form-control,
        .card-body select.form-control {
            background: #39395a !important;
            color: #f3f3fa !important;
            border: 2px solid #7b3fe4 !important;
            border-radius: 12px !important;
            box-shadow: none !important;
            font-size: 1.08rem !important;
            font-weight: 500 !important;
            outline: none !important;
            transition: border 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .card-body input.form-control:focus,
        .card-body textarea.form-control:focus,
        .card-body select.form-control:focus {
            border: 2px solid #00e0d6 !important;
            background: #44446a !important;
            color: #fff !important;
        }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>

<div class="page-header">
    <div class="container">
        <div class="header-row">
            <div>
                <h1 class="mb-1"><i class="fas fa-briefcase me-2"></i>Manage Jobs</h1>
                <p>Manage your job postings and track applications</p>
            </div>
            <a href="post-job.php" class="btn">
                <i class="fas fa-plus me-2"></i>Post New Job
            </a>
        </div>
    </div>
    </div>

<div class="container main-content glass-panel">
    <?php if ($company_suspended): ?>
        <div class="suspension-notice">
            <i class="fas fa-ban"></i>
            <h5>Account Suspended</h5>
            <p>Your company account has been suspended. You cannot post new jobs or reactivate existing ones.</p>
            <p><strong>Reason:</strong> <?= htmlspecialchars($company['suspension_reason'] ?? 'No reason provided') ?></p>
            <p><strong>Contact Support:</strong> <a href="mailto:JobPortalSupport@gmail.com" style="color: white; text-decoration: underline;">JobPortalSupport@gmail.com</a></p>
        </div>
    <?php endif; ?>

    <!-- Status Filter Pills -->
    <div class="d-flex justify-content-center mb-4">
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

            <?php if (count($jobs) > 0): ?>
        <div class="row">
            <?php foreach ($jobs as $i => $job): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="job-card <?= $job['status'] === 'Inactive' ? 'inactive' : ($job['status'] === 'Suspended' ? 'suspended' : '') ?>" 
                         data-jobid="<?= $job['jobid'] ?>" 
                         style="animation-delay: <?= 0.1 + ($i * 0.08) ?>s">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="mb-1 fw-bold"><?= htmlspecialchars($job['designation']) ?></h5>
                                <div class="job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($job['location']) ?></span>
                                    <span><i class="fas fa-dollar-sign"></i><?= number_format($job['salary']) ?></span>
                                </div>
                            </div>
                            <span class="badge badge-modern <?= $job['status'] === 'Active' ? 'badge-active' : ($job['status'] === 'Suspended' ? 'badge-suspended' : ($job['status'] === 'Pending' ? 'badge-pending' : 'badge-inactive')) ?>">
                                    <?= htmlspecialchars($job['status']) ?>
                                </span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge badge-modern badge-applicants">
                                <i class="fas fa-users me-1"></i><?= $job['applicant_count'] ?> Applicants
                            </span>
                            <span class="badge badge-modern badge-spots">
                                <?= max(0, $job['spots'] - $job['applicant_count']) ?>/<?= $job['spots'] ?> Spots
                            </span>
                        </div>
                        
                        <div class="text-muted small mb-3">
                            <i class="fas fa-calendar me-1"></i>Posted <?= date('M d, Y', strtotime($job['created_at'])) ?>
                            <?php if ($job['status'] === 'Pending'): ?>
                                <br><i class="fas fa-clock me-1 text-warning"></i>Waiting for admin approval
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-actions">
                            <button class="btn btn-warning btn-modern btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['jobid'] ?>">
                                <i class="fas fa-edit me-1"></i>Edit
                                </button>
                            
                                <?php if ($job['status'] === 'Active'): ?>
                                <button class="btn btn-secondary btn-modern btn-sm" 
                                        onclick="performAction('unpost', <?= $job['jobid'] ?>, '<?= htmlspecialchars($job['designation']) ?>')">
                                    <i class="fas fa-eye-slash me-1"></i>Unpost
                                </button>
                                <?php elseif ($job['status'] === 'Inactive' && !$company_suspended): ?>
                                <button class="btn btn-success btn-modern btn-sm" 
                                        onclick="performAction('repost', <?= $job['jobid'] ?>, '<?= htmlspecialchars($job['designation']) ?>')">
                                    <i class="fas fa-undo me-1"></i>Repost
                                </button>
                                <?php elseif ($job['status'] === 'Suspended'): ?>
                                <span class="btn btn-secondary btn-modern btn-sm disabled" title="Job suspended due to company suspension">
                                    <i class="fas fa-ban me-1"></i>Suspended
                                </span>
                                <?php endif; ?>
                            
                            <button class="btn btn-danger btn-modern btn-sm" 
                                    onclick="performAction('delete', <?= $job['jobid'] ?>, '<?= htmlspecialchars($job['designation']) ?>')">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
                
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $job['jobid'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                            <form method="post" class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-edit me-2"></i>Edit Job: <?= htmlspecialchars($job['designation']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <input type="hidden" name="edit_jobid" value="<?= $job['jobid'] ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Job Title</label>
                                        <input type="text" name="designation" class="form-control" 
                                               value="<?= htmlspecialchars($job['designation']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Location</label>
                                        <input type="text" name="location" class="form-control" 
                                               value="<?= htmlspecialchars($job['location']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Salary</label>
                                        <input type="number" name="salary" class="form-control" 
                                               value="<?= htmlspecialchars($job['salary']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Spots Available</label>
                                        <input type="number" name="spots" class="form-control" 
                                               value="<?= htmlspecialchars($job['spots']) ?>" min="1" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($job['description']) ?></textarea>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                              </div>
                            </form>
                          </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <h4>No jobs found</h4>
            <p>No jobs found for the '<?= htmlspecialchars($status_filter) ?>' filter.</p>
            <?php if (!$company_suspended): ?>
            <a href="post-job.php" class="btn btn-primary btn-modern">
                <i class="fas fa-plus me-2"></i>Post Your First Job
            </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Notification Container -->
<div id="notificationContainer"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner"></i>
        <p class="mt-2 mb-0">Processing...</p>
    </div>
</div>

<script>
// Animate job cards on load (staggered)
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.job-card').forEach(function(card, idx) {
    setTimeout(function() {
      card.classList.add('animated');
    }, 80 * idx);
  });
  
  // Show edit notification if exists
  <?php if (isset($edit_message)): ?>
    showNotification('<?= addslashes($edit_message) ?>', 'success');
  <?php endif; ?>
});

// Notification system
function showNotification(message, type = 'success') {
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
}

// Action confirmation and execution
function performAction(action, jobId, jobTitle) {
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
}

// Execute AJAX action
function executeAction(action, jobId, jobTitle) {
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
        }
    })
    .catch(error => {
        loadingOverlay.style.display = 'none';
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Handle edit form submission
document.addEventListener('DOMContentLoaded', function() {
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
</body>
</html>