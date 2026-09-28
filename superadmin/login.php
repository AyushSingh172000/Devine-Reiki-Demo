<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// If already logged in as superadmin, redirect to dashboard
if (isset($_SESSION['superadmin_logged_in']) && $_SESSION['superadmin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$usernameOrEmail = '';

// Generate CSRF token
if (empty($_SESSION['superadmin_csrf_token'])) {
    $_SESSION['superadmin_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['superadmin_csrf_token'], $submittedToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            $error = 'Please enter both username/email and master password.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM superadmin_users WHERE username = ? OR email = ? LIMIT 1");
                $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    // Prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['superadmin_logged_in'] = true;
                    $_SESSION['superadmin_id'] = $user['id'];
                    $_SESSION['superadmin_username'] = $user['username'];
                    $_SESSION['superadmin_full_name'] = $user['full_name'] ?? 'Super Administrator';

                    // Re-generate CSRF token
                    $_SESSION['superadmin_csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid Super Admin credentials. Access denied.';
                }
            } catch (\PDOException $e) {
                $error = 'Database error during authentication: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Portal | Developer &amp; Master Control</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/superadmin.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body.login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #15162c 0%, #080913 70%, #030408 100%);
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .login-bg-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(243, 201, 102, 0.08) 0%, rgba(138, 92, 246, 0.05) 50%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }

        .super-login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: rgba(18, 20, 32, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(243, 201, 102, 0.25);
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 40px rgba(243, 201, 102, 0.08);
        }

        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(243, 201, 102, 0.12);
            border: 1px solid rgba(243, 201, 102, 0.35);
            color: #f3c966;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 16px;
        }

        .portal-title {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 6px 0;
            letter-spacing: 0.03em;
        }

        .portal-subtitle {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
            width: 18px;
            height: 18px;
        }

        .form-input {
            width: 100%;
            box-sizing: border-box;
            background: rgba(11, 13, 23, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 12px 14px 12px 42px;
            font-size: 0.92rem;
            color: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: #f3c966;
            background: rgba(15, 17, 28, 0.9);
            box-shadow: 0 0 0 3px rgba(243, 201, 102, 0.15);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #f3c966 0%, #d4a737 100%);
            color: #0d0f19;
            font-weight: 700;
            font-size: 0.92rem;
            border: none;
            border-radius: 10px;
            padding: 13px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(243, 201, 102, 0.25);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(243, 201, 102, 0.35);
        }

        .login-footer-links {
            margin-top: 24px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 16px;
            font-size: 0.8rem;
            color: #64748b;
        }

        .login-footer-links a {
            color: #f3c966;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-bg-glow"></div>

    <div class="super-login-card">
        <div style="text-align: center;">
            <div class="portal-badge">
                <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                Super Admin Studio
            </div>
            <h1 class="portal-title">Master Control</h1>
            <p class="portal-subtitle">Exclusive developer workspace for layout architecture, client permission rules, and core website governance.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['superadmin_csrf_token'] ?? '') ?>">

            <div class="form-group">
                <label class="form-label" for="username">Master Username or Email</label>
                <div class="input-wrap">
                    <i data-lucide="user"></i>
                    <input type="text" id="username" name="username" class="form-input" 
                           placeholder="superadmin" value="<?= htmlspecialchars($usernameOrEmail) ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Master Password</label>
                <div class="input-wrap">
                    <i data-lucide="lock"></i>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="••••••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i data-lucide="key-round" style="width: 17px; height: 17px;"></i>
                Authenticate as Super Admin
            </button>
        </form>

        <div class="login-footer-links">
            <a href="../index.php">← Return to Website</a> &nbsp;·&nbsp;
            <a href="../admin/login.php">Client Admin Portal</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
