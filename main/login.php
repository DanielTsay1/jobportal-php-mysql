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
    <link rel="stylesheet" href="/css/loginpage.css" />
    <title>JobPortal - Login / Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
      html, body {
        height: 100%;
        min-height: 100vh;
      }
      body {
        font-family: 'Inter', Arial, sans-serif !important;
        background: linear-gradient(135deg, #181828 0%, #23233a 100%);
        color: #f3f3fa;
        min-height: 100vh;
        width: 100vw;
        background-attachment: fixed;
        background-repeat: no-repeat;
        background-size: cover;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
      }
      
      /* Animated background elements */
      .auth-bg {
        position: absolute;
        top: 0; left: 0; width: 100vw; height: 100%;
        z-index: 0;
        pointer-events: none;
        background: radial-gradient(ellipse at 60% 20%, #7b1fa2 0%, transparent 60%),
                    radial-gradient(ellipse at 20% 80%, #1976d2 0%, transparent 70%);
        animation: bgMove 12s ease-in-out infinite alternate;
        filter: blur(2px) brightness(0.8);
        opacity: 0.7;
      }
      
      @keyframes bgMove {
          0% { background-position: 60% 20%, 20% 80%; }
          100% { background-position: 65% 25%, 15% 75%; }
      }
      
      .auth-card {
        background: rgba(255,255,255,0.10);
        backdrop-filter: blur(18px) saturate(1.2);
        border: 1.5px solid rgba(255,255,255,0.13);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(30,20,60,0.13);
        padding: 2.5rem 2rem 2rem 2rem;
        max-width: 400px;
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
        background: rgba(255,255,255,0.1);
        border: 1.5px solid rgba(255,255,255,0.2);
        font-size: 1.1rem;
        font-weight: 600;
        color: #f3f3fa;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(8px);
      }
      
      .auth-tab.active, .auth-tab:focus {
        background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
        color: #fff;
        border-color: #00e0d6;
        box-shadow: 0 4px 16px rgba(0,224,214,0.2);
      }
      
      .form-floating .form-control {
        border-radius: 14px;
        border: 1.5px solid rgba(255,255,255,0.2);
        background: rgba(255,255,255,0.1);
        color: #f3f3fa;
        font-size: 1rem;
        margin-bottom: 1.1rem;
        padding-left: 2.7rem;
        backdrop-filter: blur(8px);
      }
      
      .form-floating .form-control:focus {
        border-color: #00e0d6;
        box-shadow: 0 0 0 0.2rem rgba(0,224,214,0.25);
        background: rgba(255,255,255,0.15);
      }
      
      .form-floating .form-control::placeholder {
        color: #b3b3c6;
      }
      
      .form-floating > .fa {
        position: absolute;
        left: 1.1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #00e0d6;
        z-index: 2;
        pointer-events: none;
        font-size: 1.1rem;
      }
      
      .form-floating label {
        color: #b3b3c6;
        font-size: 1rem;
        left: 2.7rem;
        padding-left: 0.2rem;
      }
      
      .auth-btn {
        background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 0.7rem 2.2rem;
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        box-shadow: 0 4px 16px rgba(0,224,214,0.2);
        transition: all 0.3s ease;
        width: 100%;
      }
      
      .auth-btn:hover {
        background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
        box-shadow: 0 8px 25px rgba(0,224,214,0.3);
        transform: translateY(-2px);
      }
      
      .auth-toggle {
        color: #00e0d6;
        cursor: pointer;
        transition: color 0.2s;
        text-align: center;
        display: block;
        margin-top: 1.2rem;
        font-weight: 500;
      }
      
      .auth-toggle:hover {
        color: #7b3fe4;
      }
      
      .alert.error {
        background: rgba(255,107,107,0.1);
        color: #ff6b6b;
        border-radius: 12px;
        padding: 0.7rem 1.2rem;
        margin-bottom: 1rem;
        font-weight: 500;
        border: 1.5px solid rgba(255,107,107,0.3);
        backdrop-filter: blur(8px);
      }
      
      .google-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.7rem;
        background: rgba(255,255,255,0.1);
        color: #f3f3fa;
        border: 1.5px solid rgba(255,255,255,0.2);
        border-radius: 20px;
        font-size: 1.08rem;
        font-weight: 600;
        padding: 0.7rem 1.5rem;
        margin-bottom: 1.2rem;
        margin-top: 0.2rem;
        box-shadow: 0 2px 8px rgba(30,20,60,0.10);
        transition: all 0.3s ease;
        cursor: pointer;
        backdrop-filter: blur(8px);
      }
      
      .google-btn:hover {
        background: rgba(255,255,255,0.15);
        border-color: #00e0d6;
        transform: translateY(-2px);
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
        background: rgba(255,255,255,0.1);
        color: #b3b3c6;
        padding: 0 1.1em;
        backdrop-filter: blur(8px);
      }
      
      .divider-or::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: rgba(255,255,255,0.2);
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
        color: #f3f3fa;
        font-weight: 500;
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 15px;
        background: rgba(255,255,255,0.1);
        border: 1.5px solid rgba(255,255,255,0.2);
        transition: all 0.3s ease;
        backdrop-filter: blur(8px);
      }
      
      .radio-group label:hover {
        background: rgba(255,255,255,0.15);
        border-color: #00e0d6;
      }
      
      .radio-group input[type="radio"] {
        accent-color: #00e0d6;
        transform: scale(1.2);
      }
      
      .radio-group input[type="radio"]:checked + span {
        color: #00e0d6;
        font-weight: 600;
      }
      
      @media (max-width: 600px) {
        .auth-card {
          padding: 1.2rem 0.5rem 1rem 0.5rem;
          max-width: 98vw;
        }
      }
      
      /* Fix for icon/label/input overlap */
      .icon-input-wrapper {
        position: relative;
        margin-bottom: 1.1rem;
      }
      .icon-input-wrapper .form-control {
        background: rgba(255,255,255,0.13);
        border: 1.5px solid rgba(255,255,255,0.18);
        border-radius: 16px;
        color: #f3f3fa;
        font-size: 1.08rem;
        font-weight: 500;
        padding-left: 2.7rem;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
        box-shadow: 0 2px 12px rgba(123,63,228,0.08);
        transition: border 0.2s, box-shadow 0.2s, background 0.2s;
        outline: none;
        backdrop-filter: blur(8px) saturate(1.1);
      }
      .icon-input-wrapper .form-control:focus {
        border: 1.5px solid #00e0d6;
        background: rgba(255,255,255,0.18);
        box-shadow: 0 4px 24px rgba(0,224,214,0.13);
        color: #fff;
      }
      .icon-input-wrapper .form-control::placeholder {
        color: #b3b3c6;
        font-weight: 400;
        opacity: 1;
        letter-spacing: 0.01em;
      }
      .icon-input-wrapper .input-icon {
        position: absolute;
        left: 1.1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #00e0d6;
        z-index: 2;
        pointer-events: none;
        font-size: 1.18rem;
        opacity: 0.95;
      }
    </style>
  </head>
  <body>
    <div class="auth-bg"></div>
    <div class="auth-card">
      <div style="text-align:center; margin-bottom: 1.2rem;">
        <span style="font-size:2rem; font-weight:700; color:#fff; letter-spacing:-1px;">
          <i class="fas fa-rocket me-2" style="color:#00e0d6;"></i>Job<span style="color:#00e0d6;">Portal</span>
        </span>
      </div>
      <div class="auth-tabs">
        <button class="auth-tab active" id="loginTab" onclick="showTab('login')" type="button">Login</button>
        <button class="auth-tab" id="registerTab" onclick="showTab('register')" type="button">Register</button>
      </div>
      <div id="loginForm">
        <?php if (!empty($error_message)): ?>
                <div class="alert error">
                  <p><?= htmlspecialchars($error_message) ?></p>
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
          <button type="submit" class="auth-btn">Sign In</button>
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
          <button type="submit" class="auth-btn">Create Account</button>
        </form>
        <span class="auth-toggle" onclick="showTab('login')">Already have an account? <b>Sign in here</b></span>
      </div>
    </div>
    <script>
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