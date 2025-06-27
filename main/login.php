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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      html, body {
        height: 100%;
        min-height: 100vh;
      }
      body {
        font-family: 'Poppins', Arial, sans-serif !important;
        background: linear-gradient(135deg, #ffffff 0%, #e3f0ff 60%, #ede7f6 100%);
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
      .auth-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(30, 144, 255, 0.10);
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
        background: none;
        border: none;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1976d2;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        transition: background 0.2s, color 0.2s;
        cursor: pointer;
      }
      .auth-tab.active, .auth-tab:focus {
        background: linear-gradient(90deg, #1976d2 0%, #7b1fa2 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(30, 144, 255, 0.08);
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
        color: #1976d2;
        z-index: 2;
      }
      .form-floating label {
        color: #888;
        font-size: 1rem;
        left: 2.2rem;
      }
      .auth-btn {
        background: linear-gradient(90deg, #1976d2 0%, #7b1fa2 100%);
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 0.7rem 2.2rem;
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        box-shadow: 0 2px 8px rgba(30, 144, 255, 0.08);
        transition: background 0.2s, box-shadow 0.2s;
        width: 100%;
      }
      .auth-btn:hover {
        background: linear-gradient(90deg, #1565c0 0%, #512da8 100%);
        box-shadow: 0 4px 16px rgba(123, 31, 162, 0.10);
      }
      .auth-toggle {
        color: #7b1fa2;
        cursor: pointer;
        transition: color 0.2s;
        text-align: center;
        display: block;
        margin-top: 1.2rem;
      }
      .auth-toggle:hover {
        color: #1976d2;
      }
      .alert.error {
        background: #ede7f6;
        color: #7b1fa2;
        border-radius: 12px;
        padding: 0.7rem 1.2rem;
        margin-bottom: 1rem;
        font-weight: 500;
        border: 1.5px solid #b3d1ff;
      }
      @media (max-width: 600px) {
        .auth-card {
          padding: 1.2rem 0.5rem 1rem 0.5rem;
          max-width: 98vw;
        }
      }
      .google-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.7rem;
        background: #181828;
        color: #fff;
        border: 1.5px solid #44445a;
        border-radius: 20px;
        font-size: 1.08rem;
        font-weight: 600;
        padding: 0.7rem 1.5rem;
        margin-bottom: 1.2rem;
        margin-top: 0.2rem;
        box-shadow: 0 2px 8px rgba(30, 20, 60, 0.10);
        transition: background 0.18s, border 0.18s;
        cursor: pointer;
      }
      .google-btn:hover {
        background: #23233a;
        border-color: #7b1fa2;
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
        background: #fff;
        color: #888;
        padding: 0 1.1em;
        font-size: 1rem;
        position: relative;
        z-index: 2;
      }
      .divider-or:before {
        content: '';
        display: block;
        position: absolute;
        top: 50%;
        left: 0;
        width: 100%;
        height: 1px;
        background: #e3e3ef;
        z-index: 1;
      }
      .google-btn-white {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.7rem;
        background: #fff;
        color: #222;
        border: 1.5px solid #dadce0;
        border-radius: 20px;
        font-size: 1.08rem;
        font-weight: 600;
        padding: 0.7rem 1.5rem;
        margin-top: 2.2rem;
        margin-bottom: 0.2rem;
        box-shadow: 0 2px 8px rgba(30, 20, 60, 0.08);
        transition: background 0.18s, border 0.18s;
        cursor: pointer;
      }
      .google-btn-white:hover {
        background: #f7f7f7;
        border-color: #7b1fa2;
      }
      .google-btn-white img {
        margin-right: 0.5rem;
        background: transparent;
        border-radius: 50%;
        padding: 2px;
      }
      .login-bg-animated {
        position: fixed;
        top: 0; left: 0; width: 100vw; height: 100vh;
        z-index: 0;
        pointer-events: none;
        background: radial-gradient(ellipse at 60% 20%, #7b1fa2 0%, transparent 60%),
                    radial-gradient(ellipse at 20% 80%, #1976d2 0%, transparent 70%),
                    linear-gradient(135deg, #181828 0%, #23233a 100%);
        animation: bgMove 12s ease-in-out infinite alternate;
      }
      @keyframes bgMove {
        0% { background-position: 60% 20%, 20% 80%, 0 0; }
        100% { background-position: 65% 25%, 15% 75%, 100% 100%; }
      }
      .login-bg-animated::before, .login-bg-animated::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        opacity: 0.18;
        filter: blur(2px);
        animation: floatShape 10s ease-in-out infinite alternate;
      }
      .login-bg-animated::before {
        width: 420px; height: 420px;
        left: -120px; top: 10vh;
        background: linear-gradient(135deg, #7b1fa2 0%, #1976d2 100%);
        animation-delay: 0s;
      }
      .login-bg-animated::after {
        width: 320px; height: 320px;
        right: -100px; bottom: 8vh;
        background: linear-gradient(135deg, #1976d2 0%, #7b1fa2 100%);
        animation-delay: 2s;
      }
      @keyframes floatShape {
        0% { transform: translateY(0) scale(1); }
        100% { transform: translateY(-40px) scale(1.08); }
      }
    </style>
  </head>
  <body>
    <div class="login-bg-animated"></div>
    <div class="auth-card">
      <div style="text-align:center; margin-bottom: 1.2rem;">
        <span style="font-size:2rem; font-weight:700; color:#1976d2; letter-spacing:-1px;">
          Job<span style="color:#7b1fa2;">Portal</span>
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
          <div class="form-floating position-relative mb-3">
            <i class="fa fa-user"></i>
            <input type="text" class="form-control" id="login-username" name="username" placeholder="Username or Email" required>
            <label for="login-username">Username or Email</label>
                </div>
          <div class="form-floating position-relative mb-3">
            <i class="fa fa-lock"></i>
            <input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
            <label for="login-password">Password</label>
                </div>
          <button type="submit" class="auth-btn">Sign In</button>
          </form>
        <span class="auth-toggle" onclick="showTab('register')">Don't have an account? <b>Sign up here</b></span>
        </div>
      <div id="registerForm" style="display:none;">
        <form action="/php/register.php" method="post" autocomplete="on">
          <div class="form-floating position-relative mb-3">
            <i class="fa fa-user"></i>
            <input type="text" class="form-control" id="register-username" name="username" placeholder="Full Name" required>
            <label for="register-username">Full Name</label>
                </div>
          <div class="form-floating position-relative mb-3">
            <i class="fa fa-envelope"></i>
            <input type="email" class="form-control" id="register-email" name="email" placeholder="Email Address" required>
            <label for="register-email">Email Address</label>
                </div>
          <div class="form-floating position-relative mb-3">
            <i class="fa fa-lock"></i>
            <input type="password" class="form-control" id="register-password" name="password" placeholder="Password" required>
            <label for="register-password">Password</label>
                </div>
          <div class="form-floating position-relative mb-3">
            <i class="fa fa-lock"></i>
            <input type="password" class="form-control" id="register-confirm" name="confirm_password" placeholder="Confirm Password" required>
            <label for="register-confirm">Confirm Password</label>
                </div>
          <div class="radio-group mb-3">
            <label><input type="radio" name="user_type" value="A" required> <span>Recruiter</span></label>
            <label><input type="radio" name="user_type" value="B" required> <span>Job Seeker</span></label>
                </div>
          <button type="submit" class="auth-btn">Create Account</button>
          </form>
        <span class="auth-toggle" onclick="showTab('login')">Already have an account? <b>Sign in here</b></span>
      </div>
      <button type="button" class="google-btn-white" onclick="alert('Google OAuth integration coming soon!')">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" style="width:22px; height:22px; vertical-align:middle; margin-right:10px;">
        <span>Continue with Google</span>
      </button>
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
        document.querySelector('.login-bg-animated').style.opacity = '1';
      });
    </script>
  </body>
</html> 