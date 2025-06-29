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
        background: linear-gradient(135deg, #181828 0%, #23233a 100%);
        color: #f3f3fa;
        font-family: 'Inter', Arial, sans-serif;
        min-height: 100vh;
        margin: 0;
        overflow-x: hidden;
      }
      .main-content { flex: 1 0 auto; }
      .applications-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem 0;
      }
      .applications-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #e8eaf6;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        letter-spacing: 0.03em;
      }
      .applications-header p {
        color: #b3b3c6;
        font-size: 1.1rem;
        margin-bottom: 0;
        font-weight: 400;
        letter-spacing: 0.02em;
      }
      .applications-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
      }
      .glass-panel {
        background: rgba(255,255,255,0.10);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(30,20,60,0.13);
        backdrop-filter: blur(18px) saturate(1.2);
        border: 1.5px solid rgba(255,255,255,0.13);
        margin: 2rem auto;
        max-width: 1200px;
        padding: 2.5rem 2rem 2rem 2rem;
        z-index: 2;
        position: relative;
      }
      .app-card {
        background: rgba(255,255,255,0.13);
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(123,63,228,0.08);
        border: 1.5px solid rgba(255,255,255,0.10);
        backdrop-filter: blur(8px) saturate(1.1);
        color: #f3f3fa;
        margin-bottom: 1.5rem;
        padding: 2rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
      }
      .app-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
      .app-card:hover {
        box-shadow: 0 8px 25px rgba(123,63,228,0.13);
        border-color: #00e0d6;
        transform: translateY(-2px);
      }
      .btn-apply, .btn-primary, .btn-outline-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        color: #fff;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      }
      .btn-apply:hover, .btn-primary:hover, .btn-outline-primary:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        color: #fff;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        transform: translateY(-1px);
      }
      .application-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #e8eaf6;
        margin-bottom: 0.5rem;
        line-height: 1.3;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
      }
      .application-company {
        color: #667eea;
        font-size: 1rem;
        margin-bottom: 0.8rem;
        font-weight: 500;
        letter-spacing: 0.02em;
      }
      .application-meta {
        color: #b3b3c6;
        font-size: 0.95rem;
        margin-bottom: 0.8rem;
        font-weight: 400;
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
      .status-withdrawn { 
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); 
        color: #fff; 
        border: none;
      }
      .application-actions {
        margin-top: 1.5rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
      }
      .btn-modern {
        border-radius: 10px;
        font-weight: 500;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        border: none;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
      }
      .btn-modern:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        text-decoration: none;
      }
      .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #b3b3c6;
      }
      .empty-state i {
        font-size: 4rem;
        color: #667eea;
        margin-bottom: 1rem;
        opacity: 0.7;
      }
      .empty-state h3 {
        color: #e8eaf6;
        font-weight: 600;
        margin-bottom: 0.5rem;
      }
      .empty-state p {
        font-size: 1rem;
        margin-bottom: 1.5rem;
      }
      @media (max-width: 768px) {
        .applications-header h1 {
          font-size: 2rem;
        }
        .applications-grid {
          grid-template-columns: 1fr;
          gap: 1rem;
        }
        .app-card {
          padding: 1.5rem;
        }
        .application-actions {
          flex-direction: column;
        }
        .btn-modern {
          width: 100%;
          justify-content: center;
        }
      }
      @media (max-width: 576px) {
        .applications-header {
          padding: 1.5rem 0;
        }
        .applications-header h1 {
          font-size: 1.8rem;
        }
        .glass-panel {
          padding: 1.5rem 1rem;
        }
        .app-card {
          padding: 1rem;
        }
      }
    </style>
</head>
<body style="padding-top:68px;">
<?php include 'header-jobseeker.php'; ?>

    <div class="container main-content py-5">
        <div class="applications-header">
            <h1>My Applications</h1>
            <p>Track your job applications and their status</p>
        </div>
        
        <div class="glass-panel">
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>No Applications Yet</h3>
                    <p>Start applying to jobs and your applications will appear here for easy tracking.</p>
                    <a href="job-list.php" class="btn btn-modern">
                        <i class="fas fa-search me-2"></i>Browse Available Jobs
                    </a>
                </div>
            <?php else: ?>
                <div class="applications-grid">
                    <?php foreach ($applications as $app): ?>
                        <div class="app-card">
                            <div class="application-title"><?= htmlspecialchars($app['designation']) ?></div>
                            <div class="application-company">
                                <i class="fas fa-building me-2"></i><?= htmlspecialchars($app['company_name']) ?>
                            </div>
                            <div class="application-meta">
                                <i class="fas fa-calendar-alt me-2"></i>Applied on <?= date('M d, Y', strtotime($app['applied_at'])) ?>
                            </div>
                            <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                <i class="fas fa-circle me-1"></i><?= htmlspecialchars($app['status']) ?>
                            </span>
                            
                            <?php if (!empty($app['suspended']) && $app['suspended'] == 1): ?>
                                <div class="alert alert-warning mt-2 mb-2" style="font-size: 0.85rem; padding: 0.75rem; border-radius: 8px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Company Suspended</strong><br>
                                    <small>Reason: <?= htmlspecialchars($app['suspension_reason'] ?? 'No reason provided.') ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="application-actions">
                                <a href="/main/job-details.php?jobid=<?= $app['jobid'] ?>" class="btn btn-modern">
                                    <i class="fas fa-eye me-1"></i>View Job Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
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