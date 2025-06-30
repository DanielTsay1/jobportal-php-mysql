<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $errstr]);
    exit;
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $error['message']]);
        exit;
    }
});

session_start();
require_once '../php/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    header('Location: login.php?error=unauthorized');
    exit;
}

$userid = $_SESSION['userid'];
$username = $_SESSION['username'];

// Fetch user profile information
$stmt = $conn->prepare("SELECT * FROM user WHERE userid = ?");
$stmt->bind_param("i", $userid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if user has a resume
$resume_stmt = $conn->prepare("SELECT resume FROM user WHERE userid = ?");
$resume_stmt->bind_param("i", $userid);
$resume_stmt->execute();
$resume_result = $resume_stmt->get_result()->fetch_assoc();
$has_resume = !empty($resume_result['resume']);
$resume_stmt->close();

// Check if user has been hired
$hired_stmt = $conn->prepare("SELECT j.designation, c.name as company_name 
                              FROM applied a 
                              JOIN `job-post` j ON a.jobid = j.jobid 
                              JOIN company c ON j.compid = c.compid 
                              WHERE a.userid = ? AND a.status = 'Hired' 
                              LIMIT 1");
$hired_stmt->bind_param("i", $userid);
$hired_stmt->execute();
$hired_result = $hired_stmt->get_result()->fetch_assoc();
$is_hired = !empty($hired_result);
$hired_stmt->close();

// Don't close connection here - it's needed for resume section
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($username) ?>'s Profile</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/profile.css" rel="stylesheet">
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
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 7rem 0 3rem;
            text-align: center;
        }

        .profile-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .profile-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            font-weight: 400;
        }

        .profile-container {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1000px;
            border: 1px solid var(--border-light);
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow-md);
        }

        .nav-tabs {
            border-bottom: 2px solid var(--border-light);
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            color: black !important;
            border: none;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            color: #2563eb !important;
            border-bottom: 3px solid var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.05);
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
        }

        .card {
            border: none;
            box-shadow: var(--shadow-md);
            border-radius: 18px;
            border: 1px solid var(--border-light);
            background: var(--bg-white);
        }

        .card-header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-light);
            border-radius: 18px 18px 0 0 !important;
            padding: 1.5rem 2rem;
            color: #000 !important;
        }

        .card-header h5, .card-header h5 i {
            color: #000 !important;
        }

        .card-body {
            padding: 2rem;
        }

        .upload-area {
            border: 2px dashed var(--border-light);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: var(--bg-light);
        }

        .upload-area:hover {
            border-color: var(--primary-blue);
            background-color: rgba(37, 99, 235, 0.05);
        }

        .resume-preview {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid var(--border-light);
        }

        .resume-name-container {
            flex: 1;
            min-width: 0;
        }

        .resume-name-display {
            font-weight: 500;
            color: var(--text-dark);
        }

        .resume-name-edit {
            max-width: 300px;
        }

        .resume-name-input {
            border: 2px solid var(--primary-blue);
            border-radius: 8px;
        }

        .resume-name-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px #2563eb22;
        }

        .btn-outline-info {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-info:hover {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
        }

        .btn-outline-danger {
            border-color: #ef4444;
            color: #ef4444;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background-color: #ef4444;
            border-color: #ef4444;
            color: white;
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--text-light);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
            border-radius: 12px;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #d97706;
            border-radius: 12px;
        }

        .list-group-item {
            border: 1px solid var(--border-light);
            background: var(--bg-white);
            padding: 1rem 1.5rem;
        }

        .list-group-item:first-child {
            border-radius: 12px 12px 0 0;
        }

        .list-group-item:last-child {
            border-radius: 0 0 12px 12px;
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        .section-divider {
            border-color: var(--border-light);
            opacity: 0.5;
        }

        /* Footer Styling - Same as my-applications.php */
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .change-photo-btn-linkedin {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-blue);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .change-photo-btn-linkedin:hover {
            background: var(--primary-blue-dark);
            transform: scale(1.1);
        }

        .profile-picture-linkedin-container {
            position: relative;
            display: inline-block;
        }

        .profile-picture-linkedin {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow-md);
        }

        .profile-header-linkedin {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 7rem 0 3rem;
            text-align: center;
        }

        .profile-header-linkedin-group {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-card-linkedin {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 18px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-info-linkedin h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: white;
        }

        .profile-info-row-linkedin {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .profile-info-row-linkedin i {
            margin-right: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .profile-card-linkedin {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .profile-info-linkedin h1 {
                font-size: 2rem;
            }

            .profile-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 600px) {
            .profile-title {
                font-size: 2.1rem;
            }

            .profile-container {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="profile-page" style="padding-top:68px;">
<?php include 'header-jobseeker.php'; ?>
    <div class="main-content" style="animation: fadeIn 0.7s cubic-bezier(.4,1.4,.6,1);">
    <?php if (!empty($user['suspended']) && $user['suspended'] == 1): ?>
    <!-- Suspension Banner -->
    <div class="container-fluid bg-danger text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <h2 class="mb-2">
                        <i class="fas fa-ban me-3"></i>
                        Your account is suspended
                    </h2>
                    <p class="mb-0 fs-5">
                        Reason: <strong><?= htmlspecialchars($user['suspension_reason'] ?? 'No reason provided.') ?></strong>
                    </p>
                    <p class="mb-0 fs-6 mt-2">
                        All your applications have been forcibly withdrawn.<br>
                        You cannot apply for jobs, upload resumes, or update your profile while suspended.<br>
                        For further actions, contact <a href="mailto:support@jobportal.com" class="text-white text-decoration-underline">support@jobportal.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <div class="profile-header-linkedin">
        <div class="container">
            <div class="profile-header-linkedin-group">
                <div class="profile-card-linkedin">
                    <div class="profile-picture-linkedin-container">
                        <img src="<?= htmlspecialchars($user['profile_picture'] ?? '/img/profile.png') ?>" 
                             alt="Profile Picture" class="profile-picture-linkedin">
                        <button class="change-photo-btn-linkedin" title="Change Photo" onclick="document.getElementById('profile-picture-input').click()">
                            <i class="fas fa-camera"></i>
                        </button>
                        <input type="file" id="profile-picture-input" accept="image/*" style="display: none;" onchange="uploadProfilePicture(this)">
                    </div>
                    <div class="profile-info-linkedin">
                        <h1 class="profile-username-linkedin mb-2"><?= htmlspecialchars($username) ?></h1>
                        <div class="profile-info-row-linkedin">
                            <i class="fas fa-envelope me-2"></i>
                            <span><?= htmlspecialchars($user['email'] ?? 'No email set') ?></span>
                        </div>
                        <div class="profile-info-row-linkedin">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span><?= htmlspecialchars($user['location'] ?? 'No location set') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab">Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="resume-tab" data-bs-toggle="tab" href="#resume" role="tab">Resume</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">Security</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="profileTabsContent">
            <!-- Personal Info Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-2">Personal Information</h5>
                        <hr class="section-divider mb-3">
                        <?php if ($is_hired): ?>
                        <!-- Employment Status Section -->
                        <div class="alert alert-success border-0 mb-3" role="alert">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-briefcase fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="alert-heading mb-1">Employment Status: Employed</h6>
                                    <p class="mb-2">
                                        <strong>Position:</strong> <?= htmlspecialchars($hired_result['designation']) ?><br>
                                        <strong>Company:</strong> <?= htmlspecialchars($hired_result['company_name']) ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Your profile shows you're currently employed. You can still update your information for future opportunities.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <form id="personalForm"<?= (!empty($user['suspended']) && $user['suspended'] == 1) ? ' style="pointer-events:none;opacity:0.6;"' : '' ?>>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="website" class="form-label">Website (Optional)</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?= htmlspecialchars($user['website'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label for="about" class="form-label">About Me</label>
                                    <textarea class="form-control" id="about" name="about" rows="3" placeholder="Tell employers about yourself..."><?= htmlspecialchars($user['about'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="education" class="form-label">Education</label>
                                    <textarea class="form-control" id="education" name="education" rows="3" placeholder="List your educational background..."><?= htmlspecialchars($user['education'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label for="experience" class="form-label">Work Experience</label>
                                    <textarea class="form-control" id="experience" name="experience" rows="3" placeholder="Describe your work experience..."><?= htmlspecialchars($user['experience'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Resume Tab -->
            <div class="tab-pane fade" id="resume" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-file-alt me-2"></i>Resume</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch and display resumes
                        $resumes_sql = "SELECT id, original_filename, filename FROM user_resumes WHERE user_id = ?";
                        $resumes_stmt = $conn->prepare($resumes_sql);
                        $resumes_stmt->bind_param("i", $userid);
                        $resumes_stmt->execute();
                        $resumes_result = $resumes_stmt->get_result();

                        if ($resumes_result->num_rows > 0) {
                            echo '<ul class="list-group list-group-flush">';
                            while ($resume = $resumes_result->fetch_assoc()) {
                                $resume_path = '/uploads/' . $resume['filename'];
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo '<div class="resume-name-container" data-resume-id="' . $resume['id'] . '">';
                                echo '<span class="resume-name-display">' . htmlspecialchars($resume['original_filename']) . '</span>';
                                echo '<div class="resume-name-edit" style="display: none;">';
                                echo '<input type="text" class="form-control form-control-sm resume-name-input" value="' . htmlspecialchars($resume['original_filename']) . '" maxlength="100">';
                                echo '<div class="mt-1">';
                                echo '<button class="btn btn-sm btn-success save-resume-name-btn me-1"><i class="fas fa-check"></i> Save</button>';
                                echo '<button class="btn btn-sm btn-secondary cancel-resume-name-btn"><i class="fas fa-times"></i> Cancel</button>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                echo '<div>';
                                echo '<button class="btn btn-sm btn-outline-info me-2 rename-resume-btn" data-resume-id="' . $resume['id'] . '"><i class="fas fa-edit"></i> Rename</button>';
                                echo '<a href="' . $resume_path . '" target="_blank" class="btn btn-sm btn-outline-primary me-2"><i class="fas fa-eye"></i> View</a>';
                                echo '<button class="btn btn-sm btn-outline-danger delete-resume-btn" data-resume-id="' . $resume['id'] . '"><i class="fas fa-trash"></i> Delete</button>';
                                echo '</div>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-muted">No resume uploaded yet.</p>';
                        }
                        $resumes_stmt->close();
                        ?>
                        <hr>
                        <form id="resume-upload-form" class="mt-3" enctype="multipart/form-data"<?= (!empty($user['suspended']) && $user['suspended'] == 1) ? ' style="pointer-events:none;opacity:0.6;"' : '' ?>>
                            <div class="mb-3">
                                <label for="resume-file" class="form-label">Upload New Resume</label>
                                <input type="file" class="form-control" id="resume-file" name="resume" accept=".pdf,.doc,.docx" required>
                                <div class="form-text">Only PDF, DOC, and DOCX files are allowed (max 5MB).</div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i>Upload</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Change Password</h5>
                        <form id="passwordForm"<?= (!empty($user['suspended']) && $user['suspended'] == 1) ? ' style="pointer-events:none;opacity:0.6;"' : '' ?>>
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <?php include 'footer.php'; ?>
    <!-- jQuery (before Bootstrap JS) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tabs and handle URL hash
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tabs
            var triggerTabList = [].slice.call(document.querySelectorAll('#profileTabs a'));
            triggerTabList.forEach(function (triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl);
                
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
            
            // Handle URL hash for tab switching
            if (window.location.hash) {
                var hash = window.location.hash.substring(1);
                var targetTab = document.querySelector('#profileTabs a[href="#' + hash + '"]');
                if (targetTab) {
                    var tab = new bootstrap.Tab(targetTab);
                    tab.show();
                }
            }
            
            // Update URL hash when tabs are clicked
            triggerTabList.forEach(function(triggerEl) {
                triggerEl.addEventListener('shown.bs.tab', function (event) {
                    var hash = event.target.getAttribute('href');
                    if (hash) {
                        window.location.hash = hash;
                    }
                });
            });
        });

        // Personal Info Form Submission
        document.getElementById('personalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_personal');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            submitBtn.disabled = true;
            
            fetch('../php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showAlert('Profile updated successfully!', 'success');
                        // Update the displayed information without page reload
                        updateDisplayedInfo();
                    } else {
                        showAlert(data.message || 'Failed to update profile', 'danger');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response:', text);
                    showAlert('Server returned invalid response. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Function to update displayed information after successful update
        function updateDisplayedInfo() {
            // Update the header information
            const email = document.getElementById('email').value;
            const location = document.getElementById('location').value;
            
            // Update the profile header
            const headerEmail = document.querySelector('.profile-header p:first-of-type');
            const headerLocation = document.querySelector('.profile-header p:last-of-type');
            
            if (headerEmail) {
                headerEmail.innerHTML = '<i class="fas fa-envelope me-2"></i>' + (email || 'No email set');
            }
            if (headerLocation) {
                headerLocation.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>' + (location || 'No location set');
            }
        }

        // Password Form Submission
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showAlert('New passwords do not match!', 'danger');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'change_password');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing...';
            submitBtn.disabled = true;
            
            fetch('../php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showAlert('Password changed successfully!', 'success');
                        this.reset();
                    } else {
                        showAlert(data.message || 'Failed to change password', 'danger');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response:', text);
                    showAlert('Server returned invalid response. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Profile Picture Upload
        function uploadProfilePicture(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', input.files[0]);
                formData.append('action', 'upload_picture');
                
                // Show loading state
                const uploadBtn = input.parentElement.querySelector('button');
                const originalText = uploadBtn.innerHTML;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
                uploadBtn.disabled = true;
                
                fetch('../php/update-profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            showAlert('Profile picture updated successfully!', 'success');
                            // Refresh the page to show new picture
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showAlert(data.message || 'Failed to upload picture', 'danger');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Raw response:', text);
                        showAlert('Server returned invalid response. Please try again.', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                })
                .finally(() => {
                    // Restore button state
                    uploadBtn.innerHTML = originalText;
                    uploadBtn.disabled = false;
                });
            }
        }

        // Resume Upload
        $('#resume-upload-form').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('action', 'upload_resume');

            // Show loading state
            var submitBtn = $(this).find('button[type="submit"]');
            var originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Uploading...');
            submitBtn.prop('disabled', true);

            fetch('../php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response:', text);
                    showAlert('Server returned invalid response. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            });
        });

        // Handle resume deletion
        $('.delete-resume-btn').click(function() {
            var resumeId = $(this).data('resume-id');
            if (confirm('Are you sure you want to delete this resume?')) {
                var deleteBtn = $(this);
                var originalText = deleteBtn.html();
                deleteBtn.html('<i class="fas fa-spinner fa-spin"></i>');
                deleteBtn.prop('disabled', true);
                
                const formData = new FormData();
                formData.append('action', 'delete_resume');
                formData.append('resume_id', resumeId);
                
                fetch('../php/update-profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Response text:', text);
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            showAlert(data.message, 'success');
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            showAlert(data.message, 'danger');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Raw response:', text);
                        showAlert('Server returned invalid response. Please try again.', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                })
                .finally(() => {
                    // Restore button state
                    deleteBtn.html(originalText);
                    deleteBtn.prop('disabled', false);
                });
            }
        });

        // Handle resume rename functionality
        $(document).on('click', '.rename-resume-btn', function() {
            var resumeId = $(this).data('resume-id');
            var container = $('.resume-name-container[data-resume-id="' + resumeId + '"]');
            var display = container.find('.resume-name-display');
            var edit = container.find('.resume-name-edit');
            var input = container.find('.resume-name-input');
            
            // Store original name for cancel
            input.data('original-name', input.val());
            
            // Show edit mode
            display.hide();
            edit.show();
            input.focus();
        });

        // Handle save resume name
        $(document).on('click', '.save-resume-name-btn', function() {
            var container = $(this).closest('.resume-name-container');
            var resumeId = container.data('resume-id');
            var input = container.find('.resume-name-input');
            var newName = input.val().trim();
            var originalName = input.data('original-name');
            
            if (!newName) {
                showAlert('Resume name cannot be empty', 'danger');
                return;
            }
            
            if (newName === originalName) {
                // No change, just cancel edit mode
                cancelResumeNameEdit(container);
                return;
            }
            
            // Show loading state
            var saveBtn = $(this);
            var originalText = saveBtn.html();
            saveBtn.html('<i class="fas fa-spinner fa-spin"></i>');
            saveBtn.prop('disabled', true);
            
            const formData = new FormData();
            formData.append('action', 'rename_resume');
            formData.append('resume_id', resumeId);
            formData.append('new_name', newName);
            
            fetch('../php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        // Update the display name
                        container.find('.resume-name-display').text(newName);
                        cancelResumeNameEdit(container);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response:', text);
                    showAlert('Server returned invalid response. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Restore button state
                saveBtn.html(originalText);
                saveBtn.prop('disabled', false);
            });
        });

        // Handle cancel resume name edit
        $(document).on('click', '.cancel-resume-name-btn', function() {
            var container = $(this).closest('.resume-name-container');
            cancelResumeNameEdit(container);
        });

        // Function to cancel resume name editing
        function cancelResumeNameEdit(container) {
            var display = container.find('.resume-name-display');
            var edit = container.find('.resume-name-edit');
            var input = container.find('.resume-name-input');
            var originalName = input.data('original-name');
            
            // Restore original name
            input.val(originalName);
            
            // Hide edit mode
            display.show();
            edit.hide();
        }

        // Handle Enter key in resume name input
        $(document).on('keypress', '.resume-name-input', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $(this).closest('.resume-name-edit').find('.save-resume-name-btn').click();
            }
        });

        // Handle Escape key in resume name input
        $(document).on('keydown', '.resume-name-input', function(e) {
            if (e.which === 27) { // Escape key
                e.preventDefault();
                $(this).closest('.resume-name-edit').find('.cancel-resume-name-btn').click();
            }
        });

        // Show Alert Function
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Enable Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection at the end
$conn->close();
?>