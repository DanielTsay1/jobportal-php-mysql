<?php
session_start();
require_once '../php/db.php';

// Ensure user is a recruiter and logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A') {
    header('Location: /main/login.html');
    exit;
}

$recruiter_username = $_SESSION['username'] ?? '';
$compid = $_SESSION['compid'] ?? null;

if (!$compid) {
    // Redirect or show error if no company is associated with recruiter
    die("Error: Recruiter is not associated with any company. Please contact support.");
}

// Fetch company name
$stmt = $conn->prepare("SELECT name FROM company WHERE compid = ?");
$stmt->bind_param("i", $compid);
$stmt->execute();
$company_name_result = $stmt->get_result()->fetch_assoc();
$company_name = $company_name_result ? $company_name_result['name'] : 'Your Company';
$stmt->close();


// Fetch all applicants for the company's jobs
$applicants = [];
$sql = "SELECT 
            a.`S. No` as app_id, 
            a.applied_at, 
            a.status,
            a.cover_letter_file,
            a.resume_file,
            u.username AS applicant_name, 
            u.email AS applicant_email,
            j.designation AS job_title
        FROM applied a
        JOIN user u ON a.userid = u.userid
        JOIN `job-post` j ON a.jobid = j.jobid
        WHERE j.compid = ?
        ORDER BY a.applied_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $compid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $applicants[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - <?= htmlspecialchars($company_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'header-recruiter.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Manage Applicants</h1>
            <a href="/main/recruiter.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Applications for <?= htmlspecialchars($company_name) ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($applicants)): ?>
                    <div class="alert alert-info m-4">No applicants found yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Applicant</th>
                                    <th class="py-3 px-4">Job Title</th>
                                    <th class="py-3 px-4">Applied On</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4">Documents</th>
                                    <th class="py-3 px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applicants as $app): ?>
                                    <tr id="app-row-<?= $app['app_id'] ?>">
                                        <td class="px-4">
                                            <strong><?= htmlspecialchars($app['applicant_name']) ?></strong>
                                            <div class="text-muted small"><?= htmlspecialchars($app['applicant_email']) ?></div>
                                        </td>
                                        <td class="px-4"><?= htmlspecialchars($app['job_title']) ?></td>
                                        <td class="px-4"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                        <td class="px-4">
                                            <span class="badge" id="status-badge-<?= $app['app_id'] ?>"></span>
                                        </td>
                                        <td class="px-4">
                                            <?php if ($app['resume_file']): ?>
                                                <a href="/uploads/<?= htmlspecialchars($app['resume_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-file-alt me-1"></i>Resume</a>
                                            <?php endif; ?>
                                            <?php if ($app['cover_letter_file']): ?>
                                                <a href="/uploads/<?= htmlspecialchars($app['cover_letter_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-envelope-open-text me-1"></i>Cover</a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Update Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStatus(<?= $app['app_id'] ?>, 'Viewed')">Viewed</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStatus(<?= $app['app_id'] ?>, 'Shortlisted')">Shortlisted</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStatus(<?= $app['app_id'] ?>, 'Hired')">Hired</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); updateStatus(<?= $app['app_id'] ?>, 'Rejected')">Rejected</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const applicantsData = <?= json_encode(array_column($applicants, 'status', 'app_id')) ?>;

    function setBadge(appId, status) {
        const badge = document.getElementById(`status-badge-${appId}`);
        if (!badge) return;

        badge.textContent = status;
        badge.className = 'badge '; // Reset classes
        switch(status) {
            case 'Hired': badge.classList.add('bg-success'); break;
            case 'Rejected': badge.classList.add('bg-danger'); break;
            case 'Shortlisted': badge.classList.add('bg-info', 'text-dark'); break;
            case 'Viewed': badge.classList.add('bg-secondary'); break;
            default: badge.classList.add('bg-primary');
        }
    }

    function updateStatus(appId, newStatus) {
        fetch('/php/update_application_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `app_id=${appId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                setBadge(appId, newStatus);
            } else {
                alert('Error updating status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        });
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        for (const appId in applicantsData) {
            setBadge(appId, applicantsData[appId]);
        }
    });
    </script>
</body>
</html> 