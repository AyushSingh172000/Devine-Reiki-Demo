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

// Fetch branding settings dynamically
$siteSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $siteSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$rawFavicon = !empty($siteSettings['favicon_path']) ? ltrim($siteSettings['favicon_path'], '/') : 'assets/images/favicon-circle.png';
$adminFavicon = '../' . $rawFavicon;
$faviconVersion = file_exists(__DIR__ . '/../' . $rawFavicon) ? filemtime(__DIR__ . '/../' . $rawFavicon) : time();

$rawLogo = !empty($siteSettings['logo_path']) ? ltrim($siteSettings['logo_path'], '/') : 'assets/images/reikilogo1.png';
$adminLogo = '../' . $rawLogo;
$logoVersion = file_exists(__DIR__ . '/../' . $rawLogo) ? filemtime(__DIR__ . '/../' . $rawLogo) : time();

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
    <title>Admin Login — Reiki Bliss</title>
    <meta name="robots" content="noindex, nofollow">
    <!-- Favicon (Matched with Website) -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($adminFavicon) ?>?v=<?= $faviconVersion ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($adminFavicon) ?>?v=<?= $faviconVersion ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($adminFavicon) ?>?v=<?= $faviconVersion ?>">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- Lucide Icons -->
    <script src="assets/js/lucide.min.js"></script>
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(179, 139, 45, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.03) 0%, transparent 40%),
                linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            position: relative;
            overflow: hidden;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08),
                        0 2px 6px rgba(15, 23, 42, 0.04);
            position: relative;
            z-index: 10;
            animation: loginCardFadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes loginCardFadeIn {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-logo-box {
            text-align: center;
            margin-bottom: 24px;
        }

        .login-logo-box img {
            max-height: 54px;
            width: auto;
            display: block;
            margin: 0 auto 10px;
        }

        .login-portal-tag {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 2.2px;
            color: var(--gold);
            font-weight: 700;
        }

        .login-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            margin: 6px 0 4px;
            letter-spacing: -0.3px;
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 24px;
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
            box-shadow: 0 4px 14px rgba(179, 139, 45, 0.3);
        }

        .login-footer-nav {
            text-align: center;
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
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
    <div class="login-card">
        <div class="login-logo-box">
            <img src="<?= htmlspecialchars($adminLogo) ?>?v=<?= $logoVersion ?>" alt="Reiki Bliss">
            <span class="login-portal-tag">REIKI BLISS</span>
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
