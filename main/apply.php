<?php
session_start();
// Add this block to check for a valid job seeker session
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    // Redirect them to the job details page with a message
    header('Location: /main/job-details.php?jobid=' . ($_GET['jobid'] ?? 0) . '&error=not_logged_in');
    exit;
}
require_once '../php/db.php';

$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : 0;
$userid = $_SESSION['userid'];

$stmt = $conn->prepare("SELECT jp.*, c.name AS company_name, c.suspended, c.suspension_reason FROM `job-post` jp JOIN company c ON jp.compid = c.compid WHERE jp.jobid = ?");
$stmt->bind_param("i", $jobid);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if company is suspended
$company_suspended = !empty($job['suspended']) && $job['suspended'] == 1;

// Redirect if company is suspended
if ($company_suspended) {
    header('Location: /main/job-details.php?jobid=' . $jobid . '&error=suspended&reason=' . urlencode($job['suspension_reason'] ?? 'No reason provided.'));
    exit;
}

// Check if user is suspended
$suspended = false;
$suspension_reason = '';
if (isset($_SESSION['userid'])) {
    $stmt = $conn->prepare("SELECT suspended, suspension_reason FROM user WHERE userid = ?");
    $stmt->bind_param("i", $_SESSION['userid']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($result['suspended']) && $result['suspended'] == 1) {
        $suspended = true;
        $suspension_reason = $result['suspension_reason'] ?? 'No reason provided.';
    }
}

$questions = [];
if ($job && !empty($job['questions'])) {
    $questions = json_decode($job['questions'], true) ?: [];
}

// Fetch user's resumes from user_resumes table
$resumes_stmt = $conn->prepare("SELECT id, original_filename, filename FROM user_resumes WHERE user_id = ? ORDER BY id ASC");
$resumes_stmt->bind_param("i", $userid);
$resumes_stmt->execute();
$resumes_result = $resumes_stmt->get_result();
$user_resumes = [];
while ($resume = $resumes_result->fetch_assoc()) {
    $user_resumes[] = $resume;
}
$resumes_stmt->close();

$has_resumes = count($user_resumes) > 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Apply for <?= htmlspecialchars($job['designation'] ?? 'Job') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

        .apply-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
        }

        .apply-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .text-gradient {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        .text-gradient-company {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        .section-divider {
            border: none;
            border-top: 2px solid var(--border-light);
            width: 60px;
            margin-left: 0;
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            padding: 0.75rem 1.5rem;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
        }

        .apply-form {
            background: none;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .resume-option, .upload-new-resume {
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-light);
        }

        .resume-option.selected, .upload-new-resume.selected {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .resume-option:hover, .upload-new-resume:hover {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .resume-option input[type="radio"], .upload-new-resume input[type="radio"] {
            margin-right: 0.5rem;
        }

        .additional-questions-section {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-light);
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            background: var(--bg-light) !important;
            color: var(--text-dark) !important;
            border: 2px solid var(--border-light) !important;
            border-radius: 12px !important;
            font-size: 1rem;
            font-weight: 500;
            padding: 0.85rem 1.2rem;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            margin-bottom: 0.5rem;
        }

        .form-control:focus {
            background: var(--bg-white) !important;
            color: var(--text-dark) !important;
            border-color: var(--primary-blue) !important;
            box-shadow: 0 0 0 2px #2563eb22 !important;
        }

        .form-select {
            background: var(--bg-light) !important;
            color: var(--text-dark) !important;
            border: 2px solid var(--border-light) !important;
            border-radius: 12px !important;
            font-size: 1rem;
            font-weight: 500;
            padding: 0.85rem 1.2rem;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            margin-bottom: 0.5rem;
        }

        .form-select:focus {
            background: var(--bg-white) !important;
            color: var(--text-dark) !important;
            border-color: var(--primary-blue) !important;
            box-shadow: 0 0 0 2px #2563eb22 !important;
        }

        .bg-light, .bg-light.rounded {
            background: var(--bg-light) !important;
            color: var(--text-dark) !important;
            border: 1px solid var(--border-light) !important;
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .text-dark {
            color: var(--text-dark) !important;
        }

        .btn-outline-secondary {
            border: 2px solid var(--border-light);
            color: var(--text-light);
            border-radius: 12px;
            font-weight: 500;
            background: transparent;
            transition: all 0.3s ease;
            padding: 0.75rem 1.5rem;
        }

        .btn-outline-secondary:hover {
            background: var(--text-light);
            color: white;
            border-color: var(--text-light);
            transform: translateY(-1px);
        }

        .form-check-label, .form-check-input {
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .alert-info {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-blue);
            border: 1px solid rgba(37, 99, 235, 0.3);
            border-radius: 12px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        .fw-semibold {
            font-weight: 600;
        }

        .small {
            font-size: 0.875rem;
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
            .apply-title {
                font-size: 1.5rem;
            }
            
            .apply-card {
                margin: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 600px) {
            .apply-title {
                font-size: 1.25rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn-gradient, .btn-outline-secondary {
                width: 100%;
                margin-bottom: 0.5rem;
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

<?php if ($suspended): ?>
    <div class="container mt-4">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading"><i class="fas fa-ban me-2"></i>Your account is suspended</h4>
            <p>Reason: <strong><?= htmlspecialchars($suspension_reason) ?></strong></p>
            <hr>
            <p class="mb-0">You cannot apply for jobs while your account is suspended.</p>
        </div>
    </div>
<?php else: ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="apply-card mb-4">
                <div class="card-body p-4">
                    <h2 class="apply-title">
                        Apply for <span class="text-primary"><?= htmlspecialchars($job['designation'] ?? 'Job') ?></span>
                        <br>
                        <span class="text-gradient-company"><?= htmlspecialchars($job['company_name'] ?? '') ?></span>
                    </h2>
                    <hr class="section-divider">
                    
                    <?php if (!$job): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Job not found.
                        </div>
                    <?php else: ?>
                        <form method="post" action="/php/apply.php" enctype="multipart/form-data" id="applyForm" novalidate>
                            <input type="hidden" name="jobid" value="<?= $jobid ?>">
                            <input type="hidden" name="resume_type" id="resume_type" value="<?= $has_resumes ? 'existing' : 'new' ?>">
                            <div class="mb-4">
                                <label class="form-label" for="cover_letter_file">
                                    <i class="fas fa-file-alt me-2"></i>Cover Letter <span class="text-muted">(PDF/DOC/DOCX)</span>
                                </label>
                                <input type="file" name="cover_letter_file" id="cover_letter_file" class="form-control" accept=".pdf,.doc,.docx" required aria-required="true">
                                <small class="text-muted">Upload your cover letter for this position</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="resume_file">
                                    <i class="fas fa-file-pdf me-2"></i>Resume <span class="text-muted">(PDF/DOC/DOCX)</span>
                                </label>
                                <?php if ($has_resumes): ?>
                                    <div class="mb-3">
                                        <select class="form-select mb-2" name="selected_resume_id" id="selected_resume_id" aria-label="Select from your saved resumes">
                                            <option value="">-- Select from your saved resumes --</option>
                                            <?php foreach ($user_resumes as $resume): ?>
                                                <option value="<?= $resume['id'] ?>"><?= htmlspecialchars($resume['original_filename']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="text-muted small mb-2">
                                            <i class="fas fa-info-circle me-1"></i>Or upload a new resume below:
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="resume_file" class="form-control mb-2" accept=".pdf,.doc,.docx" <?= $has_resumes ? '' : 'required' ?> id="resume_file_input" aria-required="true">
                                <div class="form-check" id="saveResumeCheck" style="display:none;">
                                    <input class="form-check-input" type="checkbox" name="save_resume_to_profile" id="save_resume_to_profile">
                                    <label class="form-check-label" for="save_resume_to_profile">
                                        <i class="fas fa-save me-1"></i>Save this resume to my profile for future applications
                                    </label>
                                </div>
                            </div>
                            <?php if (!empty($questions)): ?>
                                <div class="additional-questions-section">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="fas fa-question-circle me-2"></i>Additional Questions
                                    </h6>
                                    <?php foreach ($questions as $idx => $q): ?>
                                        <div class="mb-3">
                                            <label class="form-label mb-1" for="question_<?= $idx ?>"><?= htmlspecialchars($q) ?></label>
                                            <input type="text" name="question_answers[<?= $idx ?>]" id="question_<?= $idx ?>" class="form-control" required aria-required="true">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex flex-column flex-md-row justify-content-end gap-3 mt-4">
                                <a href="job-details.php?jobid=<?= $jobid ?>" class="btn btn-outline-secondary order-2 order-md-1">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                                <button type="submit" class="btn btn-gradient order-1 order-md-2">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'footer.php'; ?>

<!-- Floating Chat Button -->
<a href="https://www.stack-ai.com/chat/68623c004fe0ebb9c4eaeec8-6jBGBvdYxWKz2625u0mQhn" target="_blank" rel="noopener" id="floatingChatBtn" title="Chat with JobPortal AI Agent">
  <i class="fas fa-comments"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Resume selection logic
const resumeTypeInput = document.getElementById('resume_type');
const resumeFileInput = document.getElementById('resume_file_input');
const resumeDropdown = document.getElementById('selected_resume_id');
const saveResumeCheck = document.getElementById('saveResumeCheck');
const saveResumeCheckbox = document.getElementById('save_resume_to_profile');

function updateResumeUI() {
    if (resumeFileInput && resumeFileInput.value) {
        resumeTypeInput.value = 'new';
        if (resumeDropdown) resumeDropdown.value = '';
        if (saveResumeCheck) saveResumeCheck.style.display = '';
        if (saveResumeCheckbox) saveResumeCheckbox.checked = true;
    } else if (resumeDropdown && resumeDropdown.value) {
        resumeTypeInput.value = 'existing';
        if (saveResumeCheck) saveResumeCheck.style.display = 'none';
        if (saveResumeCheckbox) saveResumeCheckbox.checked = false;
    } else {
        if (saveResumeCheck) saveResumeCheck.style.display = 'none';
        if (saveResumeCheckbox) saveResumeCheckbox.checked = false;
    }
}
if (resumeFileInput) {
    resumeFileInput.addEventListener('change', updateResumeUI);
}
if (resumeDropdown) {
    resumeDropdown.addEventListener('change', updateResumeUI);
}
updateResumeUI();

// Form validation
const applyForm = document.getElementById('applyForm');
applyForm.addEventListener('submit', function(e) {
    let valid = true;
    // Cover letter required
    const coverLetter = document.getElementById('cover_letter_file');
    if (!coverLetter.value) {
        coverLetter.classList.add('is-invalid');
        valid = false;
    } else {
        coverLetter.classList.remove('is-invalid');
    }
    // Resume required
    if (resumeTypeInput.value === 'new' && (!resumeFileInput.value || !/\.(pdf|docx?)$/i.test(resumeFileInput.value))) {
        resumeFileInput.classList.add('is-invalid');
        valid = false;
    } else {
        resumeFileInput.classList.remove('is-invalid');
    }
    // Additional questions required
    const questionInputs = applyForm.querySelectorAll('[name^="question_answers"]');
    questionInputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    if (!valid) {
        e.preventDefault();
        alert('Please fill in all required fields and upload valid files.');
    }
});
</script>
</body>
</html>