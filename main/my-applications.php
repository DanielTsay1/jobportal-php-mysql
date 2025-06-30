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
        }
        .navbar {
            background: var(--bg-white);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .navbar-brand {
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--primary-blue);
            text-decoration: none;
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 7rem 0 3rem;
            text-align: center;
        }
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            font-weight: 400;
        }
        .search-container, .filter-section {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem 2rem 1.5rem 2rem;
            margin: -3rem auto 2rem auto;
            max-width: 1000px;
        }
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
        }
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .search-input, select {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            font-size: 1rem;
            color: var(--text-dark);
            transition: border 0.2s, box-shadow 0.2s;
        }
        .search-input:focus, select:focus {
            border-color: var(--primary-blue);
            background: var(--bg-white);
            outline: none;
            box-shadow: 0 0 0 2px #2563eb22;
        }
        .search-btn {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 0.55rem 0.9rem;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }
        .search-btn i {
            margin: 0;
            font-size: 1.25rem;
            display: block;
        }
        .search-btn:hover {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--primary-blue) 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
        }
        .filter-btn {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 30px;
            padding: 0.5rem 1.25rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .job-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
            border-color: var(--primary-blue);
        }
        .job-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .job-title:hover {
            color: var(--primary-blue);
        }
        .company-badge {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .job-location {
            color: var(--text-light);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .salary-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        }
        .view-btn {
            background: var(--bg-light);
            border: 2px solid var(--primary-blue);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .view-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .compact-btn {
            background: var(--bg-light);
            border: 2px solid var(--primary-blue);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .compact-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .text-link {
            color: var(--primary-blue);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .text-link:hover {
            color: var(--primary-blue-dark);
            text-decoration: underline;
        }
        .small-btn {
            background: var(--primary-blue);
            border: none;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            color: white;
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .small-btn:hover {
            background: var(--primary-blue-dark);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }
        .no-results i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary-blue);
            opacity: 0.5;
        }
        .no-results h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        .footer {
            background: var(--bg-white);
            border-top: 1px solid var(--border-light);
            padding: 3rem 0 2rem;
            margin-top: 4rem;
            text-align: center;
            box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.05);
        }
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }
        .footer-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-blue);
        }
        .footer p {
            color: var(--text-light);
        }
        .footer a {
            color: var(--primary-blue);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: var(--primary-blue-dark);
        }
        .footer i {
            color: var(--primary-blue);
        }
        .admin-link {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-top: 1rem;
            display: inline-block;
        }
        .admin-link:hover {
            opacity: 1;
        }
        .border-secondary {
            border-color: var(--border-light) !important;
        }
        .text-secondary {
            color: var(--text-light) !important;
        }
        .text-light {
            color: var(--text-dark) !important;
        }
        .glass {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
        }
        
        /* Applications specific styles */
        .applications-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 7rem 0 3rem;
            text-align: center;
        }
        .applications-header h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .applications-header p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            font-weight: 400;
        }
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .applications-container {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1000px;
            border: 1px solid var(--border-light);
        }
        .app-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .app-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
            border-color: var(--primary-blue);
        }
        .application-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .application-title:hover {
            color: var(--primary-blue);
        }
        .application-company {
            color: var(--primary-blue);
            font-size: 1.05rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .application-meta {
            color: var(--text-light);
            font-size: 0.98rem;
            margin-bottom: 0.8rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 12px;
            font-size: 0.92em;
            font-weight: 700;
            padding: 0.35em 0.9em;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
            text-transform: uppercase;
            box-shadow: 0 2px 4px -2px rgb(0 0 0 / 0.08);
            border: none;
        }
        .status-pending { 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
            color: white; 
        }
        .status-hired { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            color: white; 
        }
        .status-rejected { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            color: white; 
        }
        .status-withdrawn { 
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); 
            color: white; 
        }
        .application-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-modern {
            border-radius: 12px;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
            border: none;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-modern:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
            text-decoration: none;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }
        .empty-state i {
            font-size: 4rem;
            color: var(--primary-blue);
            margin-bottom: 1.2rem;
            opacity: 0.7;
        }
        .empty-state h3 {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .empty-state p {
            font-size: 1.08rem;
            margin-bottom: 1.5rem;
        }
        .alert-warning {
            background: rgba(245, 158, 11, 0.1) !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
            color: #d97706 !important;
        }
        
        @media (max-width: 900px) {
            .search-container, .filter-section, .job-card, .glass-panel, .app-card {
                max-width: 98vw;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }
        @media (max-width: 600px) {
            .search-container, .filter-section, .job-card, .glass-panel, .app-card {
                padding: 1.2rem 0.5rem;
            }
            .hero-title, .applications-header h1 { 
                font-size: 2.1rem; 
            }
        }
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
</head>
<body style="padding-top:68px;">
<?php include 'header-jobseeker.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">My Applications</h1>
            <p class="hero-subtitle">Track your job applications and their status</p>
        </div>
    </section>
         
    <!-- Applications Container -->
    <div class="container">
        <div class="applications-container">
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>No Applications Yet</h3>
                    <p>Start applying to jobs and your applications will appear here for easy tracking.</p>
                    <a href="job-list.php" class="btn view-btn">
                        <i class="fas fa-search me-2"></i>Browse Available Jobs
                    </a>
                </div>
            <?php else: ?>
                <div id="jobResults">
                    <?php foreach ($applications as $app): ?>
                        <div class="job-card">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h3 class="job-title mb-3">
                                        <a href="job-details.php?jobid=<?= $app['jobid'] ?>" class="job-title">
                                            <?= htmlspecialchars($app['designation']) ?>
                                        </a>
                                    </h3>
                                    <div class="mb-3">
                                        <span class="company-badge">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($app['company_name']) ?>
                                        </span>
                                        <span class="job-location">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            Applied on <?= date('M d, Y', strtotime($app['applied_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                            <i class="fas fa-circle me-1"></i><?= htmlspecialchars($app['status']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($app['suspended']) && $app['suspended'] == 1): ?>
                                        <div class="alert alert-warning mt-2 mb-2" style="font-size: 0.85rem; padding: 0.75rem; border-radius: 8px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107;">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <strong>Company Suspended</strong><br>
                                            <small>Reason: <?= htmlspecialchars($app['suspension_reason'] ?? 'No reason provided.') ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                                    <div class="mb-3">
                                        <a href="/main/job-details.php?jobid=<?= $app['jobid'] ?>" class="view-btn">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <h4 class="footer-title">Ready to Shape the Future?</h4>
                <p class="mb-4">Join thousands of tech professionals finding their dream roles</p>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:hello@jobportal.com" class="text-decoration-none">
                                hello@jobportal.com
                            </a>
                        </p>
                        <p class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            <span>+1 (555) JOB-PORTAL</span>
                        </p>
                    </div>
                </div>
                <div class="border-top border-secondary pt-3 mt-4">
                    <p class="mb-0 text-secondary">
                        &copy; <?= date('Y') ?> <span style="color: var(--primary-blue);">Job</span><span style="color: var(--accent-blue);">Portal</span> 
                        &mdash; Where innovation meets opportunity
                    </p>
                    <a href="admin-login.php" class="admin-link">
                        <i class="fas fa-user-shield me-1"></i>Admin Login
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Chat Button -->
    <a href="https://www.stack-ai.com/chat/68623c004fe0ebb9c4eaeec8-6jBGBvdYxWKz2625u0mQhn" target="_blank" rel="noopener" id="floatingChatBtn" title="Chat with JobPortal AI Agent">
        <i class="fas fa-comments"></i>
    </a>

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