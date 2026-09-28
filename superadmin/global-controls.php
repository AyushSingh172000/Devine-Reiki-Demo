<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Global Site Architecture & Overrides';

$successMsg = '';
$errorMsg = '';

// Default toggles
$defaultToggles = [
    'maintenance_mode' => false,
    'maintenance_msg' => 'We are currently performing scheduled spiritual enhancements. Please return shortly or message us on WhatsApp.',
    'hide_prices' => false,
    'disable_bookings' => false,
    'announcement_enabled' => false,
    'announcement_text' => '✦ Welcome to Reiki Bliss: Experience Divine Peace & Authentic Energy Alignments'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_global_controls') {
    $maintenance = !empty($_POST['maintenance_mode']);
    $maintenanceMsg = trim($_POST['maintenance_msg'] ?? $defaultToggles['maintenance_msg']);
    $hidePrices = !empty($_POST['hide_prices']);
    $disableBookings = !empty($_POST['disable_bookings']);
    $announcementEnabled = !empty($_POST['announcement_enabled']);
    $announcementText = trim($_POST['announcement_text'] ?? '');

    $togglesToSave = [
        'maintenance_mode' => $maintenance,
        'maintenance_msg' => $maintenanceMsg,
        'hide_prices' => $hidePrices,
        'disable_bookings' => $disableBookings,
        'announcement_enabled' => $announcementEnabled,
        'announcement_text' => $announcementText
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_site_toggles', json_encode($togglesToSave)]);
        $siteSettings['superadmin_site_toggles'] = json_encode($togglesToSave);
        $successMsg = "Global website controls and switches saved successfully!";
    } catch (\PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

// Load current site toggles
$currentToggles = $defaultToggles;
if (!empty($siteSettings['superadmin_site_toggles'])) {
    $decoded = json_decode($siteSettings['superadmin_site_toggles'], true);
    if (is_array($decoded)) {
        $currentToggles = array_merge($defaultToggles, $decoded);
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

<form method="POST" action="global-controls.php">
    <input type="hidden" name="action" value="save_global_controls">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <!-- Control 1: Maintenance Mode -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <h3 class="sa-card-title">
                        <i data-lucide="shield-alert" style="color: var(--sa-red);"></i>
                        Maintenance Mode
                    </h3>
                    <p class="sa-card-desc">When enabled, visitors will see an ambient maintenance screen. Logged-in superadmins can bypass.</p>
                </div>
                <label class="sa-switch">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($currentToggles['maintenance_mode']) ? 'checked' : '' ?>>
                    <span class="sa-slider"></span>
                </label>
            </div>

            <div class="sa-form-group mb-0">
                <label class="sa-form-label">Custom Maintenance Notice</label>
                <textarea name="maintenance_msg" class="sa-form-control" rows="3"><?= htmlspecialchars($currentToggles['maintenance_msg']) ?></textarea>
            </div>
        </div>

        <!-- Control 2: Price Visibility & Bookings -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--sa-border);">
                <div>
                    <h3 class="sa-card-title">
                        <i data-lucide="eye-off" style="color: var(--sa-cyan);"></i>
                        Hide Prices Globally
                    </h3>
                    <p class="sa-card-desc">Converts all product and service price tags into 'Contact for Pricing'.</p>
                </div>
                <label class="sa-switch">
                    <input type="checkbox" name="hide_prices" value="1" <?= !empty($currentToggles['hide_prices']) ? 'checked' : '' ?>>
                    <span class="sa-slider"></span>
                </label>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 class="sa-card-title">
                        <i data-lucide="calendar-x" style="color: var(--sa-gold);"></i>
                        Disable Direct Bookings
                    </h3>
                    <p class="sa-card-desc">Routes 'Book Free Session' and appointment buttons to the inquiry form or WhatsApp instead of calendar.</p>
                </div>
                <label class="sa-switch">
                    <input type="checkbox" name="disable_bookings" value="1" <?= !empty($currentToggles['disable_bookings']) ? 'checked' : '' ?>>
                    <span class="sa-slider"></span>
                </label>
            </div>
        </div>

        <!-- Control 3: Top Announcement Banner -->
        <div class="sa-card" style="grid-column: 1 / -1; margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <h3 class="sa-card-title">
                        <i data-lucide="megaphone" style="color: var(--sa-purple);"></i>
                        Global Top Announcement Banner
                    </h3>
                    <p class="sa-card-desc">Displays an elegant floating or top ribbon message across every page of the public website.</p>
                </div>
                <label class="sa-switch">
                    <input type="checkbox" name="announcement_enabled" value="1" <?= !empty($currentToggles['announcement_enabled']) ? 'checked' : '' ?>>
                    <span class="sa-slider"></span>
                </label>
            </div>

            <div class="sa-form-group mb-0">
                <label class="sa-form-label">Announcement Banner Text / HTML</label>
                <input type="text" name="announcement_text" class="sa-form-control" 
                       value="<?= htmlspecialchars($currentToggles['announcement_text']) ?>" 
                       placeholder="e.g. ✦ Diwali Special: 20% Off on All Reiki Crystal Bracelets">
            </div>
        </div>
    </div>

    <!-- Sticky Save Action Bar -->
    <div class="sa-sticky-actions">
        <div>
            <span style="font-size: 0.88rem; font-weight: 600; color: #ffffff;">Save Global Site Controls</span>
            <p style="font-size: 0.75rem; color: var(--sa-text-muted); margin: 0;">Instantly toggles maintenance mode, pricing displays, and booking pathways.</p>
        </div>
        <button type="submit" class="sa-btn sa-btn-gold">
            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Save Global Switches
        </button>
    </div>
</form>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
