<?php
// Admin Login Controller - Divine Reiki & Healing Center
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$message = '';

if (isset($_GET['logout'])) {
    $message = 'You have logged out successfully.';
}

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'] ?? '';

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Login authentication error: " . $e->getMessage());
            $error = 'A database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Shree Sai Reiki</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        if (typeof lucide === 'undefined') {
            document.write('<script src="assets/js/lucide.min.js"><\/script>');
        }
    </script>
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-color: var(--admin-bg);
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(124, 107, 196, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(201, 168, 76, 0.08) 0%, transparent 45%),
                linear-gradient(135deg, #0d0b1a 0%, #1a1035 100%);
            position: relative;
            overflow: hidden;
        }

        /* Floating subtle decorative particles */
        .login-particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0.4;
            animation: particleFloat 12s infinite ease-in-out alternate;
        }
        .particle-1 {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 8%;
            background: radial-gradient(circle, rgba(124, 107, 196, 0.25) 0%, transparent 70%);
        }
        .particle-2 {
            width: 180px;
            height: 180px;
            bottom: 12%;
            right: 8%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.2) 0%, transparent 70%);
            animation-duration: 16s;
        }
        .particle-3 {
            width: 80px;
            height: 80px;
            top: 75%;
            left: 20%;
            background: radial-gradient(circle, rgba(124, 107, 196, 0.2) 0%, transparent 70%);
            animation-duration: 9s;
        }

        @keyframes particleFloat {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-24px) scale(1.08); }
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(30, 21, 69, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.55),
                        0 0 30px rgba(124, 107, 196, 0.08);
            position: relative;
            z-index: 10;
            animation: loginCardFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes loginCardFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-logo-box {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-logo-box img {
            max-height: 54px;
            width: auto;
            display: block;
            margin: 0 auto 10px;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.5));
        }

        .login-portal-tag {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 2.2px;
            color: var(--gold);
            font-weight: 700;
        }

        .login-title {
            font-size: 1.65rem;
            font-weight: 700;
            color: #ffffff;
            margin: 6px 0 6px;
            letter-spacing: 0.3px;
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 26px;
            text-align: center;
        }

        .password-field-wrapper {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
            line-height: 1;
            transition: color 0.2s;
        }

        .password-toggle-btn:hover {
            color: var(--gold);
        }

        .w-100 {
            width: 100%;
        }

        .btn-signin {
            padding: 13px;
            font-size: 0.96rem;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 4px 18px rgba(201, 168, 76, 0.3);
        }

        .login-footer-nav {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(42, 33, 85, 0.7);
            font-size: 0.84rem;
        }

        .login-footer-nav a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-footer-nav a:hover {
            color: var(--gold);
        }
    </style>
</head>
<body class="admin-body">

<div class="login-wrapper">
    <!-- Subtle floating background particles -->
    <div class="login-particle particle-1"></div>
    <div class="login-particle particle-2"></div>
    <div class="login-particle particle-3"></div>

    <div class="login-card">
        <div class="login-logo-box">
            <img src="../assets/images/logo.png" alt="Shree Sai Reiki Center">
            <span class="login-portal-tag">SHREE SAI REIKI</span>
            <h1 class="login-title">Admin Login</h1>
        </div>

        <p class="login-subtitle">Sign in to access your administrative dashboard and controls</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error flex items-center gap-2">
                <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success flex items-center gap-2">
                <i data-lucide="check-circle-2" style="width: 16px; height: 16px;"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="usernameInput" class="form-label">Username</label>
                <input 
                    type="text" 
                    id="usernameInput" 
                    name="username" 
                    class="form-control" 
                    placeholder="Enter your admin username" 
                    value="<?= isset($username) ? htmlspecialchars($username) : '' ?>"
                    required 
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="passwordInput" class="form-label">Password</label>
                <div class="password-field-wrapper">
                    <input 
                        type="password" 
                        id="passwordInput" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password" 
                        required
                        style="padding-right: 42px;"
                    >
                    <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility" title="Show/Hide Password">
                        <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-gold btn-signin w-100">
                Sign In
            </button>
        </form>

        <div class="login-footer-nav">
            <a href="../index.php" class="flex items-center justify-center gap-1">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Return to Main Website
            </a>
        </div>
    </div>
</div>

<script>
// Initialize Lucide Icons
if (window.lucide) {
    lucide.createIcons();
}

// Show/Hide Password Toggle
const toggleBtn = document.getElementById('togglePasswordBtn');
const passwordInput = document.getElementById('passwordInput');

if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function() {
        const currentType = passwordInput.getAttribute('type');
        if (currentType === 'password') {
            passwordInput.setAttribute('type', 'text');
            toggleBtn.innerHTML = '<i data-lucide="eye-off" style="width: 18px; height: 18px;"></i>';
        } else {
            passwordInput.setAttribute('type', 'password');
            toggleBtn.innerHTML = '<i data-lucide="eye" style="width: 18px; height: 18px;"></i>';
        }
        if (window.lucide) lucide.createIcons();
    });
}
</script>

</body>
</html>
