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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #181828 0%, #23233a 100%);
            color: #f3f3fa;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }
        .apply-card {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.18);
            border: 1.5px solid rgba(120,130,255,0.13);
            color: #f3f3fa;
        }
        .apply-title {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.01em;
            text-shadow: 0 2px 8px rgba(102,126,234,0.13);
        }
        .text-gradient {
            background: linear-gradient(90deg, #00e0d6 0%, #7b3fe4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }
        .text-gradient-company {
            background: linear-gradient(90deg, #ffd700 0%, #7b3fe4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }
        .section-divider {
            border: none;
            border-top: 2px solid rgba(120,130,255,0.13);
            width: 60px;
            margin-left: 0;
            margin-bottom: 1.2rem;
            opacity: 0.8;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #00e0d6 0%, #7b3fe4 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.18);
        }
        .btn-gradient:hover {
            background: linear-gradient(90deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }
        .apply-form {
            background: none;
            border: none;
            box-shadow: none;
            padding: 0;
        }
        .resume-option, .upload-new-resume {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.07);
        }
        .resume-option.selected, .upload-new-resume.selected {
            border-color: #00e0d6;
            background: rgba(0,224,214,0.07);
        }
        .resume-option:hover, .upload-new-resume:hover {
            border-color: #7b3fe4;
            background: rgba(123,63,228,0.07);
        }
        .resume-option input[type="radio"], .upload-new-resume input[type="radio"] {
            margin-right: 10px;
        }
        .additional-questions-section {
            background: rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 1.2rem 1rem 1rem 1rem;
            box-shadow: 0 2px 8px rgba(102,126,234,0.07);
            margin-bottom: 1.2rem;
        }
        .form-label {
            font-weight: 600;
            color: #e8eaf6;
            margin-bottom: 0.3rem;
            letter-spacing: 0.01em;
        }
        .form-control {
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
        .form-control:focus {
            background: #44446a !important;
            color: #fff !important;
            border-color: #667eea !important;
            box-shadow: 0 4px 24px rgba(102, 126, 234, 0.25) !important;
        }
        .form-select {
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
        .form-select:focus {
            background: #44446a !important;
            color: #fff !important;
            border-color: #667eea !important;
            box-shadow: 0 4px 24px rgba(102, 126, 234, 0.25) !important;
        }
        .bg-light, .bg-light.rounded {
            background: rgba(255,255,255,0.07) !important;
            color: #e8eaf6 !important;
        }
        .text-primary {
            color: #00e0d6 !important;
        }
        .text-dark {
            color: #e8eaf6 !important;
        }
        .btn-outline-secondary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 10px;
            font-weight: 500;
            background: transparent;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: #667eea;
            color: #fff;
            border-color: #667eea;
        }
        .form-check-label, .form-check-input {
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: #00e0d6;
            border-color: #00e0d6;
        }
        .alert-info {
            background: rgba(0,224,214,0.08);
            color: #00e0d6;
            border: 1.5px solid #00e0d6;
        }
        .alert-danger {
            background: rgba(220,53,69,0.08);
            color: #dc3545;
            border: 1.5px solid #dc3545;
        }
        .text-muted {
            color: #b3b3c6 !important;
        }
        @media (max-width: 700px) {
            .apply-title {
                font-size: 1.3rem;
            }
            .apply-card {
                padding: 1.2rem 0.5rem 1.2rem 0.5rem;
            }
        }
    </style>
</head>
<body style="padding-top:68px;">
<?php include 'header-jobseeker.php'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-10">
            <div class="apply-card mb-4">
                <div class="card-body p-4">
                    <h2 class="apply-title mb-3">Apply for <span class="text-primary"><?= htmlspecialchars($job['designation'] ?? 'Job') ?></span> at <span class="text-dark"><?= htmlspecialchars($job['company_name'] ?? '') ?></span></h2>
                    <hr class="section-divider mb-3">
                    <?php if (!$job): ?>
                        <div class="alert alert-danger">Job not found.</div>
                    <?php else: ?>
                    <form method="post" action="/php/apply.php" enctype="multipart/form-data" id="applyForm">
                        <input type="hidden" name="jobid" value="<?= $jobid ?>">
                        <input type="hidden" name="resume_type" id="resume_type" value="<?= $has_resumes ? 'existing' : 'new' ?>">
                        <div class="mb-3">
                            <label class="form-label">Cover Letter <span class="text-muted">(PDF/DOC/DOCX)</span></label>
                            <input type="file" name="cover_letter_file" class="form-control" accept=".pdf,.doc,.docx" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resume <span class="text-muted">(PDF/DOC/DOCX)</span></label>
                            <?php if ($has_resumes): ?>
                                <div class="mb-2">
                                    <select class="form-select mb-2" name="selected_resume_id" id="selected_resume_id">
                                        <option value="">-- Select from your saved resumes --</option>
                                        <?php foreach ($user_resumes as $resume): ?>
                                            <option value="<?= $resume['id'] ?>"><?= htmlspecialchars($resume['original_filename']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="text-muted small mb-2">Or upload a new resume below:</div>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="resume_file" class="form-control mb-2" accept=".pdf,.doc,.docx" <?= $has_resumes ? '' : 'required' ?> id="resume_file_input">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="save_resume_to_profile" id="save_resume_to_profile" checked>
                                <label class="form-check-label" for="save_resume_to_profile">
                                    Save this resume to my profile for future applications
                                </label>
                            </div>
                        </div>
                        <?php if (!empty($questions)): ?>
                            <div class="mb-3 p-3 bg-light rounded border">
                                <h6 class="fw-semibold mb-3">Additional Questions</h6>
                                <?php foreach ($questions as $idx => $q): ?>
                                    <div class="mb-2">
                                        <label class="form-label mb-1"><?= htmlspecialchars($q) ?></label>
                                        <input type="text" name="question_answers[<?= $idx ?>]" class="form-control" required>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="submit" class="btn btn-gradient px-4">
                                <i class="fas fa-paper-plane me-2"></i>Submit Application
                            </button>
                            <a href="job-details.php?jobid=<?= $jobid ?>" class="btn btn-outline-secondary px-4">Back</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Resume selection logic
const resumeTypeInput = document.getElementById('resume_type');
const resumeFileInput = document.getElementById('resume_file_input');
const resumeDropdown = document.getElementById('selected_resume_id');

if (resumeFileInput) {
    resumeFileInput.addEventListener('change', function() {
        if (resumeFileInput.value) {
            resumeTypeInput.value = 'new';
            if (resumeDropdown) resumeDropdown.value = '';
        }
    });
}
if (resumeDropdown) {
    resumeDropdown.addEventListener('change', function() {
        if (resumeDropdown.value) {
            resumeTypeInput.value = 'existing';
            if (resumeFileInput) resumeFileInput.value = '';
        }
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const resumeType = document.getElementById('resume_type').value;
    if (!resumeType || (resumeType !== 'existing' && resumeType !== 'new')) {
        e.preventDefault();
        alert('Please select a resume option.');
        return;
    }
    
    if (resumeType === 'new') {
        const fileInput = document.querySelector('input[name="resume_file"]');
        if (!fileInput || !fileInput.files[0]) {
            e.preventDefault();
            alert('Please select a resume file to upload.');
            return;
        }
        
        // Check file size (5MB limit)
        const fileSize = fileInput.files[0].size / 1024 / 1024; // Convert to MB
        if (fileSize > 5) {
            e.preventDefault();
            alert('Resume file size must be less than 5MB.');
            return;
        }
        
        // Check file type
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!allowedTypes.includes(fileInput.files[0].type)) {
            e.preventDefault();
            alert('Please upload a PDF, DOC, or DOCX file.');
            return;
        }
    }
    
    // Check cover letter
    const coverLetterInput = document.querySelector('input[name="cover_letter_file"]');
    if (!coverLetterInput || !coverLetterInput.files[0]) {
        e.preventDefault();
        alert('Please select a cover letter file.');
        return;
    }
    
    // Check cover letter file size (5MB limit)
    const coverLetterSize = coverLetterInput.files[0].size / 1024 / 1024; // Convert to MB
    if (coverLetterSize > 5) {
        e.preventDefault();
        alert('Cover letter file size must be less than 5MB.');
        return;
    }
    
    // Check cover letter file type
    const allowedCoverTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowedCoverTypes.includes(coverLetterInput.files[0].type)) {
        e.preventDefault();
        alert('Please upload a PDF, DOC, or DOCX file for the cover letter.');
        return;
    }
});
</script>
</body>
</html>