<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Client Admin Accounts & Credential Governance';

$successMsg = '';
$errorMsg = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create New Client Admin
    if ($action === 'create_admin') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newPassword = $_POST['password'] ?? '';

        if (empty($newUsername) || empty($newEmail) || empty($newPassword)) {
            $errorMsg = "Please fill in all fields (username, email, password).";
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = "Password must be at least 6 characters long.";
        } else {
            try {
                // Check uniqueness
                $check = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
                $check->execute([$newUsername, $newEmail]);
                if ($check->fetch()) {
                    $errorMsg = "An administrator with that username or email already exists.";
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
                    $ins->execute([$newUsername, $newEmail, $hashed]);
                    $successMsg = "Client admin account '{$newUsername}' created successfully!";
                }
            } catch (\PDOException $e) {
                $errorMsg = "Database error: " . $e->getMessage();
            }
        }
    }

    // Reset Password
    if ($action === 'reset_password') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $resetPassword = $_POST['new_password'] ?? '';

        if ($adminId <= 0 || empty($resetPassword)) {
            $errorMsg = "Invalid request or empty password.";
        } elseif (strlen($resetPassword) < 6) {
            $errorMsg = "Password must be at least 6 characters long.";
        } else {
            try {
                $hashed = password_hash($resetPassword, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $upd->execute([$hashed, $adminId]);
                $successMsg = "Password for client admin ID #{$adminId} successfully reset!";
            } catch (\PDOException $e) {
                $errorMsg = "Database error: " . $e->getMessage();
            }
        }
    }

    // Delete Admin
    if ($action === 'delete_admin') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        if ($adminId > 0) {
            try {
                // Count total admins to ensure at least 1 remains
                $total = (int)$pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
                if ($total <= 1) {
                    $errorMsg = "Cannot delete the only existing client admin account. Create another first.";
                } else {
                    $del = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                    $del->execute([$adminId]);
                    $successMsg = "Client admin account deleted.";
                }
            } catch (\PDOException $e) {
                $errorMsg = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all client admins
$clientAdmins = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM admin_users ORDER BY id ASC");
    $clientAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $errorMsg = "Failed to load client admins: " . $e->getMessage();
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
    <!-- Table of Client Admins -->
    <div class="sa-card" style="margin-bottom: 0;">
        <div class="sa-card-header">
            <div>
                <h3 class="sa-card-title">
                    <i data-lucide="users" style="color: var(--sa-gold);"></i>
                    Existing Client Administrator Accounts
                </h3>
                <p class="sa-card-desc">Accounts capable of signing into the standard client panel (<code>/admin/</code>).</p>
            </div>
            <span class="sa-meta-tag" style="background: rgba(243, 201, 102, 0.15); color: var(--sa-gold); padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                <?= count($clientAdmins) ?> Total Accounts
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email Address</th>
                        <th>Created</th>
                        <th style="text-align: right;">Master Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientAdmins as $adm): ?>
                        <tr>
                            <td style="color: var(--sa-text-dim); font-weight: 700;">#<?= $adm['id'] ?></td>
                            <td>
                                <strong style="color: #fff;"><?= htmlspecialchars($adm['username']) ?></strong>
                            </td>
                            <td style="color: var(--sa-text-muted);"><?= htmlspecialchars($adm['email']) ?></td>
                            <td style="font-size: 0.8rem; color: var(--sa-text-dim);"><?= htmlspecialchars(substr($adm['created_at'], 0, 10)) ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <!-- Reset Password Modal Trigger -->
                                    <button type="button" class="sa-btn sa-btn-outline" style="padding: 5px 10px; font-size: 0.75rem;" 
                                            onclick="openResetModal(<?= $adm['id'] ?>, '<?= htmlspecialchars(addslashes($adm['username'])) ?>')">
                                        <i data-lucide="key" style="width: 13px; height: 13px;"></i> Reset Password
                                    </button>

                                    <!-- Delete Admin -->
                                    <form method="POST" onsubmit="return confirm('Permanently delete client admin account \'<?= htmlspecialchars(addslashes($adm['username'])) ?>\'?');" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_admin">
                                        <input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                                        <button type="submit" class="sa-btn sa-btn-danger" style="padding: 5px 10px; font-size: 0.75rem;">
                                            <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create New Client Admin Card -->
    <div class="sa-card" style="margin-bottom: 0;">
        <div class="sa-card-header">
            <div>
                <h3 class="sa-card-title">
                    <i data-lucide="user-plus" style="color: var(--sa-cyan);"></i>
                    Create Client Admin
                </h3>
                <p class="sa-card-desc">Grant standard access to a new team member.</p>
            </div>
        </div>

        <form method="POST" action="admin-users.php">
            <input type="hidden" name="action" value="create_admin">

            <div class="sa-form-group">
                <label class="sa-form-label">Username</label>
                <input type="text" name="username" class="sa-form-control" placeholder="e.g. manager" required>
            </div>

            <div class="sa-form-group">
                <label class="sa-form-label">Email Address</label>
                <input type="email" name="email" class="sa-form-control" placeholder="manager@reikibliss.local" required>
            </div>

            <div class="sa-form-group">
                <label class="sa-form-label">Temporary Password</label>
                <input type="password" name="password" class="sa-form-control" placeholder="Min 6 characters" required>
            </div>

            <button type="submit" class="sa-btn sa-btn-gold" style="width: 100%;">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Create Account
            </button>
        </form>
    </div>
</div>

<!-- Reset Password Dialog (Pure CSS/JS) -->
<div id="resetModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 100; align-items: center; justify-content: center;">
    <div class="sa-card" style="width: 100%; max-width: 420px; background: #0e111e; border-color: var(--sa-border-gold); padding: 30px;">
        <h3 class="sa-card-title" style="margin-bottom: 8px;">Reset Client Password</h3>
        <p class="sa-card-desc" style="margin-bottom: 20px;">
            Set a new password for account: <strong id="resetTargetUsername" style="color: var(--sa-gold);"></strong>
        </p>

        <form method="POST" action="admin-users.php">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="admin_id" id="resetAdminId" value="">

            <div class="sa-form-group">
                <label class="sa-form-label">New Password</label>
                <input type="password" name="new_password" class="sa-form-control" placeholder="Minimum 6 characters" required minlength="6">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px;">
                <button type="button" class="sa-btn sa-btn-outline" onclick="closeResetModal()">Cancel</button>
                <button type="submit" class="sa-btn sa-btn-gold">Confirm Password Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetModal(id, username) {
    document.getElementById('resetAdminId').value = id;
    document.getElementById('resetTargetUsername').textContent = username;
    const modal = document.getElementById('resetModal');
    modal.style.display = 'flex';
}
function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
