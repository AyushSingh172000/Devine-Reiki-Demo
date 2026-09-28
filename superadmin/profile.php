<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Super Admin Profile & Security';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update details
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if (empty($fullName) || empty($email) || empty($username)) {
            $errorMsg = "All profile fields are required.";
        } else {
            try {
                // Check if username/email taken by other superadmin
                $check = $pdo->prepare("SELECT id FROM superadmin_users WHERE (username = ? OR email = ?) AND id != ?");
                $check->execute([$username, $email, $superAdminUser['id']]);
                if ($check->fetch()) {
                    $errorMsg = "That username or email address is already in use by another master account.";
                } else {
                    $upd = $pdo->prepare("UPDATE superadmin_users SET full_name = ?, email = ?, username = ? WHERE id = ?");
                    $upd->execute([$fullName, $email, $username, $superAdminUser['id']]);
                    $_SESSION['superadmin_username'] = $username;
                    $_SESSION['superadmin_full_name'] = $fullName;
                    $superAdminUser['full_name'] = $fullName;
                    $superAdminUser['email'] = $email;
                    $superAdminUser['username'] = $username;
                    $successMsg = "Profile information updated successfully!";
                }
            } catch (\PDOException $e) {
                $errorMsg = "Database error: " . $e->getMessage();
            }
        }
    }

    // Change Password
    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $errorMsg = "Please fill in all password fields.";
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = "New password and confirmation do not match.";
        } elseif (strlen($newPass) < 6) {
            $errorMsg = "New password must be at least 6 characters long.";
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM superadmin_users WHERE id = ?");
                $stmt->execute([$superAdminUser['id']]);
                $hash = $stmt->fetchColumn();

                if (!password_verify($currentPass, $hash)) {
                    $errorMsg = "Current master password entered is incorrect.";
                } else {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE superadmin_users SET password = ? WHERE id = ?");
                    $upd->execute([$newHash, $superAdminUser['id']]);
                    $successMsg = "Master password changed successfully!";
                }
            } catch (\PDOException $e) {
                $errorMsg = "Database error: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/includes/superadmin-header.php';
?>

<?php if (!empty($successMsg)): ?>
    <div class="sa-alert sa-alert-success">
        <i data-lucide="check-circle-2" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div><?= htmlspecialchars($successMsg) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="sa-alert sa-alert-error">
        <i data-lucide="alert-octagon" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div><?= htmlspecialchars($errorMsg) ?></div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Profile Info Form -->
    <div class="sa-card" style="margin-bottom: 0;">
        <div class="sa-card-header">
            <div>
                <h3 class="sa-card-title">
                    <i data-lucide="user" style="color: var(--sa-gold);"></i>
                    Master Profile Details
                </h3>
                <p class="sa-card-desc">Super Administrator account identity and notification email.</p>
            </div>
        </div>

        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="update_profile">

            <div class="sa-form-group">
                <label class="sa-form-label">Full Name</label>
                <input type="text" name="full_name" class="sa-form-control" value="<?= htmlspecialchars($superAdminUser['full_name'] ?? '') ?>" required>
            </div>

            <div class="sa-form-group">
                <label class="sa-form-label">Master Username</label>
                <input type="text" name="username" class="sa-form-control" value="<?= htmlspecialchars($superAdminUser['username'] ?? '') ?>" required>
            </div>

            <div class="sa-form-group">
                <label class="sa-form-label">Master Email Address</label>
                <input type="email" name="email" class="sa-form-control" value="<?= htmlspecialchars($superAdminUser['email'] ?? '') ?>" required>
            </div>

            <button type="submit" class="sa-btn sa-btn-gold" style="width: 100%;">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i> Save Profile Changes
            </button>
        </form>
    </div>

    <!-- Password Change Form -->
    <div class="sa-card" style="margin-bottom: 0;">
        <div class="sa-card-header">
            <div>
                <h3 class="sa-card-title">
                    <i data-lucide="shield-lock" style="color: var(--sa-cyan);"></i>
                    Update Master Password
                </h3>
                <p class="sa-card-desc">Strengthen your super admin security credentials.</p>
            </div>
        </div>

        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="change_password">

            <div class="sa-form-group">
                <label class="sa-form-label">Current Master Password</label>
                <input type="password" name="current_password" class="sa-form-control" placeholder="••••••••••••" required>
            </div>

            <div class="sa-form-group">
                <label class="sa-form-label">New Master Password</label>
                <input type="password" name="new_password" class="sa-form-control" placeholder="Min 6 characters" required minlength="6">
            </div>

            <div class="sa-form-group">
                <label class="sa-form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="sa-form-control" placeholder="Repeat new password" required minlength="6">
            </div>

            <button type="submit" class="sa-btn sa-btn-outline" style="width: 100%; border-color: rgba(56, 189, 248, 0.4); color: var(--sa-cyan);">
                <i data-lucide="lock" style="width: 16px; height: 16px;"></i> Update Master Password
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
