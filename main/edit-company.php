<?php
session_start();
require_once '../php/db.php';

$compid = $_SESSION['compid'] ?? null;
$recid = $_SESSION['recid'] ?? null;
$username = $_SESSION['username'] ?? '';
if (!$compid || !$recid) {
    header("Location: ../main/login.php");
    exit;
}

// Fetch recruiter info
$stmt = $conn->prepare("SELECT * FROM recruiter WHERE recid = ?");
$stmt->bind_param("i", $recid);
$stmt->execute();
$recruiter = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch company info by compid
$stmt = $conn->prepare("SELECT * FROM company WHERE compid = ?");
$stmt->bind_param("i", $compid);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

$message = '';
$is_new_setup = false;

// Check if this is a new setup (company name contains "Company" and location is "Location to be updated")
if ($company && strpos($company['name'], "'s Company") !== false && $company['location'] === "Location to be updated") {
    $is_new_setup = true;
    $message = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Welcome!</strong> Please complete your company profile to get started.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recruiter && $company) {
    $company_name = $_POST['company_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $website = $_POST['website'] ?? '';
    $about = $_POST['about'] ?? '';
    $email = $_POST['email'] ?? '';
    $industry = $_POST['industry'] ?? '';

    // Update recruiter email by recid
    $stmt = $conn->prepare("UPDATE recruiter SET email = ? WHERE recid = ?");
    $stmt->bind_param("si", $email, $recid);
    $stmt->execute();
    $stmt->close();

    // Update company by compid
    $stmt = $conn->prepare("UPDATE company SET name = ?, location = ?, contact = ?, website = ?, about = ?, industry = ? WHERE compid = ?");
    $stmt->bind_param("ssssssi", $company_name, $location, $contact, $website, $about, $industry, $compid);
    $stmt->execute();
    $stmt->close();

    // Check if this was a new setup that was just completed
    $was_new_setup = $is_new_setup;
    
    $message = '<div class="alert alert-success">Company and contact info updated!</div>';

    // Refresh info
    $stmt = $conn->prepare("SELECT * FROM recruiter WHERE recid = ?");
    $stmt->bind_param("i", $recid);
    $stmt->execute();
    $recruiter = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM company WHERE compid = ?");
    $stmt->bind_param("i", $compid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // If this was a new setup, redirect to recruiter dashboard after a short delay
    if ($was_new_setup) {
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><strong>Setup Complete!</strong> Redirecting to your dashboard...</div>';
        echo '<script>setTimeout(function() { window.location.href = "recruiter.php"; }, 2000);</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $is_new_setup ? 'Complete Company Setup' : 'Edit Company Info' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .card, .main-content, .empty-state {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
        }

        .form-control, .form-select {
            background: var(--bg-light) !important;
            color: var(--text-dark) !important;
            border: 2px solid var(--border-light) !important;
            border-radius: 12px !important;
            font-size: 1rem;
            font-weight: 500;
            box-shadow: var(--shadow-md);
            transition: border 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
            margin-bottom: 0.2rem;
            padding: 0.85rem 1.2rem;
        }

        .form-control:focus, .form-select:focus {
            background: var(--bg-white) !important;
            color: var(--text-dark) !important;
            border-color: var(--primary-blue) !important;
            box-shadow: 0 0 0 2px #2563eb22 !important;
        }

        .btn, .btn-modern {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .btn:hover, .btn-modern:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            color: #fff;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
        }

        .btn-outline:hover {
            background: var(--primary-blue);
            color: #fff;
        }

        .empty-state {
            color: var(--text-light);
            text-align: center;
            padding: 4rem 2rem;
            margin: 2rem auto;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary-blue);
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .glass-panel {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            padding: 2.5rem 2rem 2rem 2rem;
            margin: 3rem auto 2rem auto;
            max-width: 700px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--primary-blue);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }

        .alert a {
            color: inherit;
            text-decoration: underline;
        }

        .alert a:hover {
            text-decoration: none;
        }

        .text-muted {
            color: var(--text-light) !important;
        }

        .border-secondary {
            border-color: var(--border-light) !important;
        }

        .text-secondary {
            color: var(--text-light) !important;
        }

        @media (max-width: 768px) {
            .glass-panel {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body style="padding-top:68px;">
<?php include 'header-recruiter.php'; ?>
<div class="container">
  <div class="glass-panel" style="max-width: 700px; margin: 3rem auto 2rem auto; padding: 2.5rem 2rem 2rem 2rem;">
    <form method="post">
      <h2 class="mb-4" style="font-weight:700;"><i class="fa fa-edit me-2"></i><?= $is_new_setup ? 'Complete Company Setup' : 'Edit Company & Contact Info' ?></h2>
      <?= $message ?>
      <?php if (!empty($company['suspended']) && $company['suspended'] == 1): ?>
        <div class="alert alert-danger" style="font-size:1.1rem; font-weight:600;">
          <i class="fas fa-ban me-2"></i>
          Your company is currently <b>suspended</b>.<br>
          <span>Reason: <?= htmlspecialchars($company['suspension_reason'] ?? 'No reason provided.') ?></span><br>
          <span>Contact Support: <a href="mailto:JobPortalSupport@gmail.com" style="color: #dc3545; text-decoration: underline;">JobPortalSupport@gmail.com</a></span>
        </div>
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">Company Name</label>
        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($company['location'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Contact Phone</label>
        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($company['contact'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">About Company</label>
        <textarea name="about" class="form-control" rows="4"><?= htmlspecialchars($company['about'] ?? '') ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Industry</label>
        <input type="text" name="industry" class="form-control" value="<?= htmlspecialchars($company['industry'] ?? '') ?>" placeholder="e.g. Technology, Healthcare, Education">
      </div>
      <div class="divider my-4"></div>
      <h5 class="mb-3">Recruiter Contact Info</h5>
      <div class="mb-3">
        <label class="form-label">Recruiter Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($recruiter['email'] ?? '') ?>" required>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-4">
        <button type="submit" class="btn btn-primary px-4"><?= $is_new_setup ? 'Complete Setup' : 'Save Changes' ?></button>
      </div>
    </form>
  </div>
</div>
    <?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Floating Chat Button -->
<a href="https://www.stack-ai.com/chat/68623c004fe0ebb9c4eaeec8-6jBGBvdYxWKz2625u0mQhn" target="_blank" rel="noopener" id="floatingChatBtn" title="Chat with JobPortal AI Agent">
  <i class="fas fa-comments"></i>
</a>
<style>
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
</body>
</html>