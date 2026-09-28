<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Client Admin Sidebar Customizer';

$successMsg = '';
$errorMsg = '';

// Default labels exactly matching the user's reference screenshot
$defaultSidebarLabels = [
    'brand_title'           => 'ADMIN PANEL',
    'menu_dashboard'        => 'Dashboard',
    'section_content'       => 'CONTENT',
    'menu_homepage_hero'    => 'Homepage Hero',
    'menu_about_us'         => 'About Us Page',
    'menu_testimonials'     => 'Testimonials',
    'section_commerce'      => 'COMMERCE',
    'menu_products'         => 'Products',
    'menu_services'         => 'Services',
    'menu_courses'          => 'Courses',
    'menu_orders'           => 'Orders',
    'section_communication' => 'COMMUNICATION',
    'menu_inquiries'        => 'Inquiries',
    'menu_whatsapp'         => 'WhatsApp Templates',
    'section_settings'      => 'SETTINGS',
    'menu_settings'         => 'Site Settings',
    'menu_footer'           => 'Footer & Legal',
    'menu_logout'           => 'Logout',
];

// Handle Reset Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_sidebar_names') {
    try {
        $stmt = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = ?");
        $stmt->execute(['superadmin_sidebar_names']);
        unset($siteSettings['superadmin_sidebar_names']);
        $successMsg = "Sidebar names have been successfully reset to the original default names shown in the reference screenshot!";
    } catch (\PDOException $e) {
        $errorMsg = "Database error resetting sidebar names: " . $e->getMessage();
    }
}

// Handle Save Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_sidebar_names') {
    $rawLabels = $_POST['sidebar_names'] ?? [];
    $sanitized = [];

    foreach ($defaultSidebarLabels as $key => $defaultVal) {
        if (isset($rawLabels[$key]) && trim($rawLabels[$key]) !== '') {
            $sanitized[$key] = trim(strip_tags($rawLabels[$key]));
        } else {
            $sanitized[$key] = $defaultVal;
        }
    }

    try {
        $jsonVal = json_encode($sanitized);
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_sidebar_names', $jsonVal]);
        $siteSettings['superadmin_sidebar_names'] = $jsonVal;
        $successMsg = "Admin sidebar navigation names updated successfully! Changes are active immediately in the Client Admin panel.";
    } catch (\PDOException $e) {
        $errorMsg = "Database error saving sidebar names: " . $e->getMessage();
    }
}

// Load current active labels
$customSidebarLabels = !empty($siteSettings['superadmin_sidebar_names']) ? json_decode($siteSettings['superadmin_sidebar_names'], true) : [];
$activeLabels = array_merge($defaultSidebarLabels, is_array($customSidebarLabels) ? $customSidebarLabels : []);

include __DIR__ . '/includes/superadmin-header.php';
?>

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #ffffff; margin: 0 0 6px;">
            <i data-lucide="edit-3" style="color: var(--sa-gold); vertical-align: middle; margin-right: 8px;"></i>
            Client Admin Sidebar Names
        </h1>
        <p style="font-size: 0.85rem; color: var(--sa-text-muted); margin: 0;">
            Rename any sidebar link or section divider in the Client Admin panel. Default values are locked to your original screenshot.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <form method="POST" action="sidebar-manager.php" onsubmit="return confirm('Reset all sidebar names back to the original screenshot defaults?');">
            <input type="hidden" name="action" value="reset_sidebar_names">
            <button type="submit" class="sa-btn sa-btn-outline" style="color: #f87171; border-color: rgba(239, 68, 68, 0.4);">
                <i data-lucide="rotate-ccw" style="width: 15px; height: 15px;"></i> Reset to Defaults
            </button>
        </form>
        <a href="../admin/" target="_blank" class="sa-btn sa-btn-outline">
            <i data-lucide="external-link" style="width: 15px; height: 15px;"></i> View Client Admin
        </a>
    </div>
</div>

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

<form method="POST" action="sidebar-manager.php">
    <input type="hidden" name="action" value="save_sidebar_names">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">

        <!-- BRAND & DASHBOARD -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div class="sa-card-header" style="border-bottom: 1px solid var(--sa-border); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(243, 201, 102, 0.15); display: flex; align-items: center; justify-content: center; color: var(--sa-gold);">
                        <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                    </span>
                    <div>
                        <h3 class="sa-card-title" style="font-size: 1rem;">Brand &amp; Top Level</h3>
                        <p class="sa-card-desc" style="font-size: 0.75rem;">Logo subtitle &amp; primary landing page</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Brand Sub-label (Under Logo)</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['brand_title']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[brand_title]" value="<?= htmlspecialchars($activeLabels['brand_title']) ?>" class="sa-input" placeholder="ADMIN PANEL" required>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Dashboard Item Name</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_dashboard']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_dashboard]" value="<?= htmlspecialchars($activeLabels['menu_dashboard']) ?>" class="sa-input" placeholder="Dashboard" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>admin/index.php</code></small>
                </div>
            </div>
        </div>

        <!-- CONTENT SECTION -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div class="sa-card-header" style="border-bottom: 1px solid var(--sa-border); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(56, 189, 248, 0.15); display: flex; align-items: center; justify-content: center; color: var(--sa-cyan);">
                        <i data-lucide="layers" style="width: 18px; height: 18px;"></i>
                    </span>
                    <div>
                        <h3 class="sa-card-title" style="font-size: 1rem;">Content Group</h3>
                        <p class="sa-card-desc" style="font-size: 0.75rem;">Hero, About Us &amp; Reviews</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Section Divider Title</span>
                        <span style="font-size: 0.72rem; color: var(--sa-cyan);">Original: <?= htmlspecialchars($defaultSidebarLabels['section_content']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[section_content]" value="<?= htmlspecialchars($activeLabels['section_content']) ?>" class="sa-input" placeholder="CONTENT" required>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Homepage Hero Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_homepage_hero']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_homepage_hero]" value="<?= htmlspecialchars($activeLabels['menu_homepage_hero']) ?>" class="sa-input" placeholder="Homepage Hero" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>settings?tab=homepage</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>About Us Page Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_about_us']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_about_us]" value="<?= htmlspecialchars($activeLabels['menu_about_us']) ?>" class="sa-input" placeholder="About Us Page" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>settings?tab=about</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Testimonials Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_testimonials']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_testimonials]" value="<?= htmlspecialchars($activeLabels['menu_testimonials']) ?>" class="sa-input" placeholder="Testimonials" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>testimonials.php</code></small>
                </div>
            </div>
        </div>

        <!-- COMMERCE SECTION -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div class="sa-card-header" style="border-bottom: 1px solid var(--sa-border); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(34, 197, 94, 0.15); display: flex; align-items: center; justify-content: center; color: var(--sa-green);">
                        <i data-lucide="store" style="width: 18px; height: 18px;"></i>
                    </span>
                    <div>
                        <h3 class="sa-card-title" style="font-size: 1rem;">Commerce Group</h3>
                        <p class="sa-card-desc" style="font-size: 0.75rem;">Products, Services, Courses &amp; Orders</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Section Divider Title</span>
                        <span style="font-size: 0.72rem; color: var(--sa-green);">Original: <?= htmlspecialchars($defaultSidebarLabels['section_commerce']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[section_commerce]" value="<?= htmlspecialchars($activeLabels['section_commerce']) ?>" class="sa-input" placeholder="COMMERCE" required>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Products Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_products']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_products]" value="<?= htmlspecialchars($activeLabels['menu_products']) ?>" class="sa-input" placeholder="Products" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>products.php</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Services Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_services']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_services]" value="<?= htmlspecialchars($activeLabels['menu_services']) ?>" class="sa-input" placeholder="Services" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>services.php</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Courses Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_courses']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_courses]" value="<?= htmlspecialchars($activeLabels['menu_courses']) ?>" class="sa-input" placeholder="Courses" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>courses.php</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Orders Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_orders']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_orders]" value="<?= htmlspecialchars($activeLabels['menu_orders']) ?>" class="sa-input" placeholder="Orders" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>orders.php</code></small>
                </div>
            </div>
        </div>

        <!-- COMMUNICATION SECTION -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div class="sa-card-header" style="border-bottom: 1px solid var(--sa-border); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(168, 85, 247, 0.15); display: flex; align-items: center; justify-content: center; color: var(--sa-purple);">
                        <i data-lucide="radio" style="width: 18px; height: 18px;"></i>
                    </span>
                    <div>
                        <h3 class="sa-card-title" style="font-size: 1rem;">Communication Group</h3>
                        <p class="sa-card-desc" style="font-size: 0.75rem;">Messages &amp; WhatsApp</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Section Divider Title</span>
                        <span style="font-size: 0.72rem; color: var(--sa-purple);">Original: <?= htmlspecialchars($defaultSidebarLabels['section_communication']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[section_communication]" value="<?= htmlspecialchars($activeLabels['section_communication']) ?>" class="sa-input" placeholder="COMMUNICATION" required>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Inquiries Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_inquiries']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_inquiries]" value="<?= htmlspecialchars($activeLabels['menu_inquiries']) ?>" class="sa-input" placeholder="Inquiries" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>inquiries.php</code> (badge preserved)</small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>WhatsApp Templates Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_whatsapp']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_whatsapp]" value="<?= htmlspecialchars($activeLabels['menu_whatsapp']) ?>" class="sa-input" placeholder="WhatsApp Templates" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>settings?tab=whatsapp</code></small>
                </div>
            </div>
        </div>

        <!-- SETTINGS SECTION -->
        <div class="sa-card" style="margin-bottom: 0;">
            <div class="sa-card-header" style="border-bottom: 1px solid var(--sa-border); padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(234, 179, 8, 0.15); display: flex; align-items: center; justify-content: center; color: var(--sa-gold);">
                        <i data-lucide="sliders-horizontal" style="width: 18px; height: 18px;"></i>
                    </span>
                    <div>
                        <h3 class="sa-card-title" style="font-size: 1rem;">Settings Group</h3>
                        <p class="sa-card-desc" style="font-size: 0.75rem;">Site Settings, Legal &amp; Session</p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Section Divider Title</span>
                        <span style="font-size: 0.72rem; color: var(--sa-gold);">Original: <?= htmlspecialchars($defaultSidebarLabels['section_settings']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[section_settings]" value="<?= htmlspecialchars($activeLabels['section_settings']) ?>" class="sa-input" placeholder="SETTINGS" required>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Site Settings Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_settings']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_settings]" value="<?= htmlspecialchars($activeLabels['menu_settings']) ?>" class="sa-input" placeholder="Site Settings" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>settings.php</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Footer &amp; Legal Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_footer']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_footer]" value="<?= htmlspecialchars($activeLabels['menu_footer']) ?>" class="sa-input" placeholder="Footer & Legal" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>settings?tab=footer</code></small>
                </div>

                <div>
                    <label style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; color: #e2e8f0; margin-bottom: 6px;">
                        <span>Logout Item</span>
                        <span style="font-size: 0.72rem; color: var(--sa-text-dim);">Original: <?= htmlspecialchars($defaultSidebarLabels['menu_logout']) ?></span>
                    </label>
                    <input type="text" name="sidebar_names[menu_logout]" value="<?= htmlspecialchars($activeLabels['menu_logout']) ?>" class="sa-input" placeholder="Logout" required>
                    <small style="color: var(--sa-text-dim); font-size: 0.72rem; margin-top: 4px; display: block;">Points to: <code>logout.php</code></small>
                </div>
            </div>
        </div>

    </div>

    <!-- Sticky Save Action Bar -->
    <div class="sa-sticky-actions">
        <div>
            <span style="font-size: 0.88rem; font-weight: 600; color: #ffffff;">Save Client Admin Sidebar Titles</span>
            <p style="font-size: 0.75rem; color: var(--sa-text-muted); margin: 0;">Routes, URLs, icons, and permissions are preserved intact; only the display labels will be updated.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="sa-btn sa-btn-gold">
                <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Save Sidebar Names
            </button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
