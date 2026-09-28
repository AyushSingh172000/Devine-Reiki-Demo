<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Client Admin Access & Permission Matrix';

$successMsg = '';
$errorMsg = '';

// Default permissions
$defaultPerms = [
    'show_hero_ctas' => false,
    'allowed_menus' => [
        'services',
        'courses',
        'products',
        'testimonials',
        'gallery',
        'inquiries',
        'whatsapp',
        'settings',
        'footer'
    ],
    'allowed_settings_tabs' => [
        'general',
        'branding',
        'contact',
        'homepage',
        'footer',
        'whatsapp',
        'password'
    ]
];

// Load dynamic sidebar labels if set
$customSidebarLabels = !empty($siteSettings['superadmin_sidebar_names']) ? json_decode($siteSettings['superadmin_sidebar_names'], true) : [];
$getSidebarName = function($key, $default) use ($customSidebarLabels) {
    return (!empty($customSidebarLabels[$key])) ? $customSidebarLabels[$key] : $default;
};

// Available Client Admin Menus (Matches Reference Screenshot Defaults & Dynamic Names)
$availableMenus = [
    'services'     => ['label' => $getSidebarName('menu_services', 'Services'), 'icon' => 'clipboard-list', 'file' => 'services.php'],
    'courses'      => ['label' => $getSidebarName('menu_courses', 'Courses'), 'icon' => 'graduation-cap', 'file' => 'courses.php'],
    'products'     => ['label' => $getSidebarName('menu_products', 'Products'), 'icon' => 'shopping-bag', 'file' => 'products.php'],
    'testimonials' => ['label' => $getSidebarName('menu_testimonials', 'Testimonials'), 'icon' => 'message-circle', 'file' => 'testimonials.php'],
    'inquiries'    => ['label' => $getSidebarName('menu_inquiries', 'Inquiries'), 'icon' => 'mail', 'file' => 'inquiries.php'],
    'whatsapp'     => ['label' => $getSidebarName('menu_whatsapp', 'WhatsApp Templates'), 'icon' => 'message-square', 'file' => 'settings.php?tab=whatsapp'],
    'settings'     => ['label' => $getSidebarName('menu_settings', 'Site Settings'), 'icon' => 'settings', 'file' => 'settings.php'],
    'footer'       => ['label' => $getSidebarName('menu_footer', 'Footer & Legal'), 'icon' => 'panel-bottom', 'file' => 'settings.php?tab=footer'],
];

// Available Settings Tabs
$availableTabs = [
    'general' => ['label' => 'General & Location', 'icon' => 'building-2'],
    'branding' => ['label' => 'Branding & Assets', 'icon' => 'image'],
    'contact' => ['label' => 'Contact & Socials', 'icon' => 'phone-call'],
    'homepage' => ['label' => 'Homepage Banner & Stats', 'icon' => 'home'],
    'footer' => ['label' => 'Footer & Legal Pages', 'icon' => 'panel-bottom'],
    'whatsapp' => ['label' => 'WhatsApp Chat Settings', 'icon' => 'message-circle'],
    'password' => ['label' => 'Admin Credentials', 'icon' => 'key-round'],
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_permissions') {
    $showHeroCtas = !empty($_POST['show_hero_ctas']);
    $allowedMenus = $_POST['allowed_menus'] ?? [];
    $allowedTabs = $_POST['allowed_settings_tabs'] ?? [];

    $permsToSave = [
        'show_hero_ctas' => $showHeroCtas,
        'allowed_menus' => is_array($allowedMenus) ? array_values($allowedMenus) : [],
        'allowed_settings_tabs' => is_array($allowedTabs) ? array_values($allowedTabs) : []
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['superadmin_admin_permissions', json_encode($permsToSave)]);
        $siteSettings['superadmin_admin_permissions'] = json_encode($permsToSave);
        $successMsg = "Client admin rights and Hero CTA visibility updated successfully! Active immediately in the client admin panel.";
    } catch (\PDOException $e) {
        $errorMsg = "Database error saving permissions: " . $e->getMessage();
    }
}

// Load Current Permissions
$currentPerms = $defaultPerms;
if (!empty($siteSettings['superadmin_admin_permissions'])) {
    $decoded = json_decode($siteSettings['superadmin_admin_permissions'], true);
    if (is_array($decoded)) {
        $currentPerms = array_merge($defaultPerms, $decoded);
    }
}

$heroCtasActive = !empty($currentPerms['show_hero_ctas']);
$activeMenus = $currentPerms['allowed_menus'] ?? [];
$activeTabs = $currentPerms['allowed_settings_tabs'] ?? [];

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

<form method="POST" action="client-permissions.php">
    <input type="hidden" name="action" value="save_permissions">

    <!-- =========================================================================
         HERO CALL-TO-ACTION (CTA) BUTTONS TOGGLE
         ========================================================================= -->
    <div class="sa-card" style="border-color: rgba(243, 201, 102, 0.4); background: radial-gradient(circle at top right, rgba(243, 201, 102, 0.05), rgba(18, 22, 38, 0.95));">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 280px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="background: var(--sa-gold); color: #090b14; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px;">FEATURE TOGGLE</span>
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: #ffffff; margin: 0;">
                        Hero Call-to-Action (CTA) Buttons in Client Admin Settings
                    </h2>
                </div>
                <p style="font-size: 0.85rem; color: var(--sa-text-muted); line-height: 1.6; margin-bottom: 12px;">
                    Controls whether <strong>Section 2: Call-to-Action (CTA) Buttons</strong> is visible to the client admin inside <a href="../admin/settings.php?tab=homepage" target="_blank" style="color: var(--sa-gold); text-decoration: underline;">admin/settings.php</a>.
                </p>
                <div style="font-size: 0.82rem; color: #cbd5e1; background: rgba(0, 0, 0, 0.25); padding: 10px 14px; border-radius: 8px; border-left: 3px solid <?= $heroCtasActive ? 'var(--sa-green)' : 'var(--sa-red)' ?>;">
                    <?php if ($heroCtasActive): ?>
                        <strong style="color: var(--sa-green);">Currently ENABLED:</strong> Client admin can view and edit the Primary Button (Book Free Session) and Secondary Button (Explore Courses) text and URL fields.
                    <?php else: ?>
                        <strong style="color: var(--sa-red);">Currently DISABLED:</strong> Section 2 is completely hidden from the client admin in Settings, preserving your previous commented-out behavior automatically without manual code editing.
                    <?php endif; ?>
                </div>
            </div>

            <div style="text-align: center; padding: 14px 20px; background: rgba(14, 18, 32, 0.85); border: 1px solid var(--sa-border); border-radius: 12px;">
                <span style="font-size: 0.72rem; color: var(--sa-text-dim); text-transform: uppercase; font-weight: 700; letter-spacing: 0.08em; display: block; margin-bottom: 8px;">
                    Client Visibility
                </span>
                <label class="sa-switch" style="width: 58px; height: 32px;">
                    <input type="checkbox" name="show_hero_ctas" value="1" <?= $heroCtasActive ? 'checked' : '' ?>>
                    <span class="sa-slider" style="border-radius: 34px;"></span>
                </label>
                <span style="display: block; font-size: 0.75rem; font-weight: 700; color: <?= $heroCtasActive ? 'var(--sa-green)' : 'var(--sa-text-dim)' ?>; margin-top: 6px;">
                    <?= $heroCtasActive ? 'VISIBLE TO CLIENT' : 'HIDDEN FROM CLIENT' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         CLIENT ADMIN SIDEBAR MENUS
         ========================================================================= -->
    <div class="sa-card">
        <div class="sa-card-header">
            <div>
                <h3 class="sa-card-title">
                    <i data-lucide="layout-list" style="color: var(--sa-cyan);"></i>
                    Client Admin Sidebar Menu Permissions
                </h3>
                <p class="sa-card-desc">
                    Toggle which navigation links appear in the client admin panel sidebar. Unchecking an item removes it from the client's view.
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="sidebar-manager.php" class="sa-btn sa-btn-gold" style="font-size: 0.75rem; padding: 6px 12px;">
                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i> Edit Sidebar Names
                </a>
                <button type="button" class="sa-btn sa-btn-outline" style="font-size: 0.75rem; padding: 6px 12px;" onclick="toggleAll('allowed_menus', true)">Select All</button>
                <button type="button" class="sa-btn sa-btn-outline" style="font-size: 0.75rem; padding: 6px 12px;" onclick="toggleAll('allowed_menus', false)">Deselect All</button>
            </div>
        </div>

        <div class="sa-perm-grid">
            <?php foreach ($availableMenus as $menuKey => $m): 
                $isAllowed = in_array($menuKey, $activeMenus);
            ?>
                <label class="sa-perm-item" style="cursor: pointer;">
                    <div class="sa-perm-label">
                        <i data-lucide="<?= htmlspecialchars($m['icon']) ?>" style="width: 17px; height: 17px; color: var(--sa-gold);"></i>
                        <div>
                            <div><?= htmlspecialchars($m['label']) ?></div>
                            <span style="font-size: 0.72rem; color: var(--sa-text-dim); font-weight: 400;"><?= htmlspecialchars($m['file']) ?></span>
                        </div>
                    </div>
                    <label class="sa-switch">
                        <input type="checkbox" name="allowed_menus[]" value="<?= htmlspecialchars($menuKey) ?>" class="allowed_menus" <?= $isAllowed ? 'checked' : '' ?>>
                        <span class="sa-slider"></span>
                    </label>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- =========================================================================
         CLIENT ADMIN SETTINGS TABS
         ========================================================================= -->
    <div class="sa-card">
        <div class="sa-card-header">
            <div>
                <h3 class="sa-card-title">
                    <i data-lucide="sliders" style="color: var(--sa-purple);"></i>
                    Client Site Settings Tabs Permissions
                </h3>
                <p class="sa-card-desc">
                    Control which configuration tabs inside <code>admin/settings.php</code> can be viewed and edited by the client admin.
                </p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="sa-btn sa-btn-outline" style="font-size: 0.75rem; padding: 6px 12px;" onclick="toggleAll('allowed_settings_tabs', true)">Select All</button>
                <button type="button" class="sa-btn sa-btn-outline" style="font-size: 0.75rem; padding: 6px 12px;" onclick="toggleAll('allowed_settings_tabs', false)">Deselect All</button>
            </div>
        </div>

        <div class="sa-perm-grid">
            <?php foreach ($availableTabs as $tabKey => $t): 
                $isAllowed = in_array($tabKey, $activeTabs);
            ?>
                <label class="sa-perm-item" style="cursor: pointer;">
                    <div class="sa-perm-label">
                        <i data-lucide="<?= htmlspecialchars($t['icon']) ?>" style="width: 17px; height: 17px; color: var(--sa-cyan);"></i>
                        <div>
                            <div><?= htmlspecialchars($t['label']) ?></div>
                            <span style="font-size: 0.72rem; color: var(--sa-text-dim); font-weight: 400;">tab=<?= htmlspecialchars($tabKey) ?></span>
                        </div>
                    </div>
                    <label class="sa-switch">
                        <input type="checkbox" name="allowed_settings_tabs[]" value="<?= htmlspecialchars($tabKey) ?>" class="allowed_settings_tabs" <?= $isAllowed ? 'checked' : '' ?>>
                        <span class="sa-slider"></span>
                    </label>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sticky Save Action Bar -->
    <div class="sa-sticky-actions">
        <div>
            <span style="font-size: 0.88rem; font-weight: 600; color: #ffffff;">Save Client Admin Rights</span>
            <p style="font-size: 0.75rem; color: var(--sa-text-muted); margin: 0;">Updates will immediately alter the visible interface and accessible routes in the client admin panel.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="../admin/settings.php?tab=homepage" target="_blank" class="sa-btn sa-btn-outline">
                <i data-lucide="external-link" style="width: 16px; height: 16px;"></i> Inspect Client Settings
            </a>
            <button type="submit" class="sa-btn sa-btn-gold">
                <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i> Apply Permissions
            </button>
        </div>
    </div>
</form>

<script>
function toggleAll(className, checkState) {
    document.querySelectorAll('.' + className).forEach(chk => {
        chk.checked = checkState;
    });
}
</script>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
