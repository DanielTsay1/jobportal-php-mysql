<?php
session_start();
require_once '../php/db.php';

$error_message = '';

// Handle error messages from URL parameters
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missing_fields':
            $error_message = 'All fields are required.';
            break;
        case 'bad_credentials':
            $error_message = 'Invalid username or password.';
            break;
        case 'no_user':
            $error_message = 'No admin found with that username.';
            break;
        case 'server_error':
            $error_message = 'Server error. Please try again.';
            break;
        default:
            $error_message = 'An error occurred. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            min-height: 100vh;
        }
        body {
            font-family: 'Poppins', Arial, sans-serif !important;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            min-height: 100vh;
            width: 100vw;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 400px;
            width: 100%;
            margin: 2rem auto;
            animation: fadeSlideIn 0.7s cubic-bezier(.4,1.4,.6,1) 0.1s forwards;
            opacity: 0;
            transform: translateY(40px);
            position: relative;
            overflow: hidden;
        }
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .admin-icon {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        .admin-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .admin-subtitle {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 0;
        }
        .form-floating .form-control {
            border-radius: 14px;
            border: 1.5px solid #e3f0ff;
            background: #f8fafc;
            font-size: 1rem;
            margin-bottom: 1.1rem;
            padding-left: 2.2rem;
        }
        .form-floating > .fa {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #dc2626;
            z-index: 2;
        }
        .form-floating label {
            color: #888;
            font-size: 1rem;
            left: 2.2rem;
        }
        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }
        .auth-btn {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 0.7rem 2.2rem;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15);
            transition: background 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .auth-btn:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            box-shadow: 0 4px 16px rgba(220, 38, 38, 0.25);
            color: white;
        }
        .back-link {
            color: #64748b;
            cursor: pointer;
            transition: color 0.2s;
            text-align: center;
            display: block;
            margin-top: 1.2rem;
            text-decoration: none;
        }
        .back-link:hover {
            color: #dc2626;
        }
        .alert.error {
            background: #fef2f2;
            color: #991b1b;
            border-radius: 12px;
            padding: 0.7rem 1.2rem;
            margin-bottom: 1rem;
            font-weight: 500;
            border: 1.5px solid #fecaca;
        }
        @media (max-width: 600px) {
            .auth-card {
                padding: 1.2rem 0.5rem 1rem 0.5rem;
                max-width: 98vw;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="admin-header">
            <div class="admin-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="admin-title">Admin Access</h1>
            <p class="admin-subtitle">Job Portal Administration</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <form action="/php/admin-login.php" method="post" autocomplete="off">
            <div class="form-floating position-relative mb-3">
                <i class="fa fa-user"></i>
                <input type="text" class="form-control" id="admin-username" name="username" placeholder="Admin Username" required>
                <label for="admin-username">Admin Username</label>
            </div>
            
            <div class="form-floating position-relative mb-3">
                <i class="fa fa-lock"></i>
                <input type="password" class="form-control" id="admin-password" name="password" placeholder="Password" required>
                <label for="admin-password">Password</label>
            </div>
            
            <button type="submit" class="auth-btn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Access Admin Panel
            </button>
        </form>
        
        <a href="/main/login.php" class="back-link">
            <i class="fas fa-arrow-left me-2"></i>
            Back to User Login
        </a>
    </div>
</body>
</html> 