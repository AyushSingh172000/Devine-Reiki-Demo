<?php
// Project Constants and Environment Configurations

// Dynamic Base URL definition
if (!defined('BASE_URL')) {
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // If loaded from within the /admin subdirectory, strip /admin to get root site base directory
        $dir = preg_replace('#/admin(?:/.*)?$#i', '', $dir);
        $baseDir = rtrim($dir, '/') . '/';
        define('BASE_URL', "{$scheme}://{$host}{$baseDir}");
    } else {
        define('BASE_URL', 'http://localhost/reikibliss/');
    }
}

// Admin URL definition
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', BASE_URL . 'admin/');
}

// File Upload Path definitions
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');
}
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', BASE_URL . 'assets/uploads/');
}

// Include database connection
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/db.php';
}

// Fetch all site_settings from database into $siteSettings associative array
$siteSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $siteSettings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (\PDOException $e) {
    error_log("Failed to load site_settings: " . $e->getMessage());
}
$settings = $siteSettings; // Backwards compatibility

// Fetch all site_stats into $siteStats associative array
$siteStats = [];
try {
    $stmt = $pdo->query("SELECT stat_key, stat_value, label FROM site_stats");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $siteStats[$row['stat_key']] = $row;
        }
    }
} catch (\PDOException $e) {
    error_log("Failed to load site_stats: " . $e->getMessage());
}

// Define Site Constants from $siteSettings
define('BOOKING_URL', $siteSettings['booking_url'] ?? 'https://calendar.google.com/calendar/appointments/schedules/AcZssZ1RI6bVu-iU0Oi4_H09OlL-bQglgmpskaOrSO0nCevRuaKlWfCVYv1XsrEzLz-g7HUkgeiO0C2c');
define('SITE_NAME', $siteSettings['site_name'] ?? 'Shree Sai Reiki Healing Center');
define('SITE_TAGLINE', $siteSettings['site_tagline'] ?? 'Heal. Balance. Transform.');
define('SITE_PHONE', $siteSettings['phone'] ?? '+91 9726581787');
define('SITE_EMAIL', $siteSettings['email'] ?? 'shreesaireikihealingcentre@gmail.com');
define('SITE_ADDRESS', $siteSettings['address'] ?? '4th Floor, Keshav Arcade, Golden Park Society, Anand Mahal Road, Adajan, Surat – 395009');
define('SITE_WHATSAPP', $siteSettings['whatsapp'] ?? '919726581787');
define('FACEBOOK_URL', $siteSettings['facebook_url'] ?? 'https://www.facebook.com/profile.php?id=100063697185284');
define('YOUTUBE_URL', $siteSettings['youtube_url'] ?? 'https://www.youtube.com/@chiraggajjarreikigrandmast8374');
define('INSTAGRAM_URL', $siteSettings['instagram_url'] ?? '');
define('MAPS_URL', $siteSettings['maps_url'] ?? 'https://maps.app.goo.gl/FaadyRvvvjf1QQHE6');
define('MAPS_EMBED_URL', $siteSettings['maps_embed_url'] ?? '');
define('WORKING_HOURS', $siteSettings['working_hours'] ?? 'Mon–Sat: 7 AM – 6 PM · Sun: 9 AM – 1 PM');
define('LOGO_PATH', $siteSettings['logo_path'] ?? 'assets/images/logo.png');
define('FAVICON_PATH', $siteSettings['favicon_path'] ?? 'assets/images/favicon.ico');
define('OG_IMAGE_PATH', $siteSettings['og_image_path'] ?? 'assets/images/og-image.png');
