<?php
session_start();
require_once '../php/db.php';

if (!isset($_SESSION['recid']) || !isset($_SESSION['compid'])) {
    header("Location: ../main/login.php");
    exit;
}

$compid = $_SESSION['compid'];

// Fetch company information including suspension status
$company_stmt = $conn->prepare("SELECT name, suspended, suspension_reason FROM company WHERE compid = ?");
$company_stmt->bind_param("i", $compid);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company = $company_result->fetch_assoc();
$company_stmt->close();

// Check if company is suspended
$is_suspended = !empty($company['suspended']) && $company['suspended'] == 1;

// Fetch company name for display and saving
$company_name = $company['name'] ?? '';

$jobPosted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_suspended) {
    $designation = trim($_POST['designation'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $status = 'Pending';
    $questions = $_POST['questions'] ?? [];
    $questions_json = json_encode(array_filter(array_map('trim', $questions)));

    $stmt = $conn->prepare("INSERT INTO `job-post` (company, compid, designation, description, location, salary, status, created_at, questions) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("sisssiss", $company_name, $compid, $designation, $description, $location, $salary, $status, $questions_json);
    if ($stmt->execute()) {
        $jobPosted = true;
    } else {
        $error = "Error posting job.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Post a Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', Arial, sans-serif !important;
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
        }
        .main-content {
            flex: 1 0 auto;
        }
        .post-job-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .post-job-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(30, 144, 255, 0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 900px;
            width: 100%;
            margin: 2rem auto;
            opacity: 0;
            transform: translateY(40px);
            animation: fadeSlideIn 0.7s cubic-bezier(.4,1.4,.6,1) 0.1s forwards;
        }
        .post-job-card h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
            text-align: center;
        }
        .divider {
            border-top: 1.5px solid #e9ecef;
            margin: 2rem 0 1.5rem 0;
        }
        .form-label {
            font-weight: 500;
            color: #2B3940;
            margin-bottom: 0.25rem;
        }
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1E90FF;
            box-shadow: 0 0 0 0.15rem rgba(30, 144, 255, 0.10);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1976d2 0%, #1E90FF 100%);
            border: none;
            border-radius: 20px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            box-shadow: 0 2px 8px rgba(30, 144, 255, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #1565c0 0%, #1976d2 100%);
            box-shadow: 0 4px 16px rgba(30, 144, 255, 0.12);
        }
        .btn-outline-secondary, .btn-outline-danger {
            border-radius: 20px;
            font-weight: 500;
        }
        .alert {
            border-radius: 12px;
            font-size: 1rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 992px) {
            .post-job-card {
                max-width: 98vw;
                padding: 2rem 1rem 1.5rem 1rem;
            }
        }
        @media (max-width: 576px) {
            .post-job-card {
                padding: 1.5rem 0.5rem 1rem 0.5rem;
            }
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
        footer {
            flex-shrink: 0;
            width: 100vw;
        }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>

<?php if ($is_suspended): ?>
    <div class="alert alert-danger text-center" style="font-size:1.1rem; font-weight:600; margin: 2rem auto; max-width: 800px;">
        <i class="fas fa-ban me-2"></i>
        <strong>Job Posting Disabled</strong><br>
        Your company is currently <b>suspended</b> and cannot post new jobs.<br>
        <span>Reason: <?= htmlspecialchars($company['suspension_reason'] ?? 'No reason provided.') ?></span><br>
        <small class="mt-2 d-block">Please contact the administrator to resolve this issue.</small>
    </div>
<?php endif; ?>

<div class="post-job-container main-content">
    <div class="post-job-card">
        <h2><i class="fa fa-briefcase me-2"></i>Post a Job</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'suspended'): ?>
            <div class="alert alert-danger">
                <i class="fas fa-ban me-2"></i>
                <strong>Job Posting Blocked</strong><br>
                Your company is currently suspended and cannot post new jobs.<br>
                <span>Reason: <?= htmlspecialchars($_GET['reason'] ?? 'No reason provided.') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($jobPosted): ?>
            <div class="alert alert-success">Job posted successfully!</div>
            <a href="recruiter-dashboard.php" class="btn btn-secondary">Back</a>
        <?php elseif ($is_suspended): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Job posting is currently disabled due to company suspension.
            </div>
            <a href="recruiter.php" class="btn btn-primary">Back to Dashboard</a>
        <?php else: ?>
        <form action="/php/postjob.php" method="POST" id="postJobForm" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="designation" name="designation" placeholder="Job Title" required>
                        <label for="designation">Job Title / Designation</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="location" name="location" placeholder="Location" required>
                        <label for="location">Location (e.g., City, State)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" class="form-control" id="salary" name="salary" placeholder="Annual Salary" required>
                        <label for="salary">Annual Salary (USD)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" class="form-control" id="spots" name="spots" placeholder="Available Spots" value="1" min="1" required>
                        <label for="spots">Number of Available Spots</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="employment_type" name="employment_type" required>
                            <option value="" selected disabled>Select Employment Type</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                            <option value="Temporary">Temporary</option>
                        </select>
                        <label for="employment_type">Employment Type</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="work_mode" name="work_mode" required>
                            <option value="" selected disabled>Select Work Mode</option>
                            <option value="On-site">On-site</option>
                            <option value="Remote">Remote</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                        <label for="work_mode">Work Mode</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="experience_level" name="experience_level" required>
                            <option value="" selected disabled>Select Experience Level</option>
                            <option value="Entry">Entry</option>
                            <option value="Mid">Mid</option>
                            <option value="Senior">Senior</option>
                            <option value="Director">Director</option>
                        </select>
                        <label for="experience_level">Experience Level</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="date" class="form-control" id="deadline" name="deadline">
                        <label for="deadline">Application Deadline</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Job Description" id="description" name="description" style="height: 150px" required></textarea>
                        <label for="description">Job Description</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control" placeholder="Benefits" id="benefits" name="benefits" style="height: 80px"></textarea>
                        <label for="benefits">Benefits (optional)</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="skills" name="skills" placeholder="Required Skills">
                        <label for="skills">Required Skills (comma-separated)</label>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="col-12">
                    <h5>Screening Questions (Optional)</h5>
                    <div id="questions-container">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="questions[]" placeholder="e.g., How many years of experience do you have?">
                            <button class="btn btn-outline-danger" type="button" onclick="removeQuestion(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addQuestion()">+ Add another question</button>
                </div>
                <div class="col-12 text-center mt-3">
                    <button class="btn btn-primary py-3 px-5" type="submit">Post Job</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<script>
function addQuestion() {
    const div = document.createElement('div');
    div.innerHTML = '<div class="input-group mb-3"><input type="text" class="form-control" name="questions[]" placeholder="Another question"><button class="btn btn-outline-danger" type="button" onclick="removeQuestion(this)">Remove</button></div>';
    document.getElementById('questions-container').appendChild(div);
}

function removeQuestion(button) {
    const div = button.parentElement;
    div.remove();
}
</script>
    <footer style="width:100vw; background: linear-gradient(90deg, #e3f0ff 0%, #ede7f6 100%); border-top: 1.5px solid #e3f0ff; margin-top:2rem; padding: 1.5rem 0 1rem 0; text-align:center; font-size:1rem; color:#1976d2;">
      <div style="font-weight:600; letter-spacing:-0.5px; font-size:1.2rem;">
        <i class="fas fa-envelope me-2" style="color:#7b1fa2;"></i>Contact us: <a href="mailto:support@jobportal.com" style="color:#1976d2; text-decoration:underline;">support@jobportal.com</a>
      </div>
      <div style="margin-top:0.5rem; color:#7b1fa2; font-size:1rem;">
        <i class="fas fa-phone me-2"></i>+1 (800) 123-4567
      </div>
      <div style="margin-top:0.5rem; color:#1976d2; font-size:0.98rem;">
        &copy; <?= date('Y') ?> <span style="color:#1976d2;">Job</span><span style="color:#7b1fa2;">Portal</span> &mdash; Your gateway to new opportunities
      </div>
    </footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>