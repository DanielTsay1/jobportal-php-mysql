<?php
session_start();
require_once '../php/db.php';

$compid = $_SESSION['compid'] ?? null;
$recid = $_SESSION['recid'] ?? null;
$username = $_SESSION['username'] ?? '';
if (!$compid || !$recid) {
    header("Location: ../main/login.html");
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recruiter && $company) {
    $company_name = $_POST['company_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $website = $_POST['website'] ?? '';
    $about = $_POST['about'] ?? '';
    $email = $_POST['email'] ?? '';

    // Update recruiter email by recid
    $stmt = $conn->prepare("UPDATE recruiter SET email = ? WHERE recid = ?");
    $stmt->bind_param("si", $email, $recid);
    $stmt->execute();
    $stmt->close();

    // Update company by compid
    $stmt = $conn->prepare("UPDATE company SET name = ?, location = ?, contact = ?, website = ?, about = ? WHERE compid = ?");
    $stmt->bind_param("ssissi", $company_name, $location, $contact, $website, $about, $compid);
    $stmt->execute();
    $stmt->close();

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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Company Info</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', 'Inter', Arial, sans-serif !important;
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .edit-company-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .edit-company-card {
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
        .edit-company-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
            text-align: center;
        }
        .edit-company-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1976d2;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .form-label {
            font-weight: 500;
            color: #2B3940;
            margin-bottom: 0.25rem;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
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
        .btn-secondary {
            border-radius: 20px;
            font-weight: 500;
            padding: 0.6rem 1.5rem;
        }
        .divider {
            border-top: 1.5px solid #e9ecef;
            margin: 2rem 0 1.5rem 0;
        }
        .alert {
            border-radius: 12px;
            font-size: 1rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 992px) {
            .edit-company-card {
                max-width: 98vw;
                padding: 2rem 1rem 1.5rem 1rem;
            }
        }
        @media (max-width: 576px) {
            .edit-company-card {
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
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>
<div class="edit-company-container">
    <form method="post" class="edit-company-card">
        <h2><i class="fa fa-edit me-2"></i>Edit Company & Contact Info</h2>
        <?= $message ?>
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
        <div class="divider"></div>
        <h5>Recruiter Contact Info</h5>
        <div class="mb-3">
            <label class="form-label">Recruiter Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($recruiter['email'] ?? '') ?>" required>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="recruiter.php" class="btn btn-secondary ms-2">Go Back</a>
        </div>
    </form>
</div>
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