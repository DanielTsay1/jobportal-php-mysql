<?php
session_start();
require_once '../php/db.php';

$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : 0;

// Fetch job info
$stmt = $conn->prepare("SELECT jp.*, c.name AS company_name, c.suspended, c.suspension_reason FROM `job-post` jp JOIN company c ON jp.compid = c.compid WHERE jp.jobid = ?");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if job exists and is active
if (!$job) {
    header('Location: /main/job-list.php?error=job_not_available');
    exit;
}

// Check if user has been hired for this job (even if job is not active)
$user_hired = false;
$userid = $_SESSION['userid'] ?? null;
if ($userid && $job) {
    $hired_stmt = $conn->prepare("SELECT status FROM applied WHERE userid = ? AND jobid = ? AND status = 'Hired'");
    $hired_stmt->bind_param("ii", $userid, $jobid);
    $hired_stmt->execute();
    $user_hired = $hired_stmt->get_result()->num_rows > 0;
    $hired_stmt->close();
}

// Only redirect if job is not active AND user is not hired for it
if ($job['status'] !== 'Active' && !$user_hired) {
    header('Location: /main/job-list.php?error=job_not_available');
    exit;
}

$application = null;

// Check if company is suspended
$company_suspended = !empty($job['suspended']) && $job['suspended'] == 1;

// If user is logged in, check if they have applied and fetch application details
if ($userid && $job) {
    $stmt = $conn->prepare("SELECT * FROM applied WHERE userid = ? AND jobid = ?");
    $stmt->bind_param("ii", $userid, $jobid);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$removed = isset($_GET['removed']) && $_GET['removed'] == 1;

// Prepare questions and answers for the preview modal
$questions = [];
$answers = [];
if ($application) {
    $questions = json_decode($job['questions'] ?? '[]', true);
    $answers = json_decode($application['answers'] ?? '[]', true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($job['designation'] ?? 'Job Details') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #181828 0%, #23233a 100%);
            color: #f3f3fa;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px;
        }
        
        .job-details-container {
            background: rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.13);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            margin: 2rem auto;
            max-width: 900px;
            padding: 3rem 2rem;
            z-index: 2;
            position: relative;
        }
        
        .job-title {
            color: #fff;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .company-name {
            color: #00e0d6;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255,255,255,0.08);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .job-meta span {
            color: #b3b3c6;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .job-meta i {
            color: #00e0d6;
            font-size: 1.1rem;
        }
        
        .job-description {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2rem;
            color: #f3f3fa;
            line-height: 1.7;
            margin: 2rem 0;
        }
        
        .section-title {
            color: #fff;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }
        
        .btn-apply, .btn-primary, .btn-outline-primary {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            border: none;
            border-radius: 25px;
            padding: 1rem 2.5rem;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover, .btn-primary:hover, .btn-outline-primary:hover {
            background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
            box-shadow: 0 8px 25px rgba(0,224,214,0.3);
        }
        
        .btn-secondary-custom {
            background: rgba(255,255,255,0.1);
            border: 1.5px solid rgba(255,255,255,0.2);
            color: #b3b3c6;
            border-radius: 25px;
            padding: 1rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }
        
        .btn-secondary-custom:hover {
            background: rgba(255,255,255,0.15);
            border-color: #00e0d6;
            color: #f3f3fa;
        }
        
        .btn-danger-custom {
            background: rgba(255,107,107,0.1);
            border: 1.5px solid rgba(255,107,107,0.3);
            color: #ff6b6b;
            border-radius: 25px;
            padding: 1rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }
        
        .btn-danger-custom:hover {
            background: rgba(255,107,107,0.2);
            border-color: #ff5252;
            color: #ff5252;
        }
        
        .alert-custom {
            background: rgba(255,193,7,0.1);
            border: 1px solid rgba(255,193,7,0.3);
            color: #ffc107;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            backdrop-filter: blur(8px);
        }
        
        .alert-info-custom {
            background: rgba(0,224,214,0.1);
            border: 1px solid rgba(0,224,214,0.3);
            color: #00e0d6;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            backdrop-filter: blur(8px);
        }
        
        .alert-warning-custom {
            background: rgba(255,107,107,0.1);
            border: 1px solid rgba(255,107,107,0.3);
            color: #ff6b6b;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            backdrop-filter: blur(8px);
        }
        
        .main-header-glass {
            position: fixed;
            top: 0; left: 0; width: 100vw;
            height: 68px;
            z-index: 2000;
            background: rgba(30, 30, 50, 0.38);
            backdrop-filter: blur(18px) saturate(1.2);
            box-shadow: 0 2px 16px rgba(30,20,60,0.10);
            border-bottom: 1.5px solid rgba(255,255,255,0.10);
            display: flex;
            align-items: center;
            transition: background 0.18s;
        }
        
        .nav-link-glass {
            color: #f3f3fa;
            font-weight: 500;
            font-size: 1.08rem;
            text-decoration: none;
            padding: 0.3rem 1.1rem;
            border-radius: 18px;
            transition: background 0.18s, color 0.18s;
            opacity: 0.92;
        }
        
        .nav-link-glass:hover, .nav-link-glass:focus {
            background: rgba(0,224,214,0.10);
            color: #00e0d6;
            text-decoration: none;
        }
        
        .nav-link-cta {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            color: #fff !important;
            font-weight: 700;
            border-radius: 22px;
            padding: 0.3rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,224,214,0.10);
            transition: background 0.18s, color 0.18s;
        }
        
        .nav-link-cta:hover, .nav-link-cta:focus {
            background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
        }
        
        .modal-content {
            background: rgba(30, 30, 50, 0.95);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            color: #f3f3fa;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .list-group-item {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #f3f3fa;
        }
        
        .list-group-item a {
            color: #00e0d6;
            text-decoration: none;
        }
        
        .list-group-item a:hover {
            color: #7b3fe4;
        }
        
        .badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }
        
        .bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%) !important;
        }
        
        .bg-info {
            background: linear-gradient(135deg, #17a2b8 0%, #00e0d6 100%) !important;
        }
        
        .bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
        }
        
        .bg-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
        }
        
        .modal-dialog {
            margin-top: 100px !important;
        }
        .modal-dialog-centered {
            align-items: flex-start !important;
        }
    </style>
</head>
<body style="padding-top:68px;">
<?php include 'header-jobseeker.php'; ?>

<div class="container py-5">
    <?php if (!$job): ?>
        <div class="alert alert-warning-custom text-center">
            <h4 class="alert-heading">Job Not Found</h4>
            <p>The job you are looking for does not exist or may have been removed.</p>
            <a href="/main/job-list.php" class="btn btn-apply">Browse Other Jobs</a>
        </div>
    <?php else: ?>
        <div class="job-details-container">
            <?php if ($removed): ?>
                <div class="alert alert-info-custom">Your application has been successfully withdrawn.</div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'suspended'): ?>
                <div class="alert alert-warning-custom">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Application Blocked</strong><br>
                    This company is currently suspended and not accepting new applications.<br>
                    <span>Reason: <?= htmlspecialchars($_GET['reason'] ?? 'No reason provided.') ?></span>
                </div>
            <?php endif; ?>

            <h1 class="job-title"><?= htmlspecialchars($job['designation']) ?></h1>
            <h2 class="company-name"><?= htmlspecialchars($job['company_name']) ?></h2>
            
            <?php if ($user_hired): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(32, 201, 151, 0.15) 100%); border: 2px solid rgba(40, 167, 69, 0.4); color: #28a745; border-radius: 15px; margin-bottom: 2rem; padding: 1.5rem;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-trophy me-3" style="font-size: 2rem; color: #28a745;"></i>
                        <div>
                            <h4 class="mb-2" style="color: #28a745; font-weight: 700;">🎉 Congratulations! You've Been Hired!</h4>
                            <p class="mb-2" style="color: #28a745; font-weight: 500; font-size: 1.1rem;">
                                <strong><?= htmlspecialchars($job['company_name']) ?></strong> has hired you for the position of <strong><?= htmlspecialchars($job['designation']) ?></strong>!
                            </p>
                            <p class="mb-0" style="color: #28a745; font-size: 0.95rem;">
                                <i class="fas fa-info-circle me-1"></i>
                                This job posting is no longer active as the position has been filled.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="job-meta">
                <span><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($job['location']) ?></span>
                <span><i class="fas fa-dollar-sign"></i><?= number_format($job['salary']) ?></span>
                <span><i class="fas fa-users"></i><?= htmlspecialchars($job['spots']) ?> Spots Available</span>
            </div>
            
            <h5 class="section-title">Job Description</h5>
            <div class="job-description">
                <?= nl2br(htmlspecialchars($job['description'])) ?>
            </div>

            <?php if ($company_suspended): ?>
                <div class="alert alert-warning-custom">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Application Temporarily Unavailable</strong><br>
                    This company is currently suspended and not accepting new applications.<br>
                    <small>Reason: <?= htmlspecialchars($job['suspension_reason'] ?? 'No reason provided.') ?></small>
                </div>
            <?php endif; ?>

            <div class="mt-4 pt-4 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                <?php if (!isset($_SESSION['userid'])): ?>
                    <a href="/main/login.php" class="btn btn-apply">Login to Apply</a>
                <?php elseif ($application): ?>
                    <button type="button" class="btn btn-apply" data-bs-toggle="modal" data-bs-target="#applicationPreviewModal">
                        <i class="fas fa-eye me-2"></i>View My Application
                    </button>
                    <?php if ($job['status'] === 'Active' && $job['spots'] > 0): ?>
                    <form method="post" action="/php/remove-application.php" class="d-inline ms-2">
                        <input type="hidden" name="jobid" value="<?= $jobid ?>">
                        <button type="submit" class="btn btn-danger-custom" onclick="return confirm('Are you sure you want to withdraw your application?');">
                            <i class="fas fa-trash-alt me-2"></i>Withdraw Application
                        </button>
                    </form>
                    <?php endif; ?>
                <?php elseif ($company_suspended): ?>
                    <button class="btn btn-secondary-custom" disabled>
                        <i class="fas fa-ban me-2"></i>Applications Disabled
                    </button>
                <?php else: ?>
                    <a href="/main/apply.php?jobid=<?= $jobid ?>" class="btn btn-apply">Apply Now</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($application): ?>
<!-- Application Preview Modal -->
<div class="modal fade" id="applicationPreviewModal" tabindex="-1" aria-labelledby="applicationPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="applicationPreviewModalLabel">Your Application for <?= htmlspecialchars($job['designation']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Applied On:</strong> <?= date('F j, Y, g:i a', strtotime($application['applied_at'])) ?></p>
        
        <div class="d-flex align-items-center mb-3">
            <strong class="me-2">Status:</strong>
            <span class="badge fs-6 <?php 
                switch($application['status']) {
                    case 'Hired': echo 'bg-success'; break;
                    case 'Rejected': echo 'bg-danger'; break;
                    case 'Shortlisted': echo 'bg-info text-dark'; break;
                    case 'Viewed': echo 'bg-secondary'; break;
                    default: echo 'bg-primary';
                }
                ?>">
                <?= htmlspecialchars($application['status']) ?>
            </span>
        </div>

        <?php if ($application['status'] === 'Hired'): ?>
            <div class="alert alert-success mb-3" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.1) 100%); border: 1px solid rgba(40, 167, 69, 0.3); color: #28a745; border-radius: 10px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-trophy me-2" style="color: #28a745;"></i>
                    <div>
                        <strong>🎉 Congratulations!</strong> You have been hired for this position! 
                        <br><small>The company will contact you with next steps.</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <hr>

        <h5><i class="fas fa-file-alt me-2 text-primary"></i>Your Documents</h5>
        <ul class="list-group list-group-flush mb-3">
          <li class="list-group-item"><a href="/uploads/<?= htmlspecialchars($application['resume_file']) ?>" target="_blank">View Your Resume</a></li>
          <?php if ($application['cover_letter_file']): ?>
            <li class="list-group-item"><a href="/uploads/<?= htmlspecialchars($application['cover_letter_file']) ?>" target="_blank">View Your Cover Letter</a></li>
          <?php endif; ?>
        </ul>

        <?php if (!empty($questions)): ?>
            <hr>
            <h5><i class="fas fa-question-circle me-2 text-primary"></i>Your Answers</h5>
            <dl>
                <?php foreach ($questions as $index => $question): ?>
                    <dt><?= htmlspecialchars($question) ?></dt>
                    <dd class="ms-3 mb-3 fst-italic">"<?= htmlspecialchars($answers[$index] ?? 'No answer provided.') ?>"</dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>