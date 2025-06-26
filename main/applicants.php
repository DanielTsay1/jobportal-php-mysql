<?php
session_start();
require_once '../php/db.php';

// Allow admin or recruiter
$user_type = $_SESSION['user_type'] ?? '';
$compid = $_SESSION['compid'] ?? null;
$jobid = isset($_GET['jobid']) ? intval($_GET['jobid']) : null;

if ($user_type === 'A') {
    // Recruiter: require compid
    if (!$compid) {
        die("Error: Recruiter is not associated with any company. Please contact support.");
    }
} else if ($user_type === 'admin') {
    // Admin: require jobid
    if (!$jobid) {
        die("Error: Admin must specify a job to view applicants.");
    }
} else {
    header('Location: /main/login.php');
    exit;
}

$recruiter_username = $_SESSION['username'] ?? '';

// Fetch company name
$stmt = $conn->prepare("SELECT name FROM company WHERE compid = ?");
$stmt->bind_param("i", $compid);
$stmt->execute();
$company_name_result = $stmt->get_result()->fetch_assoc();
$company_name = $company_name_result ? $company_name_result['name'] : 'Your Company';
$stmt->close();

// Fetch applicants
$applicants = [];
if ($jobid) {
    // Only applicants for this job
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
            WHERE j.jobid = ?
            ORDER BY a.applied_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $jobid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $applicants[] = $row;
    }
    $stmt->close();
} else {
    // All applicants for the company (recruiter view)
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', 'Inter', Arial, sans-serif !important;
            background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%);
        }
        .main-content {
            flex: 1 0 auto;
        }
        .page-header {
            background: linear-gradient(90deg, #1E90FF 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 0 0.5rem 0;
            margin-bottom: 1.5rem;
            border-radius: 0 0 14px 14px;
            box-shadow: 0 2px 10px rgba(30, 144, 255, 0.08);
            opacity: 0;
            transform: translateY(-30px);
            animation: fadeSlideDown 0.7s cubic-bezier(.4,1.4,.6,1) 0.1s forwards;
        }
        .page-header h1 {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 0;
            letter-spacing: -0.5px;
        }
        .page-header .back-btn {
            font-size: 0.95rem;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            background: #fff;
            color: #1E90FF;
            border: none;
            box-shadow: 0 2px 8px rgba(30, 144, 255, 0.07);
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .page-header .back-btn:hover {
            background: #e9ecef;
            color: #764ba2;
        }
        .page-header .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .page-header .header-row p {
            margin-bottom: 0;
            font-size: 0.98rem;
            opacity: 0.7;
        }
        .card {
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(30, 144, 255, 0.08);
            border: none;
            opacity: 0;
            transform: translateY(40px);
            animation: fadeSlideIn 0.7s cubic-bezier(.4,1.4,.6,1) 0.3s forwards;
        }
        .card-header {
            border-radius: 18px 18px 0 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%);
            border-bottom: 1px solid #e9ecef;
        }
        .table {
            font-size: 1rem;
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background: #f8fafc;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }
        .table-hover tbody tr:hover {
            background: #e3f0ff;
            transition: background 0.2s;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background: #f6fafd;
        }
        .badge {
            border-radius: 12px;
            font-size: 0.95em;
            font-weight: 500;
            padding: 0.5em 1em;
            letter-spacing: 0.01em;
        }
        .btn, .dropdown-item {
            font-family: 'Poppins', 'Inter', Arial, sans-serif !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1E90FF 0%, #764ba2 100%);
            border: none;
        }
        .btn-secondary {
            background: #e9ecef;
            color: #2B3940;
            border: none;
        }
        .btn-outline-secondary {
            border-radius: 10px;
        }
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(30, 144, 255, 0.08);
        }
        .search-bar {
            max-width: 400px;
            margin-bottom: 1.5rem;
        }
        .search-bar input {
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            border: 2px solid #e9ecef;
            font-size: 1rem;
        }
        .search-bar input:focus {
            border-color: #1E90FF;
            box-shadow: 0 0 0 0.15rem rgba(30, 144, 255, 0.10);
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.95rem;
            }
            .page-header {
                padding: 0.5rem 0 0.3rem 0;
            }
            .page-header h1 {
                font-size: 1rem;
            }
            .page-header .header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        tbody tr {
            opacity: 0;
            transform: translateY(30px);
        }
        tbody tr.animated {
            animation: fadeRowIn 0.6s cubic-bezier(.4,1.4,.6,1) forwards;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeSlideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeRowIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        footer {
            flex-shrink: 0;
            width: 100vw;
        }
    </style>
</head>
<body>
    <?php include 'header-recruiter.php'; ?>
    <div class="page-header mb-4">
        <div class="container">
            <div class="header-row">
                <h1><i class="fas fa-users me-2"></i>Manage Applicants</h1>
                <?php
                $back_url = ($user_type === 'admin') ? '/main/admin-dashboard.php' : '/main/recruiter.php';
                ?>
                <a href="<?= $back_url ?>" class="back-btn"><i class="fas fa-arrow-left me-1"></i>Back</a>
            </div>
            <p>All applications for <b><?= htmlspecialchars($company_name) ?></b></p>
        </div>
    </div>

    <div class="container main-content">
        <div class="d-flex justify-content-end">
            <form class="search-bar w-100 w-md-auto" onsubmit="return false;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search applicants, job title, or status...">
            </form>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Applications</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($applicants)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-friends"></i>
                        <h4>No applicants found yet.</h4>
                        <p>Once candidates apply to your jobs, they will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0" id="applicantsTable">
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
                                    <tr id="app-row-<?= $app['app_id'] ?>" class="animated">
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
                                                <a href="/uploads/<?= htmlspecialchars($app['resume_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="tooltip" title="View Resume"><i class="fas fa-file-alt me-1"></i>Resume</a>
                                            <?php endif; ?>
                                            <?php if ($app['cover_letter_file']): ?>
                                                <a href="/uploads/<?= htmlspecialchars($app['cover_letter_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View Cover Letter"><i class="fas fa-envelope-open-text me-1"></i>Cover</a>
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
        // Show loading spinner on badge
        setBadge(appId, '...');
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
                setBadge(appId, applicantsData[appId]);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
            setBadge(appId, applicantsData[appId]);
        });
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        for (const appId in applicantsData) {
            setBadge(appId, applicantsData[appId]);
        }
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Search/filter functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('applicantsTable');
        if (searchInput && table) {
            searchInput.addEventListener('input', function() {
                const filter = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
        // Animate table rows in a staggered fashion
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, idx) => {
            setTimeout(() => {
                row.classList.add('animated');
            }, 80 * idx);
        });
    });
    </script>
</body>
</html> 