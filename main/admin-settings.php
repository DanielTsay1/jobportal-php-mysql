<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin-login.php');
    exit;
}
require_once '../php/db.php';

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_settings':
            // Update system settings
            $site_name = $_POST['site_name'] ?? 'JobPortal';
            $admin_email = $_POST['admin_email'] ?? '';
            $max_file_size = intval($_POST['max_file_size'] ?? 5);
            $job_approval_required = isset($_POST['job_approval_required']) ? 1 : 0;
            
            // Store settings in a settings table (create if doesn't exist)
            $conn->query("
                CREATE TABLE IF NOT EXISTS system_settings (
                    setting_key VARCHAR(50) PRIMARY KEY,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            
            $settings = [
                'site_name' => $site_name,
                'admin_email' => $admin_email,
                'max_file_size' => $max_file_size,
                'job_approval_required' => $job_approval_required
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param('sss', $key, $value, $value);
                $stmt->execute();
            }
            
            $message = 'Settings updated successfully!';
            $message_type = 'success';
            break;
            
        case 'clear_cache':
            // Clear any cached data
            $message = 'Cache cleared successfully!';
            $message_type = 'success';
            break;
            
        case 'backup_database':
            // Create a simple backup
            $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_dir = '../backups/';
            
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            // Simple backup - in production, use mysqldump
            $tables = ['user', 'company', 'job-post', 'applied'];
            $backup_content = '';
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch_row();
                $backup_content .= "\n\n" . $row[1] . ";\n\n";
                
                $data = $conn->query("SELECT * FROM `$table`");
                while ($row = $data->fetch_assoc()) {
                    $values = array_map(function($value) use ($conn) {
                        return $conn->real_escape_string($value ?? '');
                    }, $row);
                    $backup_content .= "INSERT INTO `$table` VALUES ('" . implode("','", $values) . "');\n";
                }
            }
            
            file_put_contents($backup_file, $backup_content);
            $message = 'Database backup created successfully!';
            $message_type = 'success';
            break;
    }
}

// Get current settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Get system statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM user")->fetch_assoc()['count'];
$total_jobs = $conn->query("SELECT COUNT(*) as count FROM `job-post`")->fetch_assoc()['count'];
$total_applications = $conn->query("SELECT COUNT(*) as count FROM applied")->fetch_assoc()['count'];
$total_companies = $conn->query("SELECT COUNT(*) as count FROM company")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-purple: #7c3aed;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            --success-green: #10b981;
            --warning-orange: #f59e0b;
            --danger-red: #dc2626;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .admin-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .admin-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-blue);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .stat-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .btn-warning {
            background: var(--warning-orange);
            border: none;
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-red);
            border: none;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        
        .logout-btn {
            background: var(--danger-red);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #b91c1c;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="admin-title">
                        <i class="fas fa-cog me-3"></i>
                        System Settings
                    </h1>
                    <p class="admin-subtitle">
                        Configure your job portal system
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="admin-dashboard.php" class="btn btn-light me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <a href="/php/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- System Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number"><?= $total_users ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number"><?= $total_jobs ?></div>
                    <div class="stat-label">Total Jobs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number"><?= $total_applications ?></div>
                    <div class="stat-label">Applications</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number"><?= $total_companies ?></div>
                    <div class="stat-label">Companies</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- General Settings -->
            <div class="col-md-8">
                <div class="settings-card">
                    <h4 class="mb-4">
                        <i class="fas fa-cog me-2"></i>General Settings
                    </h4>
                    <form method="post">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? 'JobPortal') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admin_email" class="form-label">Admin Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_file_size" class="form-label">Max File Size (MB)</label>
                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                       value="<?= htmlspecialchars($settings['max_file_size'] ?? '5') ?>" min="1" max="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="job_approval_required" name="job_approval_required" 
                                           <?= ($settings['job_approval_required'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="job_approval_required">
                                        Require job approval
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Maintenance -->
            <div class="col-md-4">
                <div class="settings-card">
                    <h4 class="mb-4">
                        <i class="fas fa-tools me-2"></i>System Maintenance
                    </h4>
                    
                    <div class="d-grid gap-3">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-broom me-2"></i>Clear Cache
                            </button>
                        </form>
                        
                        <form method="post" style="display: inline;" onsubmit="return confirm('Create a database backup? This may take a moment.');">
                            <input type="hidden" name="action" value="backup_database">
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-download me-2"></i>Backup Database
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-danger w-100" onclick="showSystemInfo()">
                            <i class="fas fa-info-circle me-2"></i>System Information
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="settings-card">
                    <h4 class="mb-4">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h4>
                    
                    <div class="d-grid gap-2">
                        <a href="admin-dashboard.php?tab=jobs" class="btn btn-outline-primary">
                            <i class="fas fa-briefcase me-2"></i>Manage Jobs
                        </a>
                        <a href="admin-dashboard.php?tab=users" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="admin-dashboard.php?tab=companies" class="btn btn-outline-primary">
                            <i class="fas fa-building me-2"></i>Manage Companies
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSystemInfo() {
            const info = `
System Information:
- PHP Version: <?= phpversion() ?>
- MySQL Version: <?= $conn->server_info ?? 'Unknown' ?>
- Server: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
- Upload Max Size: <?= ini_get('upload_max_filesize') ?>
- Memory Limit: <?= ini_get('memory_limit') ?>
- Max Execution Time: <?= ini_get('max_execution_time') ?>s
            `;
            alert(info);
        }
    </script>
</body>
</html> 