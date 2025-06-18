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
</head>
<body>
<?php include 'header-recruiter.php'; ?>
<div class="container py-4">
    <h2 class="mb-4"><i class="fa fa-edit"></i> Edit Company & Contact Info</h2>
    <?= $message ?>
    <form method="post" class="card p-4 shadow-sm">
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
        <hr>
        <h5>Recruiter Contact Info</h5>
        <div class="mb-3">
            <label class="form-label">Recruiter Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($recruiter['email'] ?? '') ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="recruiter.php" class="btn btn-secondary ms-2">Go Back</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>