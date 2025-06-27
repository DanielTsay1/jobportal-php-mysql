<?php
session_start();
require_once '../php/db.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'B' || !isset($_SESSION['userid'])) {
    header('Location: /main/login.php');
    exit;
}

$userid = $_SESSION['userid'] ?? null;
if (!$userid) {
    header('Location: /main/login.php');
    exit;
}

// Fetch user's applications
$applications = [];
$sql = "SELECT 
            a.`S. No` as app_id,
            a.applied_at,
            a.status,
            j.designation,
            j.jobid,
            c.name as company_name,
            c.suspended,
            c.suspension_reason
        FROM applied a
        JOIN `job-post` j ON a.jobid = j.jobid
        JOIN company c ON j.compid = c.compid
        WHERE a.userid = ?
        ORDER BY a.applied_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
$conn->close();

$hired_application = null;
foreach ($applications as $app) {
    if ($app['status'] === 'Hired') {
        $hired_application = $app;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      html, body { height: 100%; }
      body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        font-family: 'Poppins', Arial, sans-serif !important;
        background: #f8fafc;
      }
      .main-content { flex: 1 0 auto; }
      .applications-header {
        text-align: center;
        margin-bottom: 2rem;
      }
      .applications-header h1 {
        font-size: 1.7rem;
        font-weight: 600;
        color: #1976d2;
        margin-bottom: 0.3rem;
      }
      .applications-header p {
        color: #888;
        font-size: 1rem;
        margin-bottom: 0;
      }
      .applications-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.2rem;
      }
      .application-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(30, 144, 255, 0.06);
        padding: 1.2rem 1rem 1rem 1rem;
        display: flex;
        flex-direction: column;
        border: 1px solid #f0f0f0;
        min-height: 140px;
      }
      .application-title {
        font-size: 1.08rem;
        font-weight: 500;
        color: #1976d2;
        margin-bottom: 0.1rem;
        line-height: 1.2;
      }
      .application-company {
        color: #7b1fa2;
        font-size: 0.98rem;
        margin-bottom: 0.4rem;
      }
      .application-meta {
        color: #6c757d;
        font-size: 0.93rem;
        margin-bottom: 0.4rem;
      }
      .status-badge {
        display: inline-block;
        border-radius: 8px;
        font-size: 0.89em;
        font-weight: 500;
        padding: 0.22em 0.8em;
        letter-spacing: 0.01em;
        margin-bottom: 0.4rem;
        background: #f0f4fa;
        color: #1976d2;
        border: 1px solid #e3f0ff;
      }
      .status-hired { background: #e8f5e9; color: #388e3c; border-color: #d4edda; }
      .status-rejected { background: #fbe9e7; color: #c62828; border-color: #f8d7da; }
      .application-actions {
        margin-top: auto;
      }
      .btn-modern {
        border-radius: 16px;
        font-weight: 500;
        padding: 0.45rem 1.1rem;
        font-size: 0.98rem;
        border: none;
        background: #1976d2;
        color: #fff;
        box-shadow: none;
        transition: background 0.2s;
      }
      .btn-modern:hover {
        background: #7b1fa2;
        color: #fff;
      }
      @media (max-width: 600px) {
        .applications-grid {
          grid-template-columns: 1fr;
        }
        .application-card {
          padding: 0.9rem 0.5rem 0.7rem 0.5rem;
        }
      }
    </style>
</head>
<body>
    <?php include 'header-jobseeker.php'; ?>

    <div class="container main-content py-5">
        <div class="applications-header">
            <h1>My Applications</h1>
            <p>Track your job applications</p>
        </div>
        <div class="applications-grid">
            <?php if (empty($applications)): ?>
                <div class="application-card text-center">
                    <i class="fas fa-briefcase fa-2x mb-3 text-muted"></i>
                    <h4>No applications found</h4>
                    <p>Start applying to jobs and your applications will appear here.</p>
                    <a href="job-list.php" class="btn btn-modern mt-2">Browse Jobs</a>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="application-title"><?= htmlspecialchars($app['designation']) ?></div>
                        <div class="application-company"><?= htmlspecialchars($app['company_name']) ?></div>
                        <div class="application-meta">
                            <?= date('M d, Y', strtotime($app['applied_at'])) ?>
                        </div>
                        <span class="status-badge status-<?= strtolower($app['status']) ?>">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                        
                        <?php if (!empty($app['suspended']) && $app['suspended'] == 1): ?>
                            <div class="alert alert-warning mt-2 mb-2" style="font-size: 0.85rem; padding: 0.5rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <strong>Company Suspended</strong><br>
                                <small>Reason: <?= htmlspecialchars($app['suspension_reason'] ?? 'No reason provided.') ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="application-actions">
                            <a href="/main/job-details.php?jobid=<?= $app['jobid'] ?>" class="btn btn-modern">View Job</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    function withdrawOtherApplications() {
        if (!confirm('Are you sure you want to withdraw all your other active applications? This action cannot be undone.')) {
            return;
        }
        
        fetch('/php/withdraw_other_applications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Your other active applications have been successfully withdrawn.');
                location.reload(); 
            } else {
                alert('An error occurred: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred. Please try again.');
        });
    }
    </script>
</body>
</html>