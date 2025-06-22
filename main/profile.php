<?php
session_start();
require_once '../php/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    header('Location: login.html?error=unauthorized');
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

$conn->close();
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
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
        }
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: none;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .resume-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header-jobseeker.php'; ?>
    
    <?php if ($is_hired): ?>
    <!-- Congratulations Banner -->
    <div class="container-fluid bg-success text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-trophy me-3"></i>
                        Congratulations on Your New Job!
                    </h2>
                    <p class="mb-0 fs-5">
                        You've been hired as <strong><?= htmlspecialchars($hired_result['designation']) ?></strong> 
                        at <strong><?= htmlspecialchars($hired_result['company_name']) ?></strong>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-column align-items-md-end">
                        <span class="badge bg-light text-success fs-6 mb-2 px-3 py-2">
                            <i class="fas fa-check-circle me-2"></i>Hired
                        </span>
                        <small class="text-light">Your profile is now marked as employed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?= htmlspecialchars($user['profile_picture'] ?? '/img/profile.png') ?>" 
                         alt="Profile Picture" class="profile-picture mb-3">
                    <button class="btn btn-light btn-sm" onclick="document.getElementById('profile-picture-input').click()">
                        <i class="fas fa-camera me-2"></i>Change Photo
                    </button>
                    <input type="file" id="profile-picture-input" accept="image/*" style="display: none;" onchange="uploadProfilePicture(this)">
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2"><?= htmlspecialchars($username) ?></h1>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email'] ?? 'No email set') ?></p>
                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($user['location'] ?? 'No location set') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                    <i class="fas fa-user me-2"></i>Personal Info
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resume-tab" data-bs-toggle="tab" data-bs-target="#resume" type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Resume
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                    <i class="fas fa-shield-alt me-2"></i>Security
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="profileTabsContent">
            <!-- Personal Info Tab -->
            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Personal Information</h5>
                        
                        <?php if ($is_hired): ?>
                        <!-- Employment Status Section -->
                        <div class="alert alert-success border-0 mb-4" role="alert">
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
                        
                        <form id="personalForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">Website (Optional)</label>
                                    <input type="url" class="form-control" id="website" name="website" 
                                           value="<?= htmlspecialchars($user['website'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="about" class="form-label">About Me</label>
                                <textarea class="form-control" id="about" name="about" rows="4" 
                                          placeholder="Tell employers about yourself..."><?= htmlspecialchars($user['about'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="education" class="form-label">Education</label>
                                <textarea class="form-control" id="education" name="education" rows="4" 
                                          placeholder="List your educational background..."><?= htmlspecialchars($user['education'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="experience" class="form-label">Work Experience</label>
                                <textarea class="form-control" id="experience" name="experience" rows="4" 
                                          placeholder="Describe your work experience..."><?= htmlspecialchars($user['experience'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Resume Tab -->
            <div class="tab-pane fade" id="resume" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Resume Management</h5>
                        
                        <?php if ($has_resume): ?>
                            <div class="resume-preview">
                                <h6><i class="fas fa-file-pdf me-2 text-danger"></i>Current Resume</h6>
                                <p class="mb-2"><?= htmlspecialchars($resume_result['resume']) ?></p>
                                <a href="/uploads/<?= htmlspecialchars($resume_result['resume']) ?>" 
                                   class="btn btn-outline-primary btn-sm me-2" target="_blank">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteResume()">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>Upload Your Resume</h5>
                            <p class="text-muted">Drag and drop your resume here or click to browse</p>
                            <p class="text-muted small">Supported formats: PDF, DOC, DOCX (Max 5MB)</p>
                            <input type="file" id="resumeInput" accept=".pdf,.doc,.docx" style="display: none;" onchange="uploadResume(this)">
                            <button class="btn btn-primary" onclick="document.getElementById('resumeInput').click()">
                                <i class="fas fa-upload me-2"></i>Choose File
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Change Password</h5>
                        <form id="passwordForm">
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

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Personal Info Form Submission
        document.getElementById('personalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_personal');
            
            fetch('/php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Profile updated successfully!', 'success');
                } else {
                    showAlert(data.message || 'Failed to update profile', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });

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
            
            fetch('/php/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Password changed successfully!', 'success');
                    this.reset();
                } else {
                    showAlert(data.message || 'Failed to change password', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });

        // Profile Picture Upload
        function uploadProfilePicture(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', input.files[0]);
                formData.append('action', 'upload_picture');
                
                fetch('/php/update-profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Profile picture updated successfully!', 'success');
                        // Refresh the page to show new picture
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Failed to upload picture', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
            }
        }

        // Resume Upload
        function uploadResume(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('resume', input.files[0]);
                formData.append('action', 'upload_resume');
                
                fetch('/php/update-profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Resume uploaded successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Failed to upload resume', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
            }
        }

        // Delete Resume
        function deleteResume() {
            if (!confirm('Are you sure you want to delete your resume?')) {
                return;
            }
            
            fetch('/php/update-profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_resume'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Resume deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message || 'Failed to delete resume', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        }

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

        // Drag and drop for resume upload
        const uploadArea = document.getElementById('uploadArea');
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#667eea';
            uploadArea.style.backgroundColor = '#f8f9ff';
        });
        
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#dee2e6';
            uploadArea.style.backgroundColor = 'transparent';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#dee2e6';
            uploadArea.style.backgroundColor = 'transparent';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('resumeInput').files = files;
                uploadResume(document.getElementById('resumeInput'));
            }
        });
    </script>
</body>
</html>