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
if (!$job || $job['status'] !== 'Active') {
    header('Location: /main/job-list.php?error=job_not_available');
    exit;
}

$userid = $_SESSION['userid'] ?? null;
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
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'header-jobseeker.php'; ?>
<div class="container py-5">
    <?php if (!$job): ?>
        <div class="alert alert-danger text-center">
            <h4 class="alert-heading">Job Not Found</h4>
            <p>The job you are looking for does not exist or may have been removed.</p>
            <a href="/main/job-list.php" class="btn btn-primary">Browse Other Jobs</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-4 p-md-5">
                <?php if ($removed): ?>
                    <div class="alert alert-info">Your application has been successfully withdrawn.</div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'suspended'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Application Blocked</strong><br>
                        This company is currently suspended and not accepting new applications.<br>
                        <span>Reason: <?= htmlspecialchars($_GET['reason'] ?? 'No reason provided.') ?></span>
                    </div>
                <?php endif; ?>

                <h1 class="display-6"><?= htmlspecialchars($job['designation']) ?></h1>
                <h2 class="h4 text-muted mb-3"><?= htmlspecialchars($job['company_name']) ?></h2>
                
                <div class="d-flex flex-wrap gap-3 text-muted mb-4">
                    <span><i class="fas fa-map-marker-alt me-2 text-primary"></i><?= htmlspecialchars($job['location']) ?></span>
                    <span><i class="fas fa-dollar-sign me-2 text-primary"></i><?= number_format($job['salary']) ?></span>
                    <span><i class="fas fa-users me-2 text-primary"></i><?= htmlspecialchars($job['spots']) ?> Spots Available</span>
                </div>
                
                <h5 class="mt-4">Job Description</h5>
                <div class="job-description bg-light p-3 rounded">
                    <?= nl2br(htmlspecialchars($job['description'])) ?>
                </div>

                <?php if ($company_suspended): ?>
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Application Temporarily Unavailable</strong><br>
                        This company is currently suspended and not accepting new applications.<br>
                        <small class="text-muted">Reason: <?= htmlspecialchars($job['suspension_reason'] ?? 'No reason provided.') ?></small>
                    </div>
                <?php endif; ?>

                <div class="mt-4 pt-4 border-top">
                    <?php if (!isset($_SESSION['userid'])): ?>
                        <a href="/main/login.html" class="btn btn-primary btn-lg">Login to Apply</a>
                    <?php elseif ($application): ?>
                        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#applicationPreviewModal">
                            <i class="fas fa-eye me-2"></i>View My Application
                        </button>
                        <form method="post" action="/php/remove-application.php" class="d-inline ms-2">
                            <input type="hidden" name="jobid" value="<?= $jobid ?>">
                            <button type="submit" class="btn btn-outline-danger btn-lg" onclick="return confirm('Are you sure you want to withdraw your application?');">
                                <i class="fas fa-trash-alt me-2"></i>Withdraw Application
                            </button>
                        </form>
                    <?php elseif ($company_suspended): ?>
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-ban me-2"></i>Applications Disabled
                        </button>
                    <?php else: ?>
                        <a href="/main/apply.php?jobid=<?= $jobid ?>" class="btn btn-primary btn-lg">Apply Now</a>
                    <?php endif; ?>
                </div>
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