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
        .resume-option {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .resume-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .resume-option.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        .resume-option input[type="radio"] {
            margin-right: 10px;
        }
        .upload-new-resume {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-new-resume:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .upload-new-resume.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Apply for <?= htmlspecialchars($job['designation'] ?? 'Job') ?> at <?= htmlspecialchars($job['company_name'] ?? '') ?></h2>
    <?php if (!$job): ?>
        <div class="alert alert-danger">Job not found.</div>
    <?php else: ?>
    <form method="post" action="/php/apply.php" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <input type="hidden" name="jobid" value="<?= $jobid ?>">
        
        <div class="mb-3">
            <label class="form-label">Cover Letter (PDF/DOC/DOCX)</label>
            <input type="file" name="cover_letter_file" class="form-control" accept=".pdf,.doc,.docx" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Resume Selection</label>
            
            <?php if ($has_resumes): ?>
                <div class="mb-3">
                    <h6>Select from your existing resumes:</h6>
                    <?php foreach ($user_resumes as $index => $resume): ?>
                        <div class="resume-option <?= $index === 0 ? 'selected' : '' ?>" data-resume-id="<?= $resume['id'] ?>" onclick="selectResume('existing', <?= $resume['id'] ?>)">
                            <input type="radio" name="resume_type" value="existing" <?= $index === 0 ? 'checked' : '' ?>>
                            <i class="fas fa-file-pdf me-2"></i>
                            <strong><?= htmlspecialchars($resume['original_filename']) ?></strong>
                            <small class="text-muted d-block ms-4">
                                Uploaded: <?= date('M j, Y', filemtime('../uploads/' . $resume['filename'])) ?> | 
                                Size: <?= number_format(filesize('../uploads/' . $resume['filename']) / 1024, 1) ?> KB
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mb-3">
                    <h6>Or upload a new resume:</h6>
                    <div class="upload-new-resume" onclick="selectResume('new')">
                        <input type="radio" name="resume_type" value="new">
                        <i class="fas fa-upload fa-2x mb-2"></i>
                        <div><strong>Upload New Resume</strong></div>
                        <small class="text-muted">PDF, DOC, or DOCX (max 5MB)</small>
                    </div>
                </div>
                
                <div id="new-resume-upload" style="display: none;">
                    <input type="file" name="resume_file" class="form-control" accept=".pdf,.doc,.docx">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="save_resume_to_profile" id="save_resume_to_profile" checked>
                        <label class="form-check-label" for="save_resume_to_profile">
                            Save this resume to my profile for future applications.
                        </label>
                    </div>
                </div>
                
                <!-- Hidden input for selected resume ID -->
                <input type="hidden" name="selected_resume_id" id="selected_resume_id" value="<?= $user_resumes[0]['id'] ?? '' ?>">
                
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No resumes found in your profile. Please upload a resume to apply.
                </div>
                <input type="file" name="resume_file" class="form-control" accept=".pdf,.doc,.docx" required>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="save_resume_to_profile" id="save_resume_to_profile" checked>
                    <label class="form-check-label" for="save_resume_to_profile">
                        Save this resume to my profile for future applications.
                    </label>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($questions)): ?>
            <hr>
            <h5>Additional Questions</h5>
            <?php foreach ($questions as $idx => $q): ?>
                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars($q) ?></label>
                    <input type="text" name="question_answers[<?= $idx ?>]" class="form-control" required>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-primary">Submit Application</button>
        <a href="job-details.php?jobid=<?= $jobid ?>" class="btn btn-secondary ms-2">Back</a>
    </form>
    <?php endif; ?>
</div>

<script>
function selectResume(type, resumeId = null) {
    // Remove selected class from all options
    document.querySelectorAll('.resume-option, .upload-new-resume').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    if (type === 'existing') {
        // Find the clicked option by resumeId
        const clickedOption = document.querySelector(`.resume-option[data-resume-id="${resumeId}"]`);
        if (clickedOption) {
            clickedOption.classList.add('selected');
        }
        document.getElementById('new-resume-upload').style.display = 'none';
        
        // Update the hidden input with the selected resume ID
        document.getElementById('selected_resume_id').value = resumeId;
        
        // Uncheck all existing radio buttons and check the one in the clicked option
        document.querySelectorAll('input[name="resume_type"][value="existing"]').forEach(radio => {
            radio.checked = false;
        });
        
        // Find the radio button in the clicked option and check it
        const radioInOption = clickedOption.querySelector('input[type="radio"]');
        if (radioInOption) {
            radioInOption.checked = true;
        }
        
    } else {
        document.querySelector('.upload-new-resume').classList.add('selected');
        document.getElementById('new-resume-upload').style.display = 'block';
        
        // Clear the selected resume ID
        document.getElementById('selected_resume_id').value = '';
        
        // Uncheck all existing radio buttons and check the new one
        document.querySelectorAll('input[name="resume_type"][value="existing"]').forEach(radio => {
            radio.checked = false;
        });
        document.querySelector('input[name="resume_type"][value="new"]').checked = true;
    }
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const resumeType = document.querySelector('input[name="resume_type"]:checked');
    if (!resumeType) {
        e.preventDefault();
        alert('Please select a resume option.');
        return;
    }
    
    if (resumeType.value === 'new') {
        const fileInput = document.querySelector('input[name="resume_file"]');
        if (!fileInput.files[0]) {
            e.preventDefault();
            alert('Please select a resume file to upload.');
            return;
        }
    }
});
</script>
</body>
</html>