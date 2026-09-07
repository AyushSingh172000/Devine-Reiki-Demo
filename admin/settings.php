<?php
// Admin Site Settings Management
$pageTitle = "Site Settings";

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

$msg = '';
$error = '';

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $settingsToUpdate = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_phone' => trim($_POST['site_phone'] ?? ''),
        'site_email' => trim($_POST['site_email'] ?? ''),
        'site_address' => trim($_POST['site_address'] ?? ''),
        'site_whatsapp' => trim($_POST['site_whatsapp'] ?? ''),
        'facebook_url' => trim($_POST['facebook_url'] ?? ''),
        'youtube_url' => trim($_POST['youtube_url'] ?? ''),
        'maps_url' => trim($_POST['maps_url'] ?? ''),
        'working_hours' => trim($_POST['working_hours'] ?? '')
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settingsToUpdate as $key => $val) {
            $stmt->execute([$key, $val]);
        }
        $msg = "Site settings updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch Current Settings from DB
$dbSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dbSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
}

include __DIR__ . '/includes/admin-header.php';
?>

<?php if (!empty($msg)): ?>
    <div style="background-color: #dcfce7; color: #16a34a; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background-color: #fee2e2; color: #dc2626; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 600;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">General Website Settings</h2>
    </div>

    <form action="settings.php" method="POST">
        <div class="form-grid-2col">
            <div class="form-group">
                <label class="form-label">Website Name</label>
                <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($dbSettings['site_name'] ?? SITE_NAME); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="site_phone" class="form-input" value="<?php echo htmlspecialchars($dbSettings['site_phone'] ?? SITE_PHONE); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="site_email" class="form-input" value="<?php echo htmlspecialchars($dbSettings['site_email'] ?? SITE_EMAIL); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">WhatsApp Number / Link</label>
                <input type="text" name="site_whatsapp" class="form-input" value="<?php echo htmlspecialchars($dbSettings['site_whatsapp'] ?? SITE_WHATSAPP); ?>" required>
            </div>

            <div class="form-group full-width">
                <label class="form-label">Physical Center Address</label>
                <input type="text" name="site_address" class="form-input" value="<?php echo htmlspecialchars($dbSettings['site_address'] ?? SITE_ADDRESS); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Facebook Page URL</label>
                <input type="text" name="facebook_url" class="form-input" value="<?php echo htmlspecialchars($dbSettings['facebook_url'] ?? FACEBOOK_URL); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">YouTube Channel URL</label>
                <input type="text" name="youtube_url" class="form-input" value="<?php echo htmlspecialchars($dbSettings['youtube_url'] ?? YOUTUBE_URL); ?>">
            </div>

            <div class="form-group full-width">
                <label class="form-label">Google Maps Direction URL</label>
                <input type="text" name="maps_url" class="form-input" value="<?php echo htmlspecialchars($dbSettings['maps_url'] ?? MAPS_URL); ?>">
            </div>

            <div class="form-group full-width">
                <label class="form-label">Working Hours Text</label>
                <input type="text" name="working_hours" class="form-input" value="<?php echo htmlspecialchars($dbSettings['working_hours'] ?? WORKING_HOURS); ?>">
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 32px; font-size: 1rem;">Save Site Settings →</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
