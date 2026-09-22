<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
require_once 'upload-helper.php';

$error = '';
$currentAdminUser = $_SESSION['admin_user'] ?? ($_SESSION['admin_username'] ?? 'admin');

// Helper to update site_settings
function setSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

// Helper to update site_stats
function setStat($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO site_stats (stat_key, stat_value, label) VALUES (?, ?, '') ON DUPLICATE KEY UPDATE stat_value = VALUES(stat_value)");
    $stmt->execute([$key, $value]);
}

// =========================================================================
// 1. HANDLE FORM SUBMISSIONS BY TAB
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = trim($_POST['tab'] ?? 'general');

    // TAB 1: GENERAL SETTINGS
    if ($tab === 'general') {
        $generalFields = [
            'site_name', 'site_tagline', 'phone', 'whatsapp', 'email', 
            'address', 'maps_url', 'working_hours', 'facebook_url', 
            'youtube_url', 'instagram_url', 'booking_url'
        ];

        try {
            foreach ($generalFields as $field) {
                $val = trim($_POST[$field] ?? '');
                setSetting($pdo, $field, $val);
            }
            $_SESSION['flash_success'] = "General settings saved successfully!";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error saving general settings: " . $e->getMessage();
        }
        header("Location: settings.php#tab-general");
        exit;
    }

    // TAB 2: BRANDING (Logo, Favicon, OG Image)
    if ($tab === 'branding') {
        try {
            // A. Logo Upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoRes = uploadImage($_FILES['logo'], '../assets/images/', 3145728); // 3MB
                if ($logoRes['success']) {
                    // Also copy directly to assets/images/logo.png and reikilogo1.png for standard references
                    $targetLogo1 = dirname(__DIR__) . '/assets/images/logo.png';
                    $targetLogo2 = dirname(__DIR__) . '/assets/images/reikilogo1.png';
                    @copy($logoRes['full_path'], $targetLogo1);
                    @copy($logoRes['full_path'], $targetLogo2);
                    setSetting($pdo, 'logo_path', $logoRes['path']);
                } else {
                    $_SESSION['flash_error'] = "Logo upload error: " . $logoRes['error'];
                }
            }

            // B. Favicon Upload (.ico, .png, .svg, .webp - max 1MB)
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                $favFile = $_FILES['favicon'];
                $ext = strtolower(pathinfo($favFile['name'], PATHINFO_EXTENSION));
                $allowedFav = ['ico', 'png', 'svg', 'webp'];
                
                if (in_array($ext, $allowedFav) && $favFile['size'] <= 1048576) {
                    $favFilename = 'favicon_' . time() . '.' . $ext;
                    $favDest = dirname(__DIR__) . '/assets/images/' . $favFilename;
                    if (move_uploaded_file($favFile['tmp_name'], $favDest)) {
                        // Also update standard circular favicon and favicon.ico
                        $stdCircle = dirname(__DIR__) . '/assets/images/favicon-circle.png';
                        $stdIco = dirname(__DIR__) . '/assets/images/favicon.ico';
                        @copy($favDest, $stdCircle);
                        if ($ext === 'ico' || $ext === 'png') {
                            @copy($favDest, $stdIco);
                        }
                        setSetting($pdo, 'favicon_path', 'assets/images/' . $favFilename);
                    }
                } else {
                    $_SESSION['flash_error'] = "Invalid favicon format or file size exceeded 1MB. Allowed: .ico, .png, .svg, .webp";
                }
            }

            // C. Apple Touch Icon
            if (isset($_FILES['apple_touch_icon']) && $_FILES['apple_touch_icon']['error'] === UPLOAD_ERR_OK) {
                $touchRes = uploadImage($_FILES['apple_touch_icon'], '../assets/images/', 1048576);
                if ($touchRes['success']) {
                    setSetting($pdo, 'apple_touch_icon_path', $touchRes['path']);
                }
            }

            // D. OpenGraph (OG) Social Card Image (Auto-optimized for WhatsApp & Social Networks)
            if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
                $ogRes = uploadImage($_FILES['og_image'], '../uploads/branding/', 8388608);
                if ($ogRes['success']) {
                    $uploadedFile = dirname(__DIR__) . '/' . ltrim($ogRes['path'], '/');
                    if (file_exists($uploadedFile)) {
                        // Automatically downscale (max 1200x630) and compress under 300KB for WhatsApp
                        if (function_exists('optimizeOgSocialImage')) {
                            optimizeOgSocialImage($uploadedFile);
                        }

                        $ext = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg'])) {
                            @copy($uploadedFile, dirname(__DIR__) . '/assets/images/og-image.jpg');
                        } elseif ($ext === 'png') {
                            @copy($uploadedFile, dirname(__DIR__) . '/assets/images/og-image.png');
                        }

                        $fileSizeBytes = filesize($uploadedFile);
                        if ($fileSizeBytes > 307200) { // 300 KB
                            $sizeKb = round($fileSizeBytes / 1024);
                            $_SESSION['flash_warning'] = "OG Image saved, but note: The image is {$sizeKb} KB. WhatsApp link preview requires images to be strictly under 300 KB. Please compress the image under 300 KB if WhatsApp doesn't display the preview.";
                        }
                    }
                    setSetting($pdo, 'og_image_path', $ogRes['path']);
                }
            }

            if (!isset($_SESSION['flash_error'])) {
                if (!isset($_SESSION['flash_warning'])) {
                    $_SESSION['flash_success'] = "Branding assets (Logo, Favicon & Social OG Image) updated successfully! Changes are now live.";
                } else {
                    $_SESSION['flash_success'] = "Branding assets updated successfully!";
                }
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Branding update error: " . $e->getMessage();
        }
        header("Location: settings.php?tab=branding");
        exit;
    }

    // TAB 3: ABOUT PAGE CONTENT & SITE STATS
    if ($tab === 'about') {
        try {
            // 1. Hero & Introduction
            setSetting($pdo, 'about_hero_badge', trim($_POST['about_hero_badge'] ?? ''));
            setSetting($pdo, 'about_hero_heading', trim($_POST['about_hero_heading'] ?? ''));
            setSetting($pdo, 'about_hero_description', trim($_POST['about_hero_description'] ?? ''));
            setSetting($pdo, 'founding_year', trim($_POST['founding_year'] ?? '2014'));

            // 2. Site Stats & Marquee
            setStat($pdo, 'lives_healed', (string)(int)($_POST['stat_lives_healed'] ?? 30000));
            setStat($pdo, 'years_experience', (string)(int)($_POST['stat_years_experience'] ?? 25));
            setStat($pdo, 'course_levels', (string)(int)($_POST['stat_course_levels'] ?? 6));
            setStat($pdo, 'sessions_completed', (string)(int)($_POST['stat_sessions_completed'] ?? 25000));
            setSetting($pdo, 'about_marquee_extra', trim($_POST['about_marquee_extra'] ?? '100% Authentic Lineage'));

            // 3. Founder Profile (Anupama Agrawal)
            setSetting($pdo, 'about_founder_label', trim($_POST['about_founder_label'] ?? 'Our Founder'));
            setSetting($pdo, 'about_founder_heading', trim($_POST['about_founder_heading'] ?? ''));
            setSetting($pdo, 'about_founder_subheading', trim($_POST['about_founder_subheading'] ?? ''));
            setSetting($pdo, 'about_founder_name', trim($_POST['about_founder_name'] ?? 'Anupama Agrawal'));
            setSetting($pdo, 'about_founder_role', trim($_POST['about_founder_role'] ?? ''));
            setSetting($pdo, 'about_founder_badge', trim($_POST['about_founder_badge'] ?? ''));
            setSetting($pdo, 'about_founder_quote', trim($_POST['about_founder_quote'] ?? ''));
            setSetting($pdo, 'about_founder_quote_caption', trim($_POST['about_founder_quote_caption'] ?? ''));
            setSetting($pdo, 'about_founder_story', trim($_POST['about_founder_story'] ?? ''));
            setSetting($pdo, 'about_founder_specialties', trim($_POST['about_founder_specialties'] ?? ''));

            // Founder Photo Upload
            if (isset($_FILES['founder_photo']) && $_FILES['founder_photo']['error'] === UPLOAD_ERR_OK) {
                $photoRes = uploadImage($_FILES['founder_photo'], '../uploads/team/', 5242880);
                if ($photoRes['success']) {
                    setSetting($pdo, 'about_founder_image', $photoRes['path']);
                }
            }

            // 4. Philosophy Section (4 Pillars)
            setSetting($pdo, 'about_philosophy_label', trim($_POST['about_philosophy_label'] ?? 'Our Philosophy'));
            setSetting($pdo, 'about_philosophy_heading', trim($_POST['about_philosophy_heading'] ?? ''));
            setSetting($pdo, 'about_philosophy_intro', trim($_POST['about_philosophy_intro'] ?? ''));
            for ($p = 1; $p <= 4; $p++) {
                setSetting($pdo, "about_phil_{$p}_icon", trim($_POST["about_phil_{$p}_icon"] ?? ''));
                setSetting($pdo, "about_phil_{$p}_title", trim($_POST["about_phil_{$p}_title"] ?? ''));
                setSetting($pdo, "about_phil_{$p}_desc", trim($_POST["about_phil_{$p}_desc"] ?? ''));
            }

            // 5. Core Values Section (4 Values)
            setSetting($pdo, 'about_values_label', trim($_POST['about_values_label'] ?? 'What We Stand For'));
            setSetting($pdo, 'about_values_heading', trim($_POST['about_values_heading'] ?? ''));
            setSetting($pdo, 'about_values_intro', trim($_POST['about_values_intro'] ?? ''));
            $coreValues = [];
            for ($i = 1; $i <= 4; $i++) {
                $valTitle = trim($_POST["core_val_{$i}_title"] ?? '');
                $valDesc = trim($_POST["core_val_{$i}_desc"] ?? '');
                $coreValues[] = [
                    'title' => $valTitle,
                    'description' => $valDesc
                ];
                setSetting($pdo, "value_{$i}_title", $valTitle);
                setSetting($pdo, "value_{$i}_desc", $valDesc);
            }
            setSetting($pdo, 'core_values', json_encode($coreValues));

            $_SESSION['flash_success'] = "About Us page content & settings saved successfully! All updates are live on the website.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Error saving About page settings: " . $e->getMessage();
        }
        header("Location: settings.php?tab=about");
        exit;
    }

    // TAB 4: HOMEPAGE CONTENT
    if ($tab === 'homepage') {
        try {
            setSetting($pdo, 'hero_heading', trim($_POST['hero_heading'] ?? ''));
            setSetting($pdo, 'hero_subtext', trim($_POST['hero_subtext'] ?? ''));

            $_SESSION['flash_success'] = "Homepage content saved successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Error saving Homepage settings: " . $e->getMessage();
        }
        header("Location: settings.php#tab-homepage");
        exit;
    }

    // TAB 5: ADMIN ACCOUNT SECURITY
    if ($tab === 'account') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newUsername = trim($_POST['new_username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $_SESSION['flash_error'] = "Current password is required to make account changes.";
            header("Location: settings.php#tab-account");
            exit;
        }

        try {
            // Fetch current user from admin_users
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$currentAdminUser]);
            $adminRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$adminRecord || !password_verify($currentPassword, $adminRecord['password'])) {
                $_SESSION['flash_error'] = "Current password was incorrect. Changes rejected.";
                header("Location: settings.php#tab-account");
                exit;
            }

            // A. Update Username if changed
            if (!empty($newUsername) && $newUsername !== $currentAdminUser) {
                // Check uniqueness
                $check = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
                $check->execute([$newUsername, $adminRecord['id']]);
                if ($check->fetch()) {
                    $_SESSION['flash_error'] = "The username '$newUsername' is already taken.";
                    header("Location: settings.php#tab-account");
                    exit;
                }

                $updUser = $pdo->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
                $updUser->execute([$newUsername, $adminRecord['id']]);
                $_SESSION['admin_user'] = $newUsername;
                $_SESSION['admin_username'] = $newUsername;
                $currentAdminUser = $newUsername;
            }

            // B. Update Password if provided
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $_SESSION['flash_error'] = "New password must be at least 6 characters long.";
                    header("Location: settings.php#tab-account");
                    exit;
                }
                if ($newPassword !== $confirmPassword) {
                    $_SESSION['flash_error'] = "New password and confirmation do not match.";
                    header("Location: settings.php#tab-account");
                    exit;
                }

                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $updPass = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $updPass->execute([$hashed, $adminRecord['id']]);
            }

            $_SESSION['flash_success'] = "Admin account security details updated successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Account update error: " . $e->getMessage();
        }
        header("Location: settings.php#tab-account");
        exit;
    }
}

// =========================================================================
// 2. FETCH CURRENT SETTINGS & STATS
// =========================================================================

$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$stats = [];
try {
    $stmt = $pdo->query("SELECT stat_key, stat_value FROM site_stats");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['stat_key']] = $row['stat_value'];
    }
} catch (PDOException $e) {}

$coreValues = [];
if (!empty($settings['core_values'])) {
    $coreValues = json_decode($settings['core_values'], true) ?: [];
}

$pageTitle = 'Site Settings';
require_once 'includes/admin-header.php';
?>

<div style="max-width: 960px; margin: 0 auto;">
    <div class="flex-between mb-3">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary);">
            Global Site Configuration
        </h2>
        <span class="text-muted" style="font-size: 0.85rem;">All updates apply site-wide instantly</span>
    </div>

    <!-- SETTINGS NAVIGATION TABS -->
    <div class="settings-tabs" id="settingsTabsBar">
        <button type="button" class="settings-tab-btn active flex items-center gap-1" data-tab="general">
            <i data-lucide="settings" style="width: 16px; height: 16px;"></i> General Settings
        </button>
        <button type="button" class="settings-tab-btn flex items-center gap-1" data-tab="branding">
            <i data-lucide="palette" style="width: 16px; height: 16px;"></i> Branding &amp; Assets
        </button>
        <button type="button" class="settings-tab-btn flex items-center gap-1" data-tab="about">
            <i data-lucide="book-open" style="width: 16px; height: 16px;"></i> About Page &amp; Stats
        </button>
        <button type="button" class="settings-tab-btn flex items-center gap-1" data-tab="homepage">
            <i data-lucide="home" style="width: 16px; height: 16px;"></i> Homepage Content
        </button>
        <button type="button" class="settings-tab-btn flex items-center gap-1" data-tab="account">
            <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i> Admin Account
        </button>
    </div>

    <!-- =====================================================================
         TAB 1: GENERAL SETTINGS
         ===================================================================== -->
    <div class="tab-content active" id="tab-general">
        <div class="admin-card">
            <h3 class="admin-card-title mb-3" style="color: var(--gold);">General Contact &amp; Business Information</h3>
            <form action="settings.php" method="POST">
                <input type="hidden" name="tab" value="general">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'Reiki Bliss') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Site Tagline / Slogan</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars($settings['site_tagline'] ?? 'Authentic Usui Reiki Healing, Energy Alignment & Crystal Therapy') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '+91 98765 43210') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp Number (with country code)</label>
                        <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($settings['whatsapp'] ?? '+91 98765 43210') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email'] ?? 'contact@reikiwebsite.com') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Working / Operating Hours</label>
                        <input type="text" name="working_hours" class="form-control" value="<?= htmlspecialchars($settings['working_hours'] ?? 'Mon–Sat: 7:00 AM – 7:00 PM · Sun: 9:00 AM – 1:00 PM') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Physical Center Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($settings['address'] ?? '108 Healing Touch Way, Spiritual Enclave, New Delhi - 110001') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Google Maps Embed / Location URL</label>
                    <input type="text" name="maps_url" class="form-control" value="<?= htmlspecialchars($settings['maps_url'] ?? '') ?>" placeholder="https://maps.google.com/...">
                </div>

                <div class="form-group">
                    <label class="form-label">Booking Appointment URL (Google Calendar / Calendly)</label>
                    <input type="text" name="booking_url" class="form-control" value="<?= htmlspecialchars($settings['booking_url'] ?? 'https://calendar.google.com/calendar/appointments/...') ?>">
                    <div class="form-hint">Used for all "Book Session" and "Book Consultation" buttons site-wide.</div>
                </div>

                <h4 style="font-size: 0.95rem; color: var(--gold); margin: 24px 0 14px; border-top: 1px solid var(--card-border); padding-top: 16px;">
                    Social Media Channels
                </h4>

                <div class="form-row" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="form-group">
                        <label class="form-label">Facebook URL</label>
                        <input type="text" name="facebook_url" class="form-control" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">YouTube URL</label>
                        <input type="text" name="youtube_url" class="form-control" value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instagram URL</label>
                        <input type="text" name="instagram_url" class="form-control" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/...">
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gold">
                        Save General Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: BRANDING (Logo, Favicon, OG Image)
         ===================================================================== -->
    <div class="tab-content" id="tab-branding">
        <div class="admin-card">
            <div class="flex-between mb-3" style="border-bottom: 1px solid var(--card-border); padding-bottom: 14px;">
                <div>
                    <h3 class="admin-card-title" style="color: var(--text-primary); font-size: 1.15rem; font-weight: 700;">Logo &amp; Favicon Management</h3>
                    <p class="text-muted" style="font-size: 0.85rem; margin-top: 2px;">Update brand imagery displayed across the public website, browser tabs, and admin panel.</p>
                </div>
                <span class="badge badge-gold">Active Branding</span>
            </div>

            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tab" value="branding">

                <?php 
                    $currentLogoPath = !empty($settings['logo_path']) ? '../' . ltrim($settings['logo_path'], '/') : '../assets/images/reikilogo1.png';
                    $currentFavPath = !empty($settings['favicon_path']) ? '../' . ltrim($settings['favicon_path'], '/') : '../assets/images/favicon-circle.png';
                    $currentTouchPath = !empty($settings['apple_touch_icon_path']) ? '../' . ltrim($settings['apple_touch_icon_path'], '/') : $currentFavPath;
                    $cacheVer = time();
                ?>

                <!-- 1. Site Logo -->
                <div class="form-group mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 24px;">
                    <label class="form-label" style="font-size: 0.95rem; font-weight: 700;">Main Website &amp; Admin Logo</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Appears in the website navigation bar, admin sidebar, and footer.</p>
                    
                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <div style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 10px; padding: 14px 24px; display: inline-flex; align-items: center; justify-content: center; min-width: 180px; min-height: 80px; box-shadow: var(--card-shadow);">
                            <img id="logoLivePreview" src="<?= htmlspecialchars($currentLogoPath) ?>?v=<?= $cacheVer ?>" alt="Current Logo" style="max-height: 52px; max-width: 170px; object-fit: contain;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <input type="file" id="logoFileInput" name="logo" class="form-control mb-1" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                            <span class="form-hint">Recommended: Transparent PNG, SVG or WebP with dimensions ~200×60px (Max 3MB).</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Favicon (Browser Tab Icon) -->
                <div class="form-group mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 24px;">
                    <label class="form-label" style="font-size: 0.95rem; font-weight: 700;">Favicon (Browser Tab Icon)</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Small icon shown in browser tabs, bookmarks, and mobile shortcuts for both the website and admin panel.</p>
                    
                    <!-- Realistic Browser Tab Mockup -->
                    <div style="background: #f1f5f9; border: 1px solid var(--card-border); border-radius: 10px; padding: 16px; margin-bottom: 14px;">
                        <div style="font-size: 0.76rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 8px;">Live Browser Tab Simulation</div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; padding: 7px 14px; border-radius: 8px 8px 0 0; border: 1px solid var(--card-border); border-bottom: none; box-shadow: 0 -1px 3px rgba(0,0,0,0.04); max-width: 280px;">
                            <img id="favLivePreview" src="<?= htmlspecialchars($currentFavPath) ?>?v=<?= $cacheVer ?>" alt="Favicon Preview" style="width: 18px; height: 18px; object-fit: contain; border-radius: 50%;">
                            <span style="font-size: 0.82rem; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($settings['site_name'] ?? 'Reiki Bliss') ?> | Heal. Balance...</span>
                            <span style="color: #94a3b8; font-size: 0.72rem; margin-left: auto;">✕</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <div style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 10px; width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center; box-shadow: var(--card-shadow);">
                            <img id="favIconBoxPreview" src="<?= htmlspecialchars($currentFavPath) ?>?v=<?= $cacheVer ?>" alt="Favicon" style="width: 36px; height: 36px; object-fit: contain; border-radius: 50%;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <input type="file" id="favFileInput" name="favicon" class="form-control mb-1" accept=".ico,image/png,image/svg+xml,image/webp">
                            <span class="form-hint">Accepted formats: .ico, .png, .svg, .webp (Recommended 32×32 or 64×64 circular PNG, Max 1MB).</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Apple Touch Icon -->
                <div class="form-group mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 24px;">
                    <label class="form-label" style="font-size: 0.95rem; font-weight: 700;">Apple Touch Icon (iOS Home Screen)</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Icon shown when users add or bookmark your website to their Apple iPhone/iPad home screen.</p>
                    
                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <div style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 12px; width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center; box-shadow: var(--card-shadow);">
                            <img id="touchLivePreview" src="<?= htmlspecialchars($currentTouchPath) ?>?v=<?= $cacheVer ?>" alt="Apple Touch Icon" style="width: 44px; height: 44px; object-fit: contain; border-radius: 8px;">
                        </div>
                        <div style="flex: 1; min-width: 250px;">
                            <input type="file" id="touchFileInput" name="apple_touch_icon" class="form-control mb-1" accept="image/png">
                            <span class="form-hint">Square 180×180 PNG recommended.</span>
                        </div>
                    </div>
                </div>

                <!-- 4. OpenGraph Social Share Card (WhatsApp, Facebook, Twitter Preview) -->
                <div class="form-group mb-4">
                    <label class="form-label" style="font-size: 0.95rem; font-weight: 700;">OpenGraph Social Share Image (WhatsApp / Facebook Preview)</label>
                    <p class="text-muted mb-3" style="font-size: 0.84rem;">Preview image displayed automatically whenever your website link is shared on <strong>WhatsApp, Facebook, Twitter (X), LinkedIn, or iMessage</strong>.</p>
                    
                    <?php 
                        $rawOg = !empty($settings['og_image_path']) ? ltrim($settings['og_image_path'], '/') : 'assets/images/og-image.jpg';
                        $docRoot = dirname(__DIR__);
                        if (!file_exists($docRoot . '/' . $rawOg)) {
                            $rawOg = file_exists($docRoot . '/assets/images/og-image.jpg') ? 'assets/images/og-image.jpg' : 'assets/images/logo.png';
                        }
                        $ogPath = '../' . $rawOg;
                        $realOgFile = $docRoot . '/' . $rawOg;
                        
                        $ogFileSizeKb = 0;
                        $ogDimensions = '';
                        $ogIsWhatsAppOk = true;
                        if (file_exists($realOgFile)) {
                            $ogSizeBytes = filesize($realOgFile);
                            $ogFileSizeKb = round($ogSizeBytes / 1024, 1);
                            $ogIsWhatsAppOk = ($ogSizeBytes <= 307200); // 300 KB limit
                            $dims = @getimagesize($realOgFile);
                            if ($dims) {
                                $ogDimensions = "{$dims[0]} × {$dims[1]} px";
                            }
                        }
                        $hostName = $_SERVER['HTTP_HOST'] ?? 'reikibliss.vijatshi.ai';
                    ?>

                    <!-- Realistic WhatsApp / Social Link Preview Card -->
                    <div style="background: #eef2f6; border: 1px solid var(--card-border); border-radius: 12px; padding: 16px; margin-bottom: 16px; max-width: 520px;">
                        <div style="font-size: 0.76rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 8px;">WhatsApp / Social Link Live Preview</div>
                        <!-- WhatsApp Message Bubble Mockup -->
                        <div style="background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #d1d5db; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                            <div style="width: 100%; height: 170px; background: #f8fafc; overflow: hidden; position: relative;">
                                <img id="ogLivePreview" src="<?= htmlspecialchars($ogPath) ?>?v=<?= $cacheVer ?>" alt="OG Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/logo.png'">
                                <?php if (!empty($ogDimensions)): ?>
                                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">
                                        <?= $ogDimensions ?> (<?= $ogFileSizeKb ?> KB)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 10px 14px; background: #f0fdf4; border-top: 1px solid #e2e8f0;">
                                <div style="font-size: 0.74rem; color: #16a34a; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($hostName) ?></div>
                                <div style="font-size: 0.92rem; font-weight: 700; color: #0f172a; margin: 3px 0 2px;"><?= htmlspecialchars($settings['site_name'] ?? 'Reiki Bliss') ?> | Heal. Balance. Transform.</div>
                                <div style="font-size: 0.8rem; color: #475569; line-height: 1.35;">Experience authentic Usui Reiki healing, certified courses, and Reiki-charged crystal bracelets.</div>
                            </div>
                        </div>
                    </div>

                    <div style="max-width: 520px;">
                        <input type="file" id="ogFileInput" name="og_image" class="form-control mb-1" accept="image/jpeg,image/png,image/webp">
                        <span class="form-hint">Recommended dimensions: <strong>1200 × 630 pixels</strong> (or square 600 × 600), JPG/PNG/WebP, <strong>strictly under 300 KB</strong> for WhatsApp compatibility. (Images are automatically resized and compressed on upload).</span>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gold">
                        <i data-lucide="check" style="width: 16px; height: 16px;"></i> Save Branding Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================          TAB 3: ABOUT US PAGE CUSTOMIZER & STATS
         ===================================================================== -->
    <div class="tab-content" id="tab-about">
        <div class="admin-card">
            <div class="flex-between mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 16px;">
                <div>
                    <h3 class="admin-card-title" style="color: var(--text-primary); font-size: 1.2rem; font-weight: 700;">
                        About Us Page Full Customizer
                    </h3>
                    <p class="text-muted" style="font-size: 0.85rem; margin-top: 2px;">
                        Manage every section of the public About Us page — from the Hero banner &amp; Impact counters to Founder Anupama Agrawal's biography, philosophy pillars, and core values.
                    </p>
                </div>
                <a href="../about.php" target="_blank" class="btn btn-outline btn-sm flex items-center gap-1" style="white-space: nowrap;">
                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i> View Live Page
                </a>
            </div>

            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tab" value="about">

                <!-- -------------------------------------------------------------
                     SECTION 1: HERO & INTRODUCTION BANNER
                     ------------------------------------------------------------- -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div class="flex items-center gap-2 mb-3">
                        <span style="background: var(--gold); color: #000; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px;">SECTION 1</span>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">Hero Banner &amp; Introduction</h4>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Hero Badge Tagline</label>
                            <input type="text" name="about_hero_badge" class="form-control" value="<?= htmlspecialchars($settings['about_hero_badge'] ?? 'Est. 2014 · Adajan, Surat') ?>" placeholder="e.g. Est. 2014 · Adajan, Surat">
                            <span class="form-hint">Appears in small pill badge above the main title.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Founding Year</label>
                            <input type="text" name="founding_year" class="form-control" value="<?= htmlspecialchars($settings['founding_year'] ?? '2014') ?>" placeholder="e.g. 2014">
                            <span class="form-hint">Used in center history and badge calculations.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hero Main Headline (H1)</label>
                        <input type="text" name="about_hero_heading" class="form-control" value="<?= htmlspecialchars($settings['about_hero_heading'] ?? 'A Journey Inward.<br>A Purpose to <em>Help Others Heal.</em>') ?>" placeholder="e.g. A Journey Inward.<br>A Purpose to <em>Help Others Heal.</em>">
                        <span class="form-hint">Tip: Use <code>&lt;em&gt;text&lt;/em&gt;</code> for cursive gold italic accent, and <code>&lt;br&gt;</code> for line breaks.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hero Introduction Paragraph</label>
                        <textarea name="about_hero_description" class="form-control" rows="3" placeholder="Introduction text under headline..."><?= htmlspecialchars($settings['about_hero_description'] ?? 'Reiki Bliss was founded by Anupama Agrawal, a Reiki Grand Master and Spiritual Wellness Coach dedicated to authentic energy healing, self-awareness, and holistic inner transformation.') ?></textarea>
                    </div>
                </div>

                <!-- -------------------------------------------------------------
                     SECTION 2: IMPACT COUNTER STATISTICS & MARQUEE
                     ------------------------------------------------------------- -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div class="flex items-center gap-2 mb-3">
                        <span style="background: var(--gold); color: #000; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px;">SECTION 2</span>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">Impact Statistics &amp; Scrolling Marquee</h4>
                    </div>

                    <div class="form-row" style="grid-template-columns: repeat(4, 1fr);">
                        <div class="form-group">
                            <label class="form-label">Healed Clients</label>
                            <input type="number" name="stat_lives_healed" class="form-control" value="<?= htmlspecialchars($stats['lives_healed'] ?? '30000') ?>">
                            <span class="form-hint">Displayed as: <strong><?= !empty($stats['lives_healed']) ? $stats['lives_healed'] : '30000' ?>+ Healed Clients</strong></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Years of Practice</label>
                            <input type="number" name="stat_years_experience" class="form-control" value="<?= htmlspecialchars($stats['years_experience'] ?? '25') ?>">
                            <span class="form-hint">Displayed as: <strong><?= !empty($stats['years_experience']) ? $stats['years_experience'] : '25' ?>+ Years Experience</strong></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Course Levels</label>
                            <input type="number" name="stat_course_levels" class="form-control" value="<?= htmlspecialchars($stats['course_levels'] ?? '6') ?>">
                            <span class="form-hint">Displayed as: <strong><?= !empty($stats['course_levels']) ? $stats['course_levels'] : '6' ?> Course Levels</strong></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sessions Completed</label>
                            <input type="number" name="stat_sessions_completed" class="form-control" value="<?= htmlspecialchars($stats['sessions_completed'] ?? '25000') ?>">
                            <span class="form-hint">Displayed as: <strong><?= !empty($stats['sessions_completed']) ? $stats['sessions_completed'] : '25000' ?>+ Sessions</strong></span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">Scrolling Marquee Extra Highlight</label>
                        <input type="text" name="about_marquee_extra" class="form-control" value="<?= htmlspecialchars($settings['about_marquee_extra'] ?? '100% Authentic Lineage') ?>" placeholder="e.g. 100% Authentic Lineage">
                        <span class="form-hint">Rotates alongside the numerical stats in the continuous infinite marquee.</span>
                    </div>
                </div>

                <!-- -------------------------------------------------------------
                     SECTION 3: FOUNDER PROFILE (ANUPAMA AGRAWAL)
                     ------------------------------------------------------------- -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div class="flex items-center gap-2 mb-3">
                        <span style="background: var(--gold); color: #000; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px;">SECTION 3</span>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">Founder Profile &amp; Biography (Anupama Agrawal)</h4>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Section Eyebrow Label</label>
                            <input type="text" name="about_founder_label" class="form-control" value="<?= htmlspecialchars($settings['about_founder_label'] ?? 'Our Founder') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Section Main Title</label>
                            <input type="text" name="about_founder_heading" class="form-control" value="<?= htmlspecialchars($settings['about_founder_heading'] ?? 'Meet The Soul Behind <em>Reiki Bliss</em>') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Section Tagline / Subtitle</label>
                        <input type="text" name="about_founder_subheading" class="form-control" value="<?= htmlspecialchars($settings['about_founder_subheading'] ?? 'Dedicated to authentic healing, energy alignment, and empowering individuals to discover their inner harmony.') ?>">
                    </div>

                    <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label class="form-label">Founder Full Name</label>
                            <input type="text" name="about_founder_name" class="form-control" value="<?= htmlspecialchars($settings['about_founder_name'] ?? 'Anupama Agrawal') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Founder Title / Credentials</label>
                            <input type="text" name="about_founder_role" class="form-control" value="<?= htmlspecialchars($settings['about_founder_role'] ?? 'Founder, Reiki Grand Master & Spiritual Wellness Coach') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Photo Badge Pill</label>
                            <input type="text" name="about_founder_badge" class="form-control" value="<?= htmlspecialchars($settings['about_founder_badge'] ?? 'Reiki Grand Master') ?>">
                        </div>
                    </div>

                    <!-- Founder Photo Upload -->
                    <?php 
                        $founderImgSrc = !empty($settings['about_founder_image']) ? '../' . ltrim($settings['about_founder_image'], '/') : '../assets/images/team/anupama_mam.jpeg';
                    ?>
                    <div class="form-group mb-3">
                        <label class="form-label">Founder Portrait Photo</label>
                        <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold); box-shadow: 0 4px 12px rgba(0,0,0,0.15); flex-shrink: 0; background: #fff;">
                                <img id="founderImgPreview" src="<?= htmlspecialchars($founderImgSrc) ?>?v=<?= time() ?>" alt="Founder Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/team/anupama_mam.jpeg'">
                            </div>
                            <div style="flex: 1; min-width: 250px;">
                                <input type="file" id="founderImgInput" name="founder_photo" class="form-control mb-1" accept="image/jpeg,image/png,image/webp">
                                <span class="form-hint">Upload a portrait or square photo (JPG, PNG, WebP, max 5MB). Photo is automatically styled on the page.</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Guiding Quote</label>
                            <input type="text" name="about_founder_quote" class="form-control" value="<?= htmlspecialchars($settings['about_founder_quote'] ?? 'Think Positive, Be Positive.') ?>" placeholder="e.g. Think Positive, Be Positive.">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quote Caption / Context</label>
                            <input type="text" name="about_founder_quote_caption" class="form-control" value="<?= htmlspecialchars($settings['about_founder_quote_caption'] ?? 'The guiding belief at the heart of her life and healing practice') ?>">
                        </div>
                    </div>

                    <?php 
                        $defaultStory = "Reiki Bliss was founded by Anupama Agrawal, a Reiki Grand Master and Spiritual Wellness Coach whose journey into holistic wellness began with a simple but powerful interest in meditation and self-healing.\n\nWhat started as a personal practice gradually became a deeper calling. For more than a decade, Anupama has studied and practiced Reiki with dedication, progressing to the level of Reiki Grand Master while continuing to explore complementary spiritual and energy practices.\n\nThrough Reiki Bliss, Anupama creates a warm, supportive space for people to slow down, reconnect with themselves and explore practices that can support greater balance, clarity and inner well-being. Her approach is personal and grounded—meeting each individual where they are rather than treating wellness as one-size-fits-all.\n\nHer work today includes individual consultations as well as classes for those who wish to learn and deepen their own practice across a comprehensive range of sacred energy disciplines.";
                        $founderStoryVal = !empty($settings['about_founder_story']) ? $settings['about_founder_story'] : $defaultStory;
                    ?>
                    <div class="form-group">
                        <label class="form-label">Founder Detailed Story &amp; Journey</label>
                        <textarea name="about_founder_story" class="form-control" rows="8" placeholder="Enter multi-paragraph founder journey..."><?= htmlspecialchars($founderStoryVal) ?></textarea>
                        <span class="form-hint">Separate paragraphs with a blank line. Each paragraph will be rendered cleanly on the website.</span>
                    </div>

                    <?php 
                        $defaultSpecialties = "Reiki Healing, Chakra Balancing, Guided Meditation, Lama Fera, Access Bars, Angel Healing, Victory Reiki, Money Reiki, Switch Words, Tarot Card Reading";
                        $founderSpecVal = !empty($settings['about_founder_specialties']) ? $settings['about_founder_specialties'] : $defaultSpecialties;
                    ?>
                    <div class="form-group">
                        <label class="form-label">Core Healing Modalities &amp; Offerings (Specialty Tags)</label>
                        <input type="text" name="about_founder_specialties" class="form-control" value="<?= htmlspecialchars($founderSpecVal) ?>" placeholder="Reiki Healing, Chakra Balancing, ...">
                        <span class="form-hint">Comma-separated list. Each specialty is displayed as a badge in the offerings box.</span>
                    </div>
                </div>

                <!-- -------------------------------------------------------------
                     SECTION 4: THE PHILOSOPHY BEHIND REIKI BLISS
                     ------------------------------------------------------------- -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div class="flex items-center gap-2 mb-3">
                        <span style="background: var(--gold); color: #000; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px;">SECTION 4</span>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">The Philosophy Behind Reiki Bliss</h4>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Philosophy Section Eyebrow</label>
                            <input type="text" name="about_philosophy_label" class="form-control" value="<?= htmlspecialchars($settings['about_philosophy_label'] ?? 'Our Philosophy') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Philosophy Section Title</label>
                            <input type="text" name="about_philosophy_heading" class="form-control" value="<?= htmlspecialchars($settings['about_philosophy_heading'] ?? 'The Philosophy Behind <em>Reiki Bliss</em>') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Philosophy Introduction Paragraph</label>
                        <textarea name="about_philosophy_intro" class="form-control" rows="2"><?= htmlspecialchars($settings['about_philosophy_intro'] ?? 'Anupama believes that meaningful change often begins by turning inward—creating space to understand ourselves, release what no longer serves us and become more intentional about the energy we bring into our lives.') ?></textarea>
                    </div>

                    <?php 
                        $defaultPhil = [
                            ['icon' => '🧘‍♀️', 'title' => 'Turning Inward', 'desc' => 'Meaningful change begins by creating space to understand ourselves, gently releasing emotional and energetic blocks that no longer serve us, and cultivating intentional positive energy.'],
                            ['icon' => '✨', 'title' => 'Our Mission', 'desc' => 'To make spiritual wellness approachable and to help more people discover the transformative power of self-awareness, self-healing, and conscious positive living.'],
                            ['icon' => '🌱', 'title' => 'Begin Where You Are', 'desc' => 'Whether you are completely new to spiritual wellness, looking for greater balance in your everyday life, or hoping to deepen an existing practice, you are welcome to begin exactly where you are.'],
                            ['icon' => '🌟', 'title' => 'Your Journey is Your Own', 'desc' => 'Your journey is your own. Reiki Bliss is here to provide grounded, compassionate guidance and create a safe sanctuary to help you explore it at your own rhythm.']
                        ];
                    ?>

                    <div class="form-row" style="grid-template-columns: 1fr 1fr;">
                        <?php for ($p = 1; $p <= 4; $p++): 
                            $pIdx = $p - 1;
                            $pIcon = $settings["about_phil_{$p}_icon"] ?? $defaultPhil[$pIdx]['icon'];
                            $pTitle = $settings["about_phil_{$p}_title"] ?? $defaultPhil[$pIdx]['title'];
                            $pDesc = $settings["about_phil_{$p}_desc"] ?? $defaultPhil[$pIdx]['desc'];
                        ?>
                            <div class="form-group" style="background: rgba(30, 21, 69, 0.35); border: 1px solid var(--card-border); border-radius: 10px; padding: 14px;">
                                <div class="flex items-center gap-2 mb-2">
                                    <input type="text" name="about_phil_<?= $p ?>_icon" class="form-control" style="width: 50px; text-align: center; font-size: 1.2rem; padding: 4px;" value="<?= htmlspecialchars($pIcon) ?>" title="Emoji Icon">
                                    <input type="text" name="about_phil_<?= $p ?>_title" class="form-control" style="flex: 1; font-weight: 700; color: var(--gold);" value="<?= htmlspecialchars($pTitle) ?>" placeholder="Pillar Title">
                                </div>
                                <textarea name="about_phil_<?= $p ?>_desc" class="form-control" rows="3" placeholder="Pillar Description" style="font-size: 0.84rem;"><?= htmlspecialchars($pDesc) ?></textarea>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- -------------------------------------------------------------
                     SECTION 5: OUR CORE VALUES
                     ------------------------------------------------------------- -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div class="flex items-center gap-2 mb-3">
                        <span style="background: var(--gold); color: #000; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px;">SECTION 5</span>
                        <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">Our Core Values (What We Stand For)</h4>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Values Section Eyebrow</label>
                            <input type="text" name="about_values_label" class="form-control" value="<?= htmlspecialchars($settings['about_values_label'] ?? 'What We Stand For') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Values Section Title</label>
                            <input type="text" name="about_values_heading" class="form-control" value="<?= htmlspecialchars($settings['about_values_heading'] ?? 'Our Core <em>Values</em>') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Values Section Tagline</label>
                        <textarea name="about_values_intro" class="form-control" rows="2"><?= htmlspecialchars($settings['about_values_intro'] ?? 'Principles that guide every healing session, attunement workshop, and crystal recommendation at our center.') ?></textarea>
                    </div>

                    <?php 
                        $defaultValues = [
                            ['title' => 'Compassionate Presence', 'description' => 'Every session is held with deep unconditional empathy, confidentiality, and spiritual grounding.'],
                            ['title' => 'Authentic Lineage', 'description' => 'Direct Usui Reiki tradition handed down through accredited grandmasters with authentic attunement.'],
                            ['title' => 'Holistic Transformation', 'description' => 'Addressing subtle energetic root causes rather than just superficial physical symptoms.'],
                            ['title' => 'Empowered Self-Healing', 'description' => 'Guiding every student and healee with knowledge to sustain their own energetic balance.']
                        ];
                        $vals = !empty($coreValues) ? $coreValues : $defaultValues;
                    ?>

                    <div class="form-row" style="grid-template-columns: 1fr 1fr;">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="form-group" style="background: rgba(30, 21, 69, 0.35); border: 1px solid var(--card-border); border-radius: 10px; padding: 14px;">
                                <label class="form-label" style="color: var(--gold); font-weight: 600;">Value #<?= $i + 1 ?> Title</label>
                                <input type="text" name="core_val_<?= $i + 1 ?>_title" class="form-control mb-2" value="<?= htmlspecialchars($vals[$i]['title'] ?? ($settings["value_" . ($i + 1) . "_title"] ?? '')) ?>">
                                
                                <label class="form-label" style="font-size: 0.78rem;">Description</label>
                                <textarea name="core_val_<?= $i + 1 ?>_desc" class="form-control" rows="2"><?= htmlspecialchars($vals[$i]['description'] ?? ($settings["value_" . ($i + 1) . "_desc"] ?? '')) ?></textarea>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mt-4" style="position: sticky; bottom: 16px; z-index: 10; background: var(--bg-card); padding: 14px 20px; border: 1px solid var(--card-border); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
                    <div class="flex-between">
                        <button type="submit" class="btn btn-gold flex items-center gap-2">
                            <i data-lucide="check-circle" style="width: 17px; height: 17px;"></i> Save All About Us Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================================
         TAB 4: HOMEPAGE CONTENT
         ===================================================================== -->
    <div class="tab-content" id="tab-homepage">
        <div class="admin-card">
            <h3 class="admin-card-title mb-3" style="color: var(--gold);">Homepage Main Hero &amp; Sections</h3>
            <form action="settings.php" method="POST">
                <input type="hidden" name="tab" value="homepage">

                <div class="form-group">
                    <label class="form-label">Hero Main Headline (H1)</label>
                    <input type="text" name="hero_heading" class="form-control" value="<?= htmlspecialchars($settings['hero_heading'] ?? 'Heal. Balance. Transform.') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Hero Subtext Paragraph</label>
                    <textarea name="hero_subtext" class="form-control" rows="3"><?= htmlspecialchars($settings['hero_subtext'] ?? 'Awaken your inner vitality with authentic Usui Reiki healing sessions, transformative certification courses, and sacred energized crystal bracelets.') ?></textarea>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gold">
                        Save Homepage Content
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================================
         TAB 5: ADMIN ACCOUNT SECURITY
         ===================================================================== -->
    <div class="tab-content" id="tab-account">
        <div class="admin-card">
            <h3 class="admin-card-title mb-3" style="color: var(--gold);">Administrator Credentials &amp; Password</h3>
            <form action="settings.php" method="POST">
                <input type="hidden" name="tab" value="account">

                <div class="form-group">
                    <label class="form-label">Current Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentAdminUser) ?>" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; color: var(--gold); font-weight: 600;">
                </div>

                <div class="form-group">
                    <label for="newUsernameInput" class="form-label">Change Username (Optional)</label>
                    <input type="text" id="newUsernameInput" name="new_username" class="form-control" placeholder="Leave unchanged to keep current username" value="<?= htmlspecialchars($currentAdminUser) ?>">
                </div>

                <div style="border-top: 1px solid var(--card-border); margin: 24px 0 20px; padding-top: 18px;">
                    <div class="form-group">
                        <label for="currentPass" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" id="currentPass" name="current_password" class="form-control" placeholder="Enter your current password to verify identity" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="newPass" class="form-label">New Password</label>
                            <input type="password" id="newPass" name="new_password" class="form-control" placeholder="Leave empty if not changing password">
                            
                            <!-- Password Strength Meter -->
                            <div class="password-strength-bar">
                                <div id="passStrengthFill" class="password-strength-fill"></div>
                            </div>
                            <span id="passStrengthLabel" style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 4px;"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirmPass" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirmPass" name="confirm_password" class="form-control" placeholder="Re-type new password">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gold">
                        Update Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Tab Switching Controller
const tabButtons = document.querySelectorAll('.settings-tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

function activateTab(tabId) {
    tabButtons.forEach(btn => {
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    tabContents.forEach(content => {
        if (content.id === 'tab-' + tabId) {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
        }
    });

    // Update URL hash
    window.location.hash = 'tab-' + tabId;
}

tabButtons.forEach(btn => {
    btn.addEventListener('click', function() {
        const tab = this.getAttribute('data-tab');
        activateTab(tab);
    });
});

// Load Active Tab from URL Query Param or URL Hash
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const hash = window.location.hash.replace('#tab-', '');
    const activeTab = tabParam || hash;
    if (activeTab && document.getElementById('tab-' + activeTab)) {
        activateTab(activeTab);
    }
});

// Instant Client-Side Image Previews for Branding Assets
const logoFileInput = document.getElementById('logoFileInput');
const logoLivePreview = document.getElementById('logoLivePreview');
if (logoFileInput && logoLivePreview) {
    logoFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                logoLivePreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

const favFileInput = document.getElementById('favFileInput');
const favLivePreview = document.getElementById('favLivePreview');
const favIconBoxPreview = document.getElementById('favIconBoxPreview');
if (favFileInput) {
    favFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (favLivePreview) favLivePreview.src = e.target.result;
                if (favIconBoxPreview) favIconBoxPreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

const touchFileInput = document.getElementById('touchFileInput');
const touchLivePreview = document.getElementById('touchLivePreview');
if (touchFileInput && touchLivePreview) {
    touchFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                touchLivePreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

const ogFileInput = document.getElementById('ogFileInput');
const ogLivePreview = document.getElementById('ogLivePreview');
if (ogFileInput && ogLivePreview) {
    ogFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                ogLivePreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

const founderImgInput = document.getElementById('founderImgInput');
const founderImgPreview = document.getElementById('founderImgPreview');
if (founderImgInput && founderImgPreview) {
    founderImgInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                founderImgPreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Password Strength Meter
const newPass = document.getElementById('newPass');
const strengthFill = document.getElementById('passStrengthFill');
const strengthLabel = document.getElementById('passStrengthLabel');

if (newPass && strengthFill && strengthLabel) {
    newPass.addEventListener('input', function() {
        const val = this.value;
        if (!val) {
            strengthFill.style.width = '0%';
            strengthLabel.textContent = '';
            return;
        }

        let score = 0;
        if (val.length >= 6) score += 25;
        if (val.length >= 10) score += 25;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score += 25;
        if (/[\d\W]/.test(val)) score += 25;

        strengthFill.style.width = score + '%';
        if (score <= 25) {
            strengthFill.style.backgroundColor = '#f87171'; // Red
            strengthLabel.textContent = 'Weak Password';
            strengthLabel.style.color = '#f87171';
        } else if (score <= 75) {
            strengthFill.style.backgroundColor = '#fbbf24'; // Yellow
            strengthLabel.textContent = 'Moderate Password';
            strengthLabel.style.color = '#fbbf24';
        } else {
            strengthFill.style.backgroundColor = '#4ade80'; // Green
            strengthLabel.textContent = 'Strong Password';
            strengthLabel.style.color = '#4ade80';
        }
    });
}
</script>

<?php require_once 'includes/admin-footer.php'; ?>
