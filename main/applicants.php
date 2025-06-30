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

        .page-header, .empty-state, .main-content, .table, .card, .search-bar {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
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

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
        }

        .table th, .table td {
            background: transparent !important;
            color: var(--text-dark) !important;
            border-color: var(--border-light) !important;
        }

        .table th {
            font-weight: 600;
            color: var(--text-dark) !important;
            background: var(--bg-light) !important;
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
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1200px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            margin-bottom: 0.5rem;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-hired {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .status-reviewed {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        .applicant-card {
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .applicant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
        }

        .applicant-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .applicant-email {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .job-title {
            color: var(--primary-blue);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .application-date {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
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
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-btn {
                text-align: center;
            }
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