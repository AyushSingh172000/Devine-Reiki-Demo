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
                $logoRes = uploadImage($_FILES['logo'], '../assets/images/', 2097152); // 2MB
                if ($logoRes['success']) {
                    // Also copy directly to assets/images/logo.png for standard references
                    $targetLogo = dirname(__DIR__) . '/assets/images/logo.png';
                    @copy($logoRes['full_path'], $targetLogo);
                    setSetting($pdo, 'logo_path', $logoRes['path']);
                } else {
                    $_SESSION['flash_error'] = "Logo upload error: " . $logoRes['error'];
                }
            }

            // B. Favicon Upload (.ico, .png, .svg - max 512KB)
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                $favFile = $_FILES['favicon'];
                $ext = strtolower(pathinfo($favFile['name'], PATHINFO_EXTENSION));
                $allowedFav = ['ico', 'png', 'svg'];
                
                if (in_array($ext, $allowedFav) && $favFile['size'] <= 524288) {
                    $favDest = dirname(__DIR__) . '/assets/images/favicon.' . $ext;
                    if (move_uploaded_file($favFile['tmp_name'], $favDest)) {
                        setSetting($pdo, 'favicon_path', 'assets/images/favicon.' . $ext);
                    }
                } else {
                    $_SESSION['flash_error'] = "Invalid favicon format or file size exceeded 512KB.";
                }
            }

            // C. Apple Touch Icon
            if (isset($_FILES['apple_touch_icon']) && $_FILES['apple_touch_icon']['error'] === UPLOAD_ERR_OK) {
                $touchRes = uploadImage($_FILES['apple_touch_icon'], '../assets/images/', 1048576);
                if ($touchRes['success']) {
                    setSetting($pdo, 'apple_touch_icon_path', $touchRes['path']);
                }
            }

            // D. OpenGraph (OG) Social Card Image (1200x630 recommended)
            if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
                $ogRes = uploadImage($_FILES['og_image'], '../uploads/branding/', 3145728);
                if ($ogRes['success']) {
                    setSetting($pdo, 'og_image_path', $ogRes['path']);
                }
            }

            if (!isset($_SESSION['flash_error'])) {
                $_SESSION['flash_success'] = "Branding assets updated successfully!";
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Branding update error: " . $e->getMessage();
        }
        header("Location: settings.php#tab-branding");
        exit;
    }

    // TAB 3: ABOUT PAGE CONTENT & SITE STATS
    if ($tab === 'about') {
        try {
            // Text Settings
            setSetting($pdo, 'about_hero_heading', trim($_POST['about_hero_heading'] ?? ''));
            setSetting($pdo, 'about_hero_description', trim($_POST['about_hero_description'] ?? ''));
            setSetting($pdo, 'founding_year', trim($_POST['founding_year'] ?? '2014'));

            // Site Stats
            setStat($pdo, 'lives_healed', (string)(int)($_POST['stat_lives_healed'] ?? 30000));
            setStat($pdo, 'years_experience', (string)(int)($_POST['stat_years_experience'] ?? 25));
            setStat($pdo, 'course_levels', (string)(int)($_POST['stat_course_levels'] ?? 6));
            setStat($pdo, 'sessions_completed', (string)(int)($_POST['stat_sessions_completed'] ?? 25000));

            // 4 Core Values
            $coreValues = [];
            for ($i = 1; $i <= 4; $i++) {
                $coreValues[] = [
                    'title' => trim($_POST["core_val_{$i}_title"] ?? ''),
                    'description' => trim($_POST["core_val_{$i}_desc"] ?? '')
                ];
            }
            setSetting($pdo, 'core_values', json_encode($coreValues));

            $_SESSION['flash_success'] = "About page content & stats saved!";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Error saving About page settings: " . $e->getMessage();
        }
        header("Location: settings.php#tab-about");
        exit;
    }

    // TAB 4: HOMEPAGE CONTENT
    if ($tab === 'homepage') {
        try {
            setSetting($pdo, 'hero_heading', trim($_POST['hero_heading'] ?? ''));
            setSetting($pdo, 'hero_subtext', trim($_POST['hero_subtext'] ?? ''));
            setSetting($pdo, 'hero_cta1_text', trim($_POST['hero_cta1_text'] ?? ''));
            setSetting($pdo, 'hero_cta1_url', trim($_POST['hero_cta1_url'] ?? ''));
            setSetting($pdo, 'hero_cta2_text', trim($_POST['hero_cta2_text'] ?? ''));
            setSetting($pdo, 'hero_cta2_url', trim($_POST['hero_cta2_url'] ?? ''));
            setSetting($pdo, 'bracelets_heading', trim($_POST['bracelets_heading'] ?? ''));
            setSetting($pdo, 'bracelets_description', trim($_POST['bracelets_description'] ?? ''));

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
        <h2 style="font-size: 1.25rem; font-weight: 700; color: #ffffff;">
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
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'Shree Sai Reiki & Healing Center') ?>">
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
            <h3 class="admin-card-title mb-3" style="color: var(--gold);">Brand Imagery &amp; Social Assets</h3>
            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tab" value="branding">

                <!-- 1. Site Logo -->
                <div class="form-group mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 24px;">
                    <label class="form-label" style="font-size: 1rem; color: #ffffff; font-weight: 600;">Site Main Logo</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Displayed in main website navigation, admin sidebar, and footer.</p>
                    
                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <div style="background: rgba(18, 16, 31, 0.8); border: 1px solid var(--card-border); border-radius: 10px; padding: 12px 20px; display: inline-flex; align-items: center; justify-content: center; min-width: 140px; min-height: 70px;">
                            <img src="../assets/images/logo.png" alt="Current Logo" style="max-height: 50px; max-width: 160px; object-fit: contain;">
                        </div>
                        <div>
                            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                            <span class="form-hint">PNG or SVG with transparent background recommended (Max 2MB).</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Favicon -->
                <div class="form-group mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 24px;">
                    <label class="form-label" style="font-size: 1rem; color: #ffffff; font-weight: 600;">Favicon (Browser Tab Icon)</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Small icon shown in browser tabs and bookmarks bar (~32x32px).</p>
                    
                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <div style="background: rgba(18, 16, 31, 0.8); border: 1px solid var(--card-border); border-radius: 8px; width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center;">
                            <?php 
                                $favPath = $settings['favicon_path'] ?? 'assets/images/favicon-circle.png';
                            ?>
                            <img src="../<?= htmlspecialchars($favPath) ?>" alt="Favicon Preview" style="width: 32px; height: 32px; object-fit: contain;" onerror="this.src='../assets/images/logo.png'">
                        </div>
                        <div>
                            <input type="file" name="favicon" class="form-control" accept=".ico,image/png,image/svg+xml">
                            <span class="form-hint">Accepted formats: .ico, .png, .svg (Max 512KB).</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Apple Touch Icon -->
                <div class="form-group mb-4" style="border-bottom: 1px solid var(--card-border); padding-bottom: 24px;">
                    <label class="form-label" style="font-size: 1rem; color: #ffffff; font-weight: 600;">Apple Touch Icon</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Icon shown when users bookmark or save your website to their mobile home screen (180x180px PNG).</p>
                    
                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <div style="background: rgba(18, 16, 31, 0.8); border: 1px solid var(--card-border); border-radius: 12px; width: 60px; height: 60px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="../assets/images/favicon-circle.png" alt="Apple Touch Icon" style="width: 44px; height: 44px; object-fit: contain;">
                        </div>
                        <div>
                            <input type="file" name="apple_touch_icon" class="form-control" accept="image/png">
                            <span class="form-hint">Square 180x180 PNG recommended.</span>
                        </div>
                    </div>
                </div>

                <!-- 4. OpenGraph Social Share Card -->
                <div class="form-group mb-4">
                    <label class="form-label" style="font-size: 1rem; color: #ffffff; font-weight: 600;">OpenGraph Social Share Image (OG Image)</label>
                    <p class="text-muted mb-2" style="font-size: 0.84rem;">Preview image shown when links to your site are shared on WhatsApp, Facebook, or Twitter (1200x630px recommended).</p>
                    
                    <div class="flex items-center gap-3" style="flex-wrap: wrap;">
                        <?php 
                            $ogPath = $settings['og_image_path'] ?? 'assets/images/hero-bg.jpg';
                        ?>
                        <div style="background: rgba(18, 16, 31, 0.8); border: 1px solid var(--card-border); border-radius: 8px; width: 140px; height: 75px; overflow: hidden;">
                            <img src="../<?= htmlspecialchars($ogPath) ?>" alt="OG Preview" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div>
                            <input type="file" name="og_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <span class="form-hint">Dimensions: 1200 x 630 pixels (Max 3MB).</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gold">
                        Save Branding
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: ABOUT PAGE & STATS
         ===================================================================== -->
    <div class="tab-content" id="tab-about">
        <div class="admin-card">
            <h3 class="admin-card-title mb-3" style="color: var(--gold);">About Sanctuary &amp; Mission Content</h3>
            <form action="settings.php" method="POST">
                <input type="hidden" name="tab" value="about">

                <div class="form-group">
                    <label class="form-label">About Hero Headline</label>
                    <input type="text" name="about_hero_heading" class="form-control" value="<?= htmlspecialchars($settings['about_hero_heading'] ?? 'Healing with Heart & Purpose') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">About Hero Introduction Description</label>
                    <textarea name="about_hero_description" class="form-control" rows="3"><?= htmlspecialchars($settings['about_hero_description'] ?? 'Founded with the sacred intention of bringing authentic Usui Reiki to seekers everywhere, our sanctuary blends ancient spiritual healing with modern mindfulness practices.') ?></textarea>
                </div>

                <div class="form-group" style="max-width: 240px;">
                    <label class="form-label">Center Founding Year</label>
                    <input type="text" name="founding_year" class="form-control" value="<?= htmlspecialchars($settings['founding_year'] ?? '2014') ?>">
                </div>

                <!-- Numeric Site Stats -->
                <h4 style="font-size: 0.95rem; color: var(--gold); margin: 24px 0 14px; border-top: 1px solid var(--card-border); padding-top: 16px;">
                    Key Impact Counter Statistics (Site Stats)
                </h4>

                <div class="form-row" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="form-group">
                        <label class="form-label">Healed Clients</label>
                        <input type="number" name="stat_lives_healed" class="form-control" value="<?= htmlspecialchars($stats['lives_healed'] ?? '30000') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Years of Practice</label>
                        <input type="number" name="stat_years_experience" class="form-control" value="<?= htmlspecialchars($stats['years_experience'] ?? '25') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Course Levels</label>
                        <input type="number" name="stat_course_levels" class="form-control" value="<?= htmlspecialchars($stats['course_levels'] ?? '6') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sessions Completed</label>
                        <input type="number" name="stat_sessions_completed" class="form-control" value="<?= htmlspecialchars($stats['sessions_completed'] ?? '25000') ?>">
                    </div>
                </div>

                <!-- 4 Core Values -->
                <h4 style="font-size: 0.95rem; color: var(--gold); margin: 24px 0 14px; border-top: 1px solid var(--card-border); padding-top: 16px;">
                    Four Pillars / Core Values
                </h4>

                <?php 
                    $defaultValues = [
                        ['title' => 'Compassionate Presence', 'description' => 'Every session is held with deep unconditional empathy, confidentiality, and spiritual grounding.'],
                        ['title' => 'Authentic Lineage', 'description' => 'Direct Usui Reiki tradition handed down through accredited grandmasters with authentic attunement.'],
                        ['title' => 'Holistic Transformation', 'description' => 'Addressing subtle energetic root causes rather than just superficial physical symptoms.'],
                        ['title' => 'Empowered Self-Healing', 'description' => 'Guiding every student and healee with knowledge to sustain their own energetic balance.']
                    ];
                    $vals = !empty($coreValues) ? $coreValues : $defaultValues;
                ?>

                <div class="form-row">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="form-group" style="background: rgba(30, 21, 69, 0.4); border: 1px solid var(--card-border); border-radius: 10px; padding: 14px;">
                            <label class="form-label" style="color: var(--gold); font-weight: 600;">Pillar #<?= $i + 1 ?> Title</label>
                            <input type="text" name="core_val_<?= $i + 1 ?>_title" class="form-control mb-2" value="<?= htmlspecialchars($vals[$i]['title'] ?? '') ?>">
                            
                            <label class="form-label" style="font-size: 0.78rem;">Description</label>
                            <textarea name="core_val_<?= $i + 1 ?>_desc" class="form-control" rows="2"><?= htmlspecialchars($vals[$i]['description'] ?? '') ?></textarea>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-gold">
                        Save About Page
                    </button>
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

                <div class="form-row">
                    <!-- CTA Button 1 -->
                    <div class="form-group" style="background: rgba(30, 21, 69, 0.4); border: 1px solid var(--card-border); border-radius: 10px; padding: 14px;">
                        <label class="form-label" style="color: var(--gold); font-weight: 600;">Call-to-Action 1 (Primary)</label>
                        <input type="text" name="hero_cta1_text" class="form-control mb-2" placeholder="Button Label e.g. Explore Services" value="<?= htmlspecialchars($settings['hero_cta1_text'] ?? 'Explore Healing Services') ?>">
                        <input type="text" name="hero_cta1_url" class="form-control" placeholder="Target Link e.g. services.php" value="<?= htmlspecialchars($settings['hero_cta1_url'] ?? 'services.php') ?>">
                    </div>

                    <!-- CTA Button 2 -->
                    <div class="form-group" style="background: rgba(30, 21, 69, 0.4); border: 1px solid var(--card-border); border-radius: 10px; padding: 14px;">
                        <label class="form-label" style="color: var(--gold); font-weight: 600;">Call-to-Action 2 (Secondary)</label>
                        <input type="text" name="hero_cta2_text" class="form-control mb-2" placeholder="Button Label e.g. Custom Bracelet" value="<?= htmlspecialchars($settings['hero_cta2_text'] ?? 'Order Custom Bracelet') ?>">
                        <input type="text" name="hero_cta2_url" class="form-control" placeholder="Target Link e.g. order-bracelet.php" value="<?= htmlspecialchars($settings['hero_cta2_url'] ?? 'order-bracelet.php') ?>">
                    </div>
                </div>

                <h4 style="font-size: 0.95rem; color: var(--gold); margin: 24px 0 14px; border-top: 1px solid var(--card-border); padding-top: 16px;">
                    Custom Bracelets Showcase Section
                </h4>

                <div class="form-group">
                    <label class="form-label">Bracelets Section Heading</label>
                    <input type="text" name="bracelets_heading" class="form-control" value="<?= htmlspecialchars($settings['bracelets_heading'] ?? 'Energized Astrological & Custom Crystal Bracelets') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Bracelets Section Subtitle / Description</label>
                    <textarea name="bracelets_description" class="form-control" rows="2"><?= htmlspecialchars($settings['bracelets_description'] ?? 'Tailored specifically according to your date and place of birth or personalized healing intentions, charged with high-frequency Reiki symbols.') ?></textarea>
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

// Load Active Tab from URL Hash
window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#tab-', '');
    if (hash && document.getElementById('tab-' + hash)) {
        activateTab(hash);
    }
});

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
