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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        .job-card, .page-header, .empty-state {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.18);
            border: 1.5px solid rgba(120,130,255,0.13);
            color: #f3f3fa;
        }
        .btn, .btn-modern {
            background: linear-gradient(90deg, #00e0d6 0%, #7b3fe4 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.18);
        }
        .btn:hover, .btn-modern:hover {
            background: linear-gradient(90deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }
        .form-control, .form-select {
            background: #39395a !important;
            color: #f3f3fa !important;
            border: 2px solid #e9ecef !important;
            border-radius: 10px !important;
            font-size: 1.08rem;
            font-weight: 500;
            box-shadow: 0 2px 12px rgba(123,63,228,0.08);
            transition: border 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
            margin-bottom: 0.2rem;
        }
        .form-control:focus, .form-select:focus {
            background: #44446a !important;
            color: #fff !important;
            border-color: #667eea !important;
            box-shadow: 0 4px 24px rgba(102, 126, 234, 0.25) !important;
        }
        .badge-modern {
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
        }
        .badge-applicants { background: #6b7280 !important; color: #fff !important; border-radius: 10px; font-size: 0.9em; font-weight: 600; padding: 0.3em 1em; margin-right: 0.5em; }
        .badge-spots { background: #7b3fe4; color: #fff; border-radius: 10px; font-size: 0.9em; font-weight: 600; padding: 0.3em 1em; }
        .empty-state {
            color: #b3b3c6;
            text-align: center;
            padding: 2rem 1rem;
            margin: 2rem auto;
        }
        @media (max-width: 900px) {
            .job-card { padding: 1rem 0.7rem; }
        }
        @media (max-width: 700px) {
            .job-card { padding: 1.2rem 0.5rem 1.2rem 0.5rem; }
        }
        .glass-panel {
            background: rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.13);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            z-index: 2;
            position: relative;
        }
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .app-card {
            background: rgba(255,255,255,0.13);
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(123,63,228,0.08);
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
            color: #f3f3fa;
            margin-bottom: 1.5rem;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .app-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .app-card:hover {
            box-shadow: 0 8px 25px rgba(123,63,228,0.13);
            border-color: #00e0d6;
            transform: translateY(-2px);
        }
        .application-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #e8eaf6;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .application-company {
            color: #667eea;
            font-size: 1rem;
            margin-bottom: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        .application-meta {
            color: #b3b3c6;
            font-size: 0.95rem;
            margin-bottom: 0.8rem;
            font-weight: 400;
        }
        .status-badge {
            display: inline-block;
            border-radius: 10px;
            font-size: 0.9em;
            font-weight: 600;
            padding: 0.4em 1em;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
            text-transform: uppercase;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
            border: none;
        }
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #fff;
            border: none;
        }
        .status-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: #fff;
            border: none;
        }
        .status-suspended {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
            border: none;
        }
        .btn-primary { background: #3b82f6 !important; color: #fff !important; border: none !important; }
        .btn-success { background: #22c55e !important; color: #fff !important; border: none !important; }
        .btn-danger { background: #ef4444 !important; color: #fff !important; border: none !important; }
        /* Modal Glassmorphism Theme */
        .modal-content {
            background: rgba(36, 38, 58, 0.98) !important;
            color: #f3f3fa !important;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.18);
            border: 1.5px solid rgba(120,130,255,0.13);
            backdrop-filter: blur(18px) saturate(1.2);
        }
        .modal-header, .modal-footer {
            background: rgba(36, 38, 58, 0.98) !important;
            color: #f3f3fa !important;
            border-bottom: 1px solid rgba(120,130,255,0.13);
        }
        .modal-title, .modal-body label, .modal-body input, .modal-body textarea {
            color: #f3f3fa !important;
        }
        .modal-body input, .modal-body textarea {
            background: #23233a !important;
            border: 1.5px solid #39395a !important;
            color: #f3f3fa !important;
        }
        .modal-body input:focus, .modal-body textarea:focus {
            background: #23233a !important;
            border-color: #7b3fe4 !important;
            color: #fff !important;
        }
        .modal-footer .btn-primary {
            background: #3b82f6 !important;
            border: none;
        }
        .modal-footer .btn-secondary {
            background: #39395a !important;
            color: #f3f3fa !important;
            border: none;
        }
        .modal-dialog {
            margin-top: 80px;
        }
        @media (max-width: 700px) {
            .modal-dialog {
                margin-top: 40px;
            }
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        
        .loading-spinner {
            background: rgba(36, 38, 58, 0.95);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            color: #f3f3fa;
            border: 1.5px solid rgba(120,130,255,0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .loading-spinner i {
            font-size: 2rem;
            color: #7b3fe4;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Notification System */
        #notificationContainer {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        }
        
        .notification-popup {
            background: rgba(36, 38, 58, 0.95);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            border: 1.5px solid rgba(120,130,255,0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .notification-popup.show {
            transform: translateX(0);
        }
        
        .notification-popup.success {
            border-color: #28a745;
        }
        
        .notification-popup.error {
            border-color: #dc3545;
        }
        
        .notification-popup.warning {
            border-color: #ffc107;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #f3f3fa;
        }
        
        .notification-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .notification-popup.success .notification-icon {
            color: #28a745;
        }
        
        .notification-popup.error .notification-icon {
            color: #dc3545;
        }
        
        .notification-popup.warning .notification-icon {
            color: #ffc107;
        }
        
        .notification-message {
            flex: 1;
            font-weight: 500;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #b3b3c6;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: color 0.2s;
        }
        
        .notification-close:hover {
            color: #f3f3fa;
        }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>
<div class="container py-4">
  <div id="ajaxErrorBox" style="display:none; max-width:900px; margin:0 auto 1.5rem auto;">
    <div class="alert alert-danger-glass d-flex align-items-center justify-content-between" style="background:rgba(220,53,69,0.13); border:1.5px solid #dc3545; color:#dc3545; border-radius:16px; font-weight:600; box-shadow:0 4px 18px rgba(220,53,69,0.08);">
      <span id="ajaxErrorMsg"></span>
      <button type="button" class="btn-close ms-3" aria-label="Close" onclick="document.getElementById('ajaxErrorBox').style.display='none';"></button>
    </div>
  </div>
  <div class="glass-panel" style="max-width:1200px; margin:2.5rem auto 2rem auto; padding:2.5rem 2rem 2rem 2rem;">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <!-- Filter bar here -->
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
      </div>
      <a href="post-job.php" class="btn btn-gradient px-4"><i class="fas fa-plus me-2"></i>Post New Job</a>
    </div>
    <?php if (empty($jobs)): ?>
      <div class="empty-state text-center py-5">
        <i class="fas fa-briefcase fa-3x mb-3" style="color: #cbd5e1;"></i>
        <h4>No jobs found</h4>
        <p>No jobs found for the selected filter.</p>
        <a href="post-job.php" class="btn btn-gradient mt-2"><i class="fas fa-plus me-2"></i>Post Your First Job</a>
      </div>
    <?php else: ?>
      <div class="applications-grid">
        <?php foreach ($jobs as $job): ?>
          <div class="app-card mb-3" data-jobid="<?= $job['jobid'] ?>">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="application-title mb-0"><?= htmlspecialchars($job['designation']) ?></div>
              <span class="status-badge status-<?= strtolower($job['status']) ?> text-uppercase ms-2"><?= htmlspecialchars($job['status']) ?></span>
            </div>
            <div class="application-company mb-1"><i class="fas fa-building me-1"></i><?= htmlspecialchars($company['name'] ?? '') ?></div>
            <div class="application-meta mb-2">
              <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($job['location']) ?>
              <span class="ms-3"><i class="fas fa-calendar me-1"></i><?= date('M d, Y', strtotime($job['created_at'])) ?></span>
            </div>
            <div class="d-flex gap-2 mb-2">
              <span class="badge badge-applicants"><i class="fas fa-users me-1"></i><?= $job['applicant_count'] ?> Applicants</span>
              <span class="badge badge-spots ms-2"><?= max(0, $job['spots'] - $job['applicant_count']) ?>/<?= $job['spots'] ?> Spots</span>
            </div>
            <div class="d-flex gap-2 mt-2">
              <a href="applicants.php?jobid=<?= $job['jobid'] ?>" class="btn btn-gradient btn-sm"><i class="fas fa-users me-1"></i>View Applicants</a>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['jobid'] ?>"><i class="fas fa-edit me-1"></i>Edit</button>
              <?php if ($job['status'] === 'Active'): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="console.log('Unpost button clicked for job', <?= $job['jobid'] ?>); if (typeof performAction === 'function') { performAction('unpost', <?= $job['jobid'] ?>, <?= json_encode($job['designation']) ?>); } else { alert('performAction function not found!'); console.error('performAction is not defined'); } return false;"><i class="fas fa-eye-slash me-1"></i>Unpost</button>
              <?php elseif ($job['status'] === 'Inactive' && !$company_suspended): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="console.log('Repost button clicked for job', <?= $job['jobid'] ?>); if (typeof performAction === 'function') { performAction('repost', <?= $job['jobid'] ?>, <?= json_encode($job['designation']) ?>); } else { alert('performAction function not found!'); console.error('performAction is not defined'); } return false;"><i class="fas fa-undo me-1"></i>Repost</button>
              <?php elseif ($job['status'] === 'Suspended'): ?>
                <span class="btn btn-secondary btn-sm disabled" title="Job suspended due to company suspension"><i class="fas fa-ban me-1"></i>Suspended</span>
              <?php endif; ?>
              <button type="button" class="btn btn-primary btn-sm" onclick="console.log('Delete button clicked for job', <?= $job['jobid'] ?>); if (typeof performAction === 'function') { performAction('delete', <?= $job['jobid'] ?>, <?= json_encode($job['designation']) ?>); } else { alert('performAction function not found!'); console.error('performAction is not defined'); } return false;"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global JavaScript Functions -->
<script>
// Make functions globally available
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
    // Animate job cards on load (staggered)
    document.querySelectorAll('.job-card').forEach(function(card, idx) {
        setTimeout(function() {
            card.classList.add('animated');
        }, 80 * idx);
    });
    
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