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
        body {
            background: linear-gradient(135deg, #181828 0%, #23233a 100%);
            color: #f3f3fa;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px; /* Prevent content under header */
        }
        .page-header, .empty-state, .main-content, .table, .card, .search-bar {
            background: rgba(36, 38, 58, 0.98);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.18);
            border: 1.5px solid rgba(120,130,255,0.13);
            color: #f3f3fa;
        }
        .btn, .btn-modern {
            background: linear-gradient(90deg, #00e0d6 0%, #7b3fe4 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.18);
        }
        .btn:hover, .btn-modern:hover {
            background: linear-gradient(90deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }
        .table th, .table td {
            background: transparent !important;
            color: #f3f3fa !important;
        }
        .empty-state {
            color: #b3b3c6;
            text-align: center;
            padding: 2rem 1rem;
            margin: 2rem auto;
        }
        @media (max-width: 900px) {
            .main-content { padding: 1rem 0.7rem; }
        }
        @media (max-width: 700px) {
            .main-content { padding: 1.2rem 0.5rem 1.2rem 0.5rem; }
        }
        .glass-panel {
            background: rgba(255,255,255,0.10);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.13);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            z-index: 2;
            position: relative;
        }
        .status-badge {
            display: inline-block;
            border-radius: 10px;
            font-size: 0.9em;
            font-weight: 600;
            padding: 0.4em 1em;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
            text-transform: uppercase;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
            color: #fff;
            border: none;
        }
        .status-hired {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
            border: none;
        }
        .status-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
            border: none;
        }
        .status-reviewed {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: #fff;
            border: none;
        }
        .status-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: #fff;
            border: none;
        }
    </style>
</head>
<body>
    <?php include 'header-recruiter.php'; ?>
    <div class="container py-4">
        <div class="glass-panel" style="max-width:1200px; margin:2.5rem auto 2rem auto; padding:2.5rem 2rem 2rem 2rem;">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="mb-0" style="font-weight:700;"><i class="fas fa-users me-2"></i>Manage Applicants</h2>
                <a href="recruiter.php" class="btn btn-gradient px-4"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>
            <form class="search-bar mb-4" onsubmit="return false;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search applicants, job title, or status...">
            </form>
            <?php if (empty($applicants)): ?>
                <div class="empty-state text-center py-5">
                    <i class="fas fa-user-friends fa-3x mb-3" style="color: #cbd5e1;"></i>
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
                                <tr>
                                    <td class="px-4">
                                        <strong><?= htmlspecialchars($app['applicant_name']) ?></strong>
                                        <div class="text-muted small"><?= htmlspecialchars($app['applicant_email']) ?></div>
                                    </td>
                                    <td class="px-4"><?= htmlspecialchars($app['job_title']) ?></td>
                                    <td class="px-4"><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                    <td class="px-4">
                                        <span id="status-badge-<?= $app['app_id'] ?>" class="status-badge status-<?= strtolower($app['status']) ?> text-uppercase ms-2"><?= htmlspecialchars($app['status']) ?></span>
                                    </td>
                                    <td class="px-4">
                                        <div class="d-flex flex-row gap-2 align-items-center">
                                            <?php if (!empty($app['resume_file'])): ?>
                                                <a href="/uploads/<?= htmlspecialchars($app['resume_file']) ?>" target="_blank" class="btn btn-gradient btn-sm" style="height: 50px; line-height: 1;"><i class="fas fa-file-alt me-1"></i>Resume</a>
                                            <?php endif; ?>
                                            <?php if (!empty($app['cover_letter_file'])): ?>
                                                <a href="/uploads/<?= htmlspecialchars($app['cover_letter_file']) ?>" target="_blank" class="btn btn-gradient btn-sm" style="height: 50px; line-height: 1;"><i class="fas fa-file-alt me-1"></i>Cover</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4">
                                        <div class="dropdown">
                                            <button class="btn btn-gradient btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="height: 50px; line-height: 1;">
                                                <i class="fas fa-edit me-1"></i>Update Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStatus(<?= $app['app_id'] ?>, 'Hired')">Hired</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStatus(<?= $app['app_id'] ?>, 'Rejected')">Rejected</a></li>
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
    
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const applicantsData = <?= json_encode(array_column($applicants, 'status', 'app_id')) ?>;

    function setBadge(appId, status) {
        const badge = document.getElementById(`status-badge-${appId}`);
        if (!badge) return;

        badge.textContent = status;
        badge.className = 'status-badge text-uppercase ms-2'; // Reset to base classes
        switch(status) {
            case 'Hired': badge.classList.add('status-hired'); break;
            case 'Rejected': badge.classList.add('status-rejected'); break;
            case 'Pending': badge.classList.add('status-pending'); break;
            case 'Reviewed': badge.classList.add('status-reviewed'); break;
            default: badge.classList.add('status-pending'); // Default fallback
        }
    }

    function updateStatus(appId, newStatus) {
        // Show loading spinner on badge
        setBadge(appId, '...');
        fetch('/php/update_application_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                app_id: appId,
                status: newStatus
            })
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