<?php
// Admin Login Controller - Divine Reiki & Healing Center
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/auth-rate-limiter.php';

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

// Social / WhatsApp Open Graph Image for Admin Portal
$rawAdminOg = !empty($siteSettings['og_image_path']) ? ltrim($siteSettings['og_image_path'], '/') : (!empty($siteSettings['logo_path']) ? ltrim($siteSettings['logo_path'], '/') : 'assets/images/logo.png');
$adminOgImageUrl = BASE_URL . $rawAdminOg;
$adminOgImageSecureUrl = preg_replace('/^http:\/\//i', 'https://', $adminOgImageUrl);
$adminOgVer = file_exists(__DIR__ . '/../' . $rawAdminOg) ? filemtime(__DIR__ . '/../' . $rawAdminOg) : time();
$adminOgImageUrlVersioned = $adminOgImageUrl . '?v=' . $adminOgVer;
$adminOgImageSecureUrlVersioned = $adminOgImageSecureUrl . '?v=' . $adminOgVer;

$error = '';
$message = '';
$isLocked = false;
$lockoutRetryAfter = 0;
$remainingAttempts = 5;

// Client IP & Initial Rate Limiter State
$clientIp = getClientIpAddress();
pruneOldLoginAttempts($pdo);

// Check if the client IP is currently locked
$initialIpCheck = checkLoginRateLimit($pdo, $clientIp, '');
if ($initialIpCheck['is_locked']) {
    $isLocked = true;
    $lockoutRetryAfter = $initialIpCheck['retry_after'];
    $remainingAttempts = 0;
    http_response_code(429);
    header('Retry-After: ' . $lockoutRetryAfter);
    $lockMinutes = ceil($lockoutRetryAfter / 60);
    $error = "Too many failed login attempts. Your access is temporarily locked. Please try again in {$lockMinutes} minute(s).";
} else {
    $remainingAttempts = $initialIpCheck['remaining_attempts'];
}

if (isset($_GET['logout'])) {
    $message = 'You have logged out successfully.';
}

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check rate limit for IP and username before executing authentication
    $prePostCheck = checkLoginRateLimit($pdo, $clientIp, $username);
    if ($prePostCheck['is_locked']) {
        $isLocked = true;
        $lockoutRetryAfter = $prePostCheck['retry_after'];
        $remainingAttempts = 0;
        http_response_code(429);
        header('Retry-After: ' . $lockoutRetryAfter);
        $lockMinutes = ceil($lockoutRetryAfter / 60);
        $error = "Too many failed login attempts. Your access is temporarily locked. Please try again in {$lockMinutes} minute(s).";
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                // Successful login: record and clear failed attempts
                recordLoginAttempt($pdo, $clientIp, $username, true);
                clearFailedLoginAttempts($pdo, $clientIp, $username);

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
                // Failed login attempt: record in rate limiter
                recordLoginAttempt($pdo, $clientIp, $username, false);

                // Re-evaluate rate limit immediately after recording failure
                $postCheck = checkLoginRateLimit($pdo, $clientIp, $username);
                if ($postCheck['is_locked']) {
                    $isLocked = true;
                    $lockoutRetryAfter = $postCheck['retry_after'];
                    $remainingAttempts = 0;
                    http_response_code(429);
                    header('Retry-After: ' . $lockoutRetryAfter);
                    $lockMinutes = ceil($lockoutRetryAfter / 60);
                    $error = "Too many failed login attempts. Your access has been locked for {$lockMinutes} minute(s).";
                } else {
                    $rem = $postCheck['remaining_attempts'];
                    $remainingAttempts = $rem;
                    $attemptWord = $rem === 1 ? '1 attempt remaining' : "{$rem} attempts remaining";
                    $error = "Invalid username or password. ({$attemptWord})";
                }
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
    
    <!-- Open Graph / WhatsApp / Social Share Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Reiki Bliss">
    <meta property="og:title" content="Reiki Bliss — Admin Portal">
    <meta property="og:description" content="Sign in to access your administrative dashboard and controls.">
    <meta property="og:image" content="<?= htmlspecialchars($adminOgImageUrlVersioned) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($adminOgImageSecureUrlVersioned) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="424">
    <meta property="og:image:height" content="424">
    <link rel="image_src" href="<?= htmlspecialchars($adminOgImageUrlVersioned) ?>">

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

        /* Rate Limiting & Lockout UI */
        .rate-limit-card {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            color: #9f1239;
            font-size: 0.88rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.05);
            animation: fadeIn 0.3s ease-out;
        }

        .rate-limit-card .timer-badge {
            display: inline-block;
            background: #e11d48;
            color: #ffffff;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            padding: 3px 9px;
            border-radius: 6px;
            letter-spacing: 0.8px;
            font-size: 0.95rem;
            margin-top: 6px;
        }

        .btn-signin:disabled,
        .form-control:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            background-color: #f8fafc;
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

        <?php if ($isLocked && $lockoutRetryAfter > 0): ?>
            <div class="rate-limit-card" id="lockoutNotice" data-retry-after="<?= (int)$lockoutRetryAfter ?>">
                <i data-lucide="shield-alert" style="width: 22px; height: 22px; color: #e11d48; flex-shrink: 0; margin-top: 2px;"></i>
                <div>
                    <div style="font-weight: 600; margin-bottom: 2px;">Access Temporarily Suspended</div>
                    <div>Maximum login attempts exceeded. Please wait:</div>
                    <div class="timer-badge" id="lockoutTimerDisplay">
                        <?= sprintf('%02d:%02d', floor($lockoutRetryAfter / 60), $lockoutRetryAfter % 60) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error) && !$isLocked): ?>
            <div class="alert alert-error flex items-center gap-2">
                <i data-lucide="alert-triangle" style="width: 16px; height: 16px; flex-shrink: 0;"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success flex items-center gap-2">
                <i data-lucide="check-circle-2" style="width: 16px; height: 16px; flex-shrink: 0;"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off" id="adminLoginForm">
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
                    <?= $isLocked ? 'disabled' : '' ?>
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
                        <?= $isLocked ? 'disabled' : '' ?>
                    >
                    <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility" title="Show/Hide Password" <?= $isLocked ? 'disabled' : '' ?>>
                        <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-gold btn-signin w-100" <?= $isLocked ? 'disabled' : '' ?>>
                <?= $isLocked ? 'Access Suspended' : 'Sign In' ?>
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
        if (passwordInput.disabled) return;
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

// Live Countdown Timer for Rate Limiting Lockout
const lockoutNotice = document.getElementById('lockoutNotice');
const timerDisplay = document.getElementById('lockoutTimerDisplay');
const submitBtn = document.querySelector('.btn-signin');
const usernameInput = document.getElementById('usernameInput');

if (lockoutNotice && timerDisplay) {
    let secondsLeft = parseInt(lockoutNotice.getAttribute('data-retry-after'), 10) || 0;

    const interval = setInterval(function() {
        secondsLeft--;
        if (secondsLeft <= 0) {
            clearInterval(interval);
            timerDisplay.textContent = '00:00';

            // Re-enable form fields
            if (usernameInput) usernameInput.removeAttribute('disabled');
            if (passwordInput) passwordInput.removeAttribute('disabled');
            if (toggleBtn) toggleBtn.removeAttribute('disabled');
            if (submitBtn) {
                submitBtn.removeAttribute('disabled');
                submitBtn.textContent = 'Sign In';
            }

            // Transform banner into unlock notification
            lockoutNotice.style.backgroundColor = '#f0fdf4';
            lockoutNotice.style.borderColor = '#bbf7d0';
            lockoutNotice.style.color = '#166534';
            lockoutNotice.innerHTML = '<i data-lucide="check-circle-2" style="width: 22px; height: 22px; color: #16a34a; flex-shrink: 0; margin-top: 2px;"></i><div><div style="font-weight: 600;">Lockout Expired</div><div>You may now enter your credentials to sign in.</div></div>';
            if (window.lucide) lucide.createIcons();
        } else {
            const mins = Math.floor(secondsLeft / 60);
            const secs = secondsLeft % 60;
            timerDisplay.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
    }, 1000);
}
</script>

</body>
</html>
