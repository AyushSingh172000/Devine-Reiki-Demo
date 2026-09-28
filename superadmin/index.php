<?php
require_once __DIR__ . '/auth-check.php';

$pageTitle = 'Master Control Dashboard';

// Load Super Admin Configurations
$layoutJson = $siteSettings['superadmin_homepage_layout'] ?? '[]';
$homepageSections = json_decode($layoutJson, true) ?: [];
$activeSectionCount = 0;
foreach ($homepageSections as $sec) {
    if (!empty($sec['visible'])) $activeSectionCount++;
}

$permsJson = $siteSettings['superadmin_admin_permissions'] ?? '[]';
$adminPerms = json_decode($permsJson, true) ?: [];
$heroCtasEnabled = !empty($adminPerms['show_hero_ctas']);

$togglesJson = $siteSettings['superadmin_site_toggles'] ?? '[]';
$siteToggles = json_decode($togglesJson, true) ?: [];
$isMaintenance = !empty($siteToggles['maintenance_mode']);
$pricesHidden = !empty($siteToggles['hide_prices']);
$bookingsDisabled = !empty($siteToggles['disable_bookings']);

// Count client admins
$clientAdminCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    $clientAdminCount = (int)$stmt->fetchColumn();
} catch (\PDOException $e) {}

include __DIR__ . '/includes/superadmin-header.php';
?>

<!-- =========================================================================
     DASHBOARD OVERVIEW METRICS
     ========================================================================= -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; margin-bottom: 28px;">
    <!-- Metric 1: Homepage Sections -->
    <div class="sa-card" style="margin-bottom: 0; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; letter-spacing: 0.08em;">Homepage Sections</span>
                <h3 style="font-size: 1.8rem; font-weight: 700; color: #ffffff; margin-top: 6px;"><?= $activeSectionCount ?> / <?= count($homepageSections) ?></h3>
                <span style="font-size: 0.78rem; color: var(--sa-green); font-weight: 600;">Active on Live Site</span>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(56, 189, 248, 0.15); color: var(--sa-cyan); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="layers" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
        <div style="margin-top: 14px; pt-2; border-top: 1px solid var(--sa-border); padding-top: 10px;">
            <a href="homepage-manager.php" style="font-size: 0.8rem; color: var(--sa-cyan); text-decoration: none; font-weight: 600;">Reorder &amp; Align Sections →</a>
        </div>
    </div>

    <!-- Metric 2: Hero CTA Buttons for Client Admin -->
    <div class="sa-card" style="margin-bottom: 0; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; letter-spacing: 0.08em;">Hero CTA in Admin</span>
                <h3 style="font-size: 1.8rem; font-weight: 700; color: <?= $heroCtasEnabled ? 'var(--sa-green)' : 'var(--sa-red)' ?>; margin-top: 6px;">
                    <?= $heroCtasEnabled ? 'Enabled' : 'Hidden' ?>
                </h3>
                <span style="font-size: 0.78rem; color: var(--sa-text-muted);">Client Settings Control</span>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: <?= $heroCtasEnabled ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $heroCtasEnabled ? 'var(--sa-green)' : 'var(--sa-red)' ?>; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="<?= $heroCtasEnabled ? 'check-circle' : 'slash' ?>" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
        <div style="margin-top: 14px; border-top: 1px solid var(--sa-border); padding-top: 10px;">
            <a href="client-permissions.php" style="font-size: 0.8rem; color: var(--sa-gold); text-decoration: none; font-weight: 600;">Toggle Client Permissions →</a>
        </div>
    </div>

    <!-- Metric 3: Client Admin Accounts -->
    <div class="sa-card" style="margin-bottom: 0; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; letter-spacing: 0.08em;">Client Admins</span>
                <h3 style="font-size: 1.8rem; font-weight: 700; color: #ffffff; margin-top: 6px;"><?= $clientAdminCount ?></h3>
                <span style="font-size: 0.78rem; color: var(--sa-text-muted);">Users in <code>admin_users</code></span>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(168, 85, 247, 0.15); color: var(--sa-purple); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="users" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
        <div style="margin-top: 14px; border-top: 1px solid var(--sa-border); padding-top: 10px;">
            <a href="admin-users.php" style="font-size: 0.8rem; color: var(--sa-purple); text-decoration: none; font-weight: 600;">Manage Accounts →</a>
        </div>
    </div>

    <!-- Metric 4: Site Governance -->
    <div class="sa-card" style="margin-bottom: 0; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--sa-text-dim); text-transform: uppercase; letter-spacing: 0.08em;">Site Mode</span>
                <h3 style="font-size: 1.8rem; font-weight: 700; color: <?= $isMaintenance ? 'var(--sa-red)' : 'var(--sa-green)' ?>; margin-top: 6px;">
                    <?= $isMaintenance ? 'Maintenance' : 'Live Normal' ?>
                </h3>
                <span style="font-size: 0.78rem; color: var(--sa-text-muted);"><?= $pricesHidden ? 'Prices Hidden' : 'Full Access' ?></span>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: <?= $isMaintenance ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; color: <?= $isMaintenance ? 'var(--sa-red)' : 'var(--sa-green)' ?>; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="globe" style="width: 22px; height: 22px;"></i>
            </div>
        </div>
        <div style="margin-top: 14px; border-top: 1px solid var(--sa-border); padding-top: 10px;">
            <a href="global-controls.php" style="font-size: 0.8rem; color: var(--sa-text-muted); text-decoration: none; font-weight: 600;">Global Switches →</a>
        </div>
    </div>
</div>

<!-- =========================================================================
     CORE SUPER ADMIN WORKSPACES
     ========================================================================= -->
<div class="sa-card">
    <div class="sa-card-header">
        <div>
            <h2 class="sa-card-title">
                <i data-lucide="cpu" style="color: var(--sa-gold);"></i>
                Master Architectural Workspaces
            </h2>
            <p class="sa-card-desc">Control layout architecture, client permission rules, and site-wide behavioral switches.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <!-- Card 1 -->
        <div style="background: rgba(14, 18, 32, 0.8); border: 1px solid var(--sa-border); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(243, 201, 102, 0.15); color: var(--sa-gold); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="move" style="width: 18px; height: 18px;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff;">Homepage Section Architecture</h3>
                </div>
                <p style="font-size: 0.85rem; color: var(--sa-text-muted); line-height: 1.6; margin-bottom: 16px;">
                    Move Testimonials above Reels, position Courses below Products, toggle section visibility, and set Section Heading Alignments (Left, Center, Right) dynamically.
                </p>
            </div>
            <a href="homepage-manager.php" class="sa-btn sa-btn-gold" style="width: 100%;">
                <i data-lucide="sliders-horizontal" style="width: 16px; height: 16px;"></i> Configure Sections
            </a>
        </div>

        <!-- Card 2 -->
        <div style="background: rgba(14, 18, 32, 0.8); border: 1px solid var(--sa-border); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(56, 189, 248, 0.15); color: var(--sa-cyan); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff;">Client Admin Rights &amp; Hero CTAs</h3>
                </div>
                <p style="font-size: 0.85rem; color: var(--sa-text-muted); line-height: 1.6; margin-bottom: 16px;">
                    Control what client admins can view or edit. Toggle Hero CTA Buttons (Book Free Session / Explore Courses) in client settings without writing or commenting PHP code.
                </p>
            </div>
            <a href="client-permissions.php" class="sa-btn sa-btn-outline" style="width: 100%; border-color: rgba(56, 189, 248, 0.4); color: var(--sa-cyan);">
                <i data-lucide="lock-keyhole" style="width: 16px; height: 16px;"></i> Manage Permissions
            </a>
        </div>

        <!-- Card 3 -->
        <div style="background: rgba(14, 18, 32, 0.8); border: 1px solid var(--sa-border); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(168, 85, 247, 0.15); color: var(--sa-purple); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff;">Client Admin Accounts</h3>
                </div>
                <p style="font-size: 0.85rem; color: var(--sa-text-muted); line-height: 1.6; margin-bottom: 16px;">
                    View client administrator accounts, reset lost passwords, add new admin accounts, or deactivate credentials directly from master control.
                </p>
            </div>
            <a href="admin-users.php" class="sa-btn sa-btn-outline" style="width: 100%;">
                <i data-lucide="user-check" style="width: 16px; height: 16px;"></i> Manage Admin Users
            </a>
        </div>

        <!-- Card 4 -->
        <div style="background: rgba(14, 18, 32, 0.8); border: 1px solid var(--sa-border); border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(234, 179, 8, 0.15); color: var(--sa-gold); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="edit-3" style="width: 18px; height: 18px;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff;">Client Admin Sidebar Names</h3>
                </div>
                <p style="font-size: 0.85rem; color: var(--sa-text-muted); line-height: 1.6; margin-bottom: 16px;">
                    Rename every sidebar menu item and section header in the client admin panel, with original screenshot defaults preserved and one-click reset.
                </p>
            </div>
            <a href="sidebar-manager.php" class="sa-btn sa-btn-outline" style="width: 100%; border-color: rgba(234, 179, 8, 0.4); color: var(--sa-gold);">
                <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i> Customize Sidebar Names
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/superadmin-footer.php'; ?>
