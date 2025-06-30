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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
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

        .job-details-container {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin: 2rem auto;
            max-width: 900px;
            padding: 3rem 2rem;
            position: relative;
        }

        .job-title {
            color: var(--text-dark);
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .company-name {
            color: var(--primary-blue);
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
            background: var(--bg-light);
            border-radius: 16px;
            border: 1px solid var(--border-light);
        }

        .job-meta span {
            color: var(--text-dark);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-meta i {
            color: var(--primary-blue);
            font-size: 1.1rem;
        }

        .job-description {
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 2rem;
            color: var(--text-dark);
            line-height: 1.7;
            margin: 2rem 0;
        }

        .section-title {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .btn-apply, .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .btn-apply:hover, .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
        }

        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary-custom {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            color: var(--text-light);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            background: var(--text-light);
            border-color: var(--text-light);
            color: white;
            transform: translateY(-1px);
        }

        .btn-danger-custom {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-danger-custom:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
            transform: translateY(-1px);
        }

        .alert-custom {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #d97706;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .alert-info-custom {
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.3);
            color: var(--primary-blue);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .alert-warning-custom {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
            border-radius: 12px;
        }

        .modal-content {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-light);
            background: var(--bg-light);
            border-radius: 18px 18px 0 0;
        }

        .modal-footer {
            border-top: 1px solid var(--border-light);
            background: var(--bg-light);
            border-radius: 0 0 18px 18px;
        }

        .list-group-item {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
        }

        .list-group-item a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .list-group-item a:hover {
            color: var(--primary-blue-dark);
            text-decoration: underline;
        }

        .badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .bg-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }

        .bg-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }

        .bg-info {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%) !important;
        }

        .bg-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        }

        .bg-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%) !important;
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .border-top {
            border-color: var(--border-light) !important;
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        .fst-italic {
            color: var(--text-light);
            font-style: italic;
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
        .border-secondary {
            border-color: var(--border-light) !important;
        }
        .text-secondary {
            color: var(--text-light) !important;
        }
        .text-light {
            color: var(--text-dark) !important;
        }

        @media (max-width: 768px) {
            .job-details-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .job-title {
                font-size: 2rem;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 600px) {
            .job-title {
                font-size: 1.75rem;
            }
            
            .job-details-container {
                padding: 1rem;
            }
        }

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
<?php include 'header-jobseeker.php'; ?>

<div class="container py-4">
    <?php if (!$job): ?>
        <div class="alert alert-warning-custom text-center">
            <h4 class="alert-heading">Job Not Found</h4>
            <p>The job you are looking for does not exist or may have been removed.</p>
            <a href="/main/job-list.php" class="btn btn-apply">Browse Other Jobs</a>
        </div>
    <?php else: ?>
        <div class="job-details-container">
            <?php if ($removed): ?>
                <div class="alert alert-info-custom">
                    <i class="fas fa-check-circle me-2"></i>
                    Your application has been successfully withdrawn.
                </div>
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
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-trophy me-3" style="font-size: 2rem; color: #10b981;"></i>
                        <div>
                            <h4 class="mb-2" style="color: #059669; font-weight: 700;">🎉 Congratulations! You've Been Hired!</h4>
                            <p class="mb-2" style="color: #059669; font-weight: 500; font-size: 1.1rem;">
                                <strong><?= htmlspecialchars($job['company_name']) ?></strong> has hired you for the position of <strong><?= htmlspecialchars($job['designation']) ?></strong>!
                            </p>
                            <p class="mb-0" style="color: #059669; font-size: 0.95rem;">
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

            <div class="mt-4 pt-4 border-top">
                <?php if (!isset($_SESSION['userid'])): ?>
                    <a href="/main/login.php" class="btn btn-apply">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Apply
                    </a>
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
                    <a href="/main/apply.php?jobid=<?= $jobid ?>" class="btn btn-apply">
                        <i class="fas fa-paper-plane me-2"></i>Apply Now
                    </a>
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
        <h5 class="modal-title" id="applicationPreviewModalLabel">
            <i class="fas fa-file-alt me-2"></i>Your Application for <?= htmlspecialchars($job['designation']) ?>
        </h5>
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
            <div class="alert alert-success mb-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-trophy me-2" style="color: #10b981;"></i>
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

<!-- Floating Chat Button -->
<a href="https://www.stack-ai.com/chat/68623c004fe0ebb9c4eaeec8-6jBGBvdYxWKz2625u0mQhn" target="_blank" rel="noopener" id="floatingChatBtn" title="Chat with JobPortal AI Agent">
  <i class="fas fa-comments"></i>
</a>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>