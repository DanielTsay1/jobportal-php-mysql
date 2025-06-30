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
            $error_message = 'No user found with that username.';
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
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <title>JobPortal - Login / Register</title>
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

        html, body {
            height: 100%;
            min-height: 100vh;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 2rem 1rem;
        }
        
        /* Background pattern */
        .auth-bg {
            position: absolute;
            top: 0; 
            left: 0; 
            width: 100vw; 
            height: 100%;
            z-index: 0;
            pointer-events: none;
            background: 
                radial-gradient(circle at 20% 80%, rgba(37, 99, 235, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
        }
        
        .auth-card {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 450px;
            width: 100%;
            margin: 2rem auto;
            animation: fadeSlideIn 0.7s cubic-bezier(.4,1.4,.6,1) 0.1s forwards;
            opacity: 0;
            transform: translateY(40px);
            position: relative;
            z-index: 2;
        }
        
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        
        .auth-tab {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .auth-tab.active, .auth-tab:focus {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
        
        .auth-tab:hover {
            background: rgba(37, 99, 235, 0.1);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .form-floating .form-control {
            border-radius: 12px;
            border: 2px solid var(--border-light);
            background: var(--bg-light);
            color: var(--text-dark);
            font-size: 1rem;
            margin-bottom: 1rem;
            padding-left: 2.7rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        
        .form-floating .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px #2563eb22;
            background: var(--bg-white);
        }
        
        .form-floating .form-control::placeholder {
            color: var(--text-light);
        }
        
        .form-floating > .fa {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            z-index: 2;
            pointer-events: none;
            font-size: 1.1rem;
        }
        
        .form-floating label {
            color: var(--text-light);
            font-size: 1rem;
            left: 2.7rem;
            padding-left: 0.2rem;
        }
        
        .auth-btn {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .auth-btn:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--primary-blue) 100%);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
            transform: translateY(-1px);
        }
        
        .auth-toggle {
            color: var(--primary-blue);
            cursor: pointer;
            transition: color 0.2s;
            text-align: center;
            display: block;
            margin-top: 1.2rem;
            font-weight: 500;
        }
        
        .auth-toggle:hover {
            color: var(--primary-blue-dark);
        }
        
        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
            font-weight: 500;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .google-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            background: var(--bg-light);
            color: var(--text-dark);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            margin-bottom: 1.2rem;
            margin-top: 0.2rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .google-btn:hover {
            background: var(--bg-white);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            transform: translateY(-1px);
        }
        
        .google-btn img {
            margin-right: 0.5rem;
            background: #fff;
            border-radius: 50%;
            padding: 2px;
        }
        
        .divider-or {
            width: 100%;
            text-align: center;
            margin: 1.1rem 0 1.2rem 0;
            position: relative;
        }
        
        .divider-or span {
            background: var(--bg-white);
            color: var(--text-light);
            padding: 0 1.1em;
        }
        
        .divider-or::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-light);
            z-index: -1;
        }
        
        .radio-group {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin: 1rem 0;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
            cursor: pointer;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            transition: all 0.3s ease;
        }
        
        .radio-group label:hover {
            background: var(--bg-white);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .radio-group input[type="radio"] {
            accent-color: var(--primary-blue);
            transform: scale(1.2);
        }
        
        .radio-group input[type="radio"]:checked + span {
            color: var(--primary-blue);
            font-weight: 600;
        }
        
        /* Fix for icon/label/input overlap */
        .icon-input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .icon-input-wrapper .form-control {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            color: var(--text-dark);
            font-size: 1rem;
            font-weight: 500;
            padding-left: 2.7rem;
            padding-top: 0.85rem;
            padding-bottom: 0.85rem;
            box-shadow: var(--shadow-md);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        
        .icon-input-wrapper .form-control:focus {
            border: 2px solid var(--primary-blue);
            background: var(--bg-white);
            box-shadow: 0 0 0 2px #2563eb22;
            color: var(--text-dark);
        }
        
        .icon-input-wrapper .form-control::placeholder {
            color: var(--text-light);
            font-weight: 400;
            opacity: 1;
        }
        
        .icon-input-wrapper .input-icon {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            z-index: 2;
            pointer-events: none;
            font-size: 1.1rem;
        }

        .back-btn {
            background: var(--bg-white);
            border: 2px solid var(--border-light);
            color: var(--text-dark);
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .back-btn:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
        }

        .back-btn i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-2px);
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .brand-accent {
            color: var(--primary-blue);
        }

        .brand-icon {
            color: var(--primary-blue);
        }
        
        @media (max-width: 600px) {
            .auth-card {
                padding: 1.5rem 1rem;
                max-width: 98vw;
                margin: 1rem;
            }
            
            .back-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
            
            .brand-title {
                font-size: 1.5rem;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .radio-group label {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="auth-bg"></div>
    <div class="auth-card">
        <button type="button" onclick="goBack()" class="back-btn">
            <i class="fas fa-arrow-left me-2"></i>Go Back
        </button>
        
        <div style="text-align:center; margin-bottom: 2rem;">
            <span class="brand-title">
                <i class="fas fa-rocket me-2 brand-icon"></i>Job<span class="brand-accent">Portal</span>
            </span>
        </div>
        
        <div class="auth-tabs">
            <button class="auth-tab active" id="loginTab" onclick="showTab('login')" type="button">Login</button>
            <button class="auth-tab" id="registerTab" onclick="showTab('register')" type="button">Register</button>
        </div>
        
        <div id="loginForm">
            <?php if (!empty($error_message)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form action="/php/login.php" method="post" autocomplete="on">
                <div class="icon-input-wrapper">
                    <i class="fa fa-user input-icon"></i>
                    <input type="text" class="form-control" id="login-username" name="username" placeholder="Username or Email" required>
                </div>
                <div class="icon-input-wrapper">
                    <i class="fa fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>
            <span class="auth-toggle" onclick="showTab('register')">Don't have an account? <b>Sign up here</b></span>
        </div>
        
        <div id="registerForm" style="display:none;">
            <form action="/php/register.php" method="post" autocomplete="on">
                <div class="icon-input-wrapper">
                    <i class="fa fa-user input-icon"></i>
                    <input type="text" class="form-control" id="register-username" name="username" placeholder="Full Name" required>
                </div>
                <div class="icon-input-wrapper">
                    <i class="fa fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="register-email" name="email" placeholder="Email Address" required>
                </div>
                <div class="icon-input-wrapper">
                    <i class="fa fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="register-password" name="password" placeholder="Password" required>
                </div>
                <div class="icon-input-wrapper">
                    <i class="fa fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="register-confirm" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="radio-group mb-3">
                    <label><input type="radio" name="user_type" value="A" required> <span>Recruiter</span></label>
                    <label><input type="radio" name="user_type" value="B" required> <span>Job Seeker</span></label>
                </div>
                <button type="submit" class="auth-btn">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            <span class="auth-toggle" onclick="showTab('login')">Already have an account? <b>Sign in here</b></span>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/main/index.php';
            }
        }
        
        function showTab(tab) {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (tab === 'login') {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                loginForm.style.display = '';
                registerForm.style.display = 'none';
            } else {
                loginTab.classList.remove('active');
                registerTab.classList.add('active');
                loginForm.style.display = 'none';
                registerForm.style.display = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.auth-card').style.opacity = '1';
            document.querySelector('.auth-card').style.transform = 'translateY(0)';
        });
    </script>
</body>
</html> 