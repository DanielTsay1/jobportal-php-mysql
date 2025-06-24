<?php
session_start();
require_once '../php/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;
    $user_type = $_POST['user_type'] ?? null;

    if (empty($username) || empty($password) || empty($user_type)) {
        $error_message = 'All fields are required.';
    } else {
        if ($user_type === 'A') { // Recruiter
            $sql = "SELECT recid, username, password, compid FROM recruiter WHERE username = ?";
            $redirect_path = 'recruiter.php';
            $id_field = 'recid';
        } else { // Job Seeker
            $sql = "SELECT userid, username, password FROM user WHERE username = ?";
            $redirect_path = 'job-list.php';
            $id_field = 'userid';
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_type'] = $user_type;
                $_SESSION['username'] = $user['username'];
                $_SESSION['userid'] = $user[$id_field];
                if ($user_type === 'A') {
                    $_SESSION['compid'] = $user['compid'];
                }
                header("Location: $redirect_path");
                exit;
            } else {
                $error_message = 'Invalid password.';
            }
        } else {
            $error_message = 'No user found with that username.';
        }
        $stmt->close();
    }
    $conn->close();
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
      .radio-group {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.2rem;
      }
      .radio-group label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 1rem;
        color: #1976d2;
        font-weight: 500;
      }
      .radio-group input[type="radio"] {
        accent-color: #1976d2 !important;
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
    </style>
  </head>
  <body>
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
          <div class="radio-group mb-3">
            <label><input type="radio" name="user_type" value="A" required> <span>Recruiter</span></label>
            <label><input type="radio" name="user_type" value="B" required> <span>Job Seeker</span></label>
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
      // Optional: Animate card in
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.auth-card').style.opacity = '1';
        document.querySelector('.auth-card').style.transform = 'translateY(0)';
      });
    </script>
  </body>
</html> 