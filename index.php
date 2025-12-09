<?php
session_start();
require 'db.php';

// Authentication Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'signup') {
        // Sign Up Logic
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($username && $password && $confirm_password) {
            if ($password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } else if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username already exists. Please choose another.';
                } else {
                    // Create new user with 'user' role
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $email = trim($_POST['email'] ?? '');
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Please enter a valid email address.";
                    // Show error to user
                    }

                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
                    if ($stmt->execute([$username, $hashed_password,$email])) {
                        $success = 'Account created successfully! Please log in.';
                    } else {
                        $error = 'Failed to create account. Please try again.';
                    }
                }
            }
        } else {
            $error = 'Please fill in all fields.';
        }
    } else {
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['userid'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Please enter both username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Maintenance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      
      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
        position: relative;
        background: #0f172a;
      }
      
      /* Background image with overlay */
      body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('es.jpg'); 
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.15; 
        z-index: 0;
      }
      
      /* Gradient overlay */
      body::after {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
        z-index: 0;
      }
      
      .navbar {
        background: rgba(15, 23, 42, 0.8) !important;
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        min-height: 70px;
        position: relative;
        z-index: 100;
      }
      
      .navbar .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        padding: 10px 28px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
      }
      
      .navbar .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
      }
      
      .navbar .btn-outline-primary {
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 10px 28px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
      }
      
      .navbar .btn-outline-primary:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
        color: #fff;
      }
      
      .login-card {
        display: none;
        margin: 60px auto 0 auto;
        max-width: 460px;
        padding: 48px;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        z-index: 10;
        position: relative;
        animation: fadeInUp 0.6s ease forwards;
      }
      
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .login-card h4 {
        color: #fff;
        font-weight: 700;
        font-size: 28px;
        margin-bottom: 32px;
        letter-spacing: -0.5px;
      }
      
      .toggle-form {
        text-align: center;
        margin-top: 24px;
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
      }
      
      .toggle-form a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
      }
      
      .toggle-form a:hover {
        color: #60a5fa;
      }
      
      .login-card .form-label {
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 8px;
      }
      
      .login-card .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        padding: 14px 16px;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s ease;
      }
      
      .login-card .form-control:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        color: #fff;
      }
      
      .login-card .form-control::placeholder {
        color: rgba(255, 255, 255, 0.4);
      }
      
      .login-card .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        padding: 14px;
        font-weight: 600;
        font-size: 16px;
        border-radius: 12px;
        margin-top: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
      }
      
      .login-card .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
      }
      
      .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
      }
      
      .alert-success {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #86efac;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
      }
      
      /* Animated system title */
      .system-title {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        z-index: 5;
        transition: all 0.8s cubic-bezier(0.768, 0, 0.216, 1);
      }
      
      .system-title.left {
        left: 80px;
        top: 50%;
        transform: translateY(-50%);
      }

      .system-title .main-text {
        font-size: 56px;
        font-weight: 800;
        background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -2px;
        line-height: 1.1;
        text-align: center;
        opacity: 0;
        animation: fadeInUp 1s ease forwards 0.2s;
      }

      .system-title .system-text {
        font-size: 56px;
        font-weight: 800;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-top: 4px;
        letter-spacing: -2px;
        line-height: 1.1;
        text-align: center;
        opacity: 0;
        animation: fadeInUp 1s ease forwards 0.4s, gradientShift 3s ease infinite 1.4s;
        background-size: 200% 200%;
      }
      
      .system-title .subtitle {
        color: rgba(255, 255, 255, 0.6);
        font-size: 18px;
        font-weight: 500;
        margin-top: 16px;
        text-align: center;
        letter-spacing: 0.5px;
        opacity: 0;
        animation: fadeInUp 1s ease forwards 0.6s;
      }
      
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      @keyframes gradientShift {
        0%, 100% {
          background-position: 0% 50%;
        }
        50% {
          background-position: 100% 50%;
        }
      }
      
      /* Text glowing effect on hover */
      .system-title .system-text:hover {
        animation: fadeInUp 1s ease forwards 0.4s, gradientShift 1.5s ease infinite, textGlow 1.5s ease infinite;
      }
      
      @keyframes textGlow {
        0%, 100% {
          filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
        }
        50% {
          filter: drop-shadow(0 0 20px rgba(139, 92, 246, 0.8));
        }
      }
      
      /* Responsive design */
      @media (max-width: 768px) {
        .system-title .main-text,
        .system-title .system-text {
          font-size: 36px;
        }
        
        .system-title.left {
          left: 50%;
          top: 120px;
          transform: translate(-50%, 0);
        }
        
        .login-card {
          margin-top: 240px;
          padding: 32px 24px;
        }
        
        .system-title .subtitle {
          font-size: 16px;
        }
      }
      
      /* Floating animation for decorative elements */
      @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
      }
      
      .decorative-circle {
        position: fixed;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
        pointer-events: none;
        z-index: 1;
      }
      
      .circle-1 {
        width: 400px;
        height: 400px;
        top: -100px;
        right: -100px;
        animation: float 8s ease-in-out infinite;
      }
      
      .circle-2 {
        width: 300px;
        height: 300px;
        bottom: -80px;
        left: -80px;
        animation: float 6s ease-in-out infinite 1s;
      }
    </style>
</head>
<body>
    <!-- Decorative elements -->
    <div class="decorative-circle circle-1"></div>
    <div class="decorative-circle circle-2"></div>

    <!-- TOP NAV -->
    <nav class="navbar navbar-expand-lg">
      <div class="container-fluid">
        <div class="ms-auto">
          <button id="showLoginBtn" type="button" class="btn btn-primary me-2">Login</button>
          <a href="about.php" class="btn btn-outline-primary">About</a>
        </div>
      </div>
    </nav>
    
    <!-- Animated Title -->
    <div class="system-title" id="mainTitle">
        <span class="main-text">Maintenance Management</span>
        <span class="system-text">System</span>
        <span class="subtitle">Streamline your operations with intelligent maintenance tracking</span>
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card" id="loginCard">
        <h4 class="text-center" id="formTitle">Welcome</h4>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" id="timed-alert"><?= htmlspecialchars($error) ?></div>
            <script>
                setTimeout(function(){ var el = document.getElementById('timed-alert'); if(el){ el.style.display='none'; }}, 3500);
            </script>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" id="success-alert"><?= htmlspecialchars($success) ?></div>
            <script>
                setTimeout(function(){ var el = document.getElementById('success-alert'); if(el){ el.style.display='none'; }}, 3500);
            </script>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="index.php" id="loginForm">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>
        
        <!-- Sign Up Form (Hidden by default) -->
        <form method="POST" action="index.php" id="signupForm" style="display: none;">
            <input type="hidden" name="action" value="signup">
            <div class="mb-3">
                <label for="signup_username" class="form-label">Username</label>
                <input type="text" name="username" id="signup_username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="signupemail" class="form-label">Email</label>
                <input type="email" name="email" id="signupemail" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="signup_password" class="form-label">Password</label>
                <input type="password" class="form-control" id="signuppassword" name="password" required placeholder="At least 6 characters" minlength="6">
                <small class="form-text text-muted">Password must be at least 6 characters long.</small>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>
        
        <div class="toggle-form">
            <span id="toggleText">Don't have an account? <a href="#" id="toggleLink">Sign up</a></span>
        </div>
    </div>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Slide center title to left and show login card when Login is clicked
  document.getElementById('showLoginBtn').addEventListener('click', function() {
    var title = document.getElementById('mainTitle');
    title.classList.add('left');
    var card = document.getElementById('loginCard');
    setTimeout(function(){
      card.style.display = 'block';
      card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 600);
  });
  
  // Toggle between login and signup forms
  document.getElementById('toggleLink').addEventListener('click', function(e) {
    e.preventDefault();
    var loginForm = document.getElementById('loginForm');
    var signupForm = document.getElementById('signupForm');
    var formTitle = document.getElementById('formTitle');
    var toggleText = document.getElementById('toggleText');
    
    if (loginForm.style.display !== 'none') {
      // Switch to signup
      loginForm.style.display = 'none';
      signupForm.style.display = 'block';
      formTitle.textContent = 'Create Account';
      toggleText.innerHTML = 'Already have an account? <a href="#" id="toggleLink">Sign in</a>';
    } else {
      // Switch to login
      loginForm.style.display = 'block';
      signupForm.style.display = 'none';
      formTitle.textContent = 'Welcome Back';
      toggleText.innerHTML = 'Don\'t have an account? <a href="#" id="toggleLink">Sign up</a>';
    }
    
    // Re-attach event listener to the new link
    document.getElementById('toggleLink').addEventListener('click', arguments.callee);
  });
</script>
</body>
</html>