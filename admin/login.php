<?php
// Admin Login Page - Divine Reiki Center
require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: " . BASE_URL . "admin/index.php");
    exit;
}

$error = '';
$logoutMsg = isset($_GET['logout']) ? 'You have been logged out successfully.' : '';

// Process Login Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Password correct -> initialize admin session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'];

                header("Location: " . BASE_URL . "admin/index.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Database error in login.php: " . $e->getMessage());
            $error = 'An error occurred during authentication. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Divine Reiki Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #2D1B69;
            --accent-gold: #C9A84C;
            --light-cream: #FDF8F0;
            --dark-text: #1a1a1a;
            --muted-gray: #6b7280;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #160a2c 0%, #2D1B69 60%, #120726 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #ffffff;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 44px 36px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo-icon {
            font-size: 2.5rem;
            margin-bottom: 8px;
            display: inline-block;
        }
        .login-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }
        .login-subtitle {
            font-size: 0.9rem;
            color: var(--accent-gold);
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .form-group {
            margin-bottom: 22px;
        }
        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
        }
        .form-input {
            width: 100%;
            padding: 13px 18px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            outline: none;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: var(--accent-gold);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.25);
        }
        .alert-error {
            background: rgba(244, 67, 54, 0.18);
            border: 1px solid rgba(244, 67, 54, 0.4);
            color: #ff8a80;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.88rem;
            margin-bottom: 22px;
            text-align: center;
        }
        .alert-logout {
            background: rgba(76, 175, 80, 0.18);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #b9f6ca;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.88rem;
            margin-bottom: 22px;
            text-align: center;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #C9A84C 0%, #B59338 100%);
            color: #1a1a1a;
            border: none;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(201, 168, 76, 0.3);
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(201, 168, 76, 0.5);
            background: linear-gradient(135deg, #D4B357 0%, #C9A84C 100%);
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }
        .login-footer a {
            color: var(--accent-gold);
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <span class="login-logo-icon">✨</span>
        <h1 class="login-title">Divine Reiki</h1>
        <p class="login-subtitle">Admin Portal Login</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($logoutMsg) && empty($error)): ?>
        <div class="alert-logout"><?php echo htmlspecialchars($logoutMsg); ?></div>
    <?php endif; ?>

    <form action="" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-input" placeholder="Enter admin username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-input" placeholder="Enter admin password" required>
        </div>

        <button type="submit" class="btn-submit">Login to Dashboard →</button>
    </form>

    <div class="login-footer">
        <p>&copy; 2026 Divine Reiki &amp; Energy Healing Center</p>
        <p style="margin-top: 6px;"><a href="<?php echo BASE_URL; ?>index.php">← Back to Main Website</a></p>
    </div>
</div>

</body>
</html>
