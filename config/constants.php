<?php
// Project Constants and Environment Configurations

// Dynamic Base URL definition
if (!defined('BASE_URL')) {
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']), 'https') !== false)
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
            || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on')
            || (isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false);

        // On live production servers (non-localhost), default to HTTPS to avoid mixed content & 301 POST drops
        if (!$isHttps && isset($_SERVER['HTTP_HOST'])) {
            $isLocal = preg_match('/^(localhost|127\.0\.0\.1|::1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1]))/i', $_SERVER['HTTP_HOST']);
            if (!$isLocal) {
                $isHttps = true;
            }
        }

        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // If loaded from within the /admin or /superadmin subdirectory, strip it to get root site base directory
        $dir = preg_replace('#/(?:admin|superadmin)(?:/.*)?$#i', '', $dir);
        $baseDir = rtrim($dir, '/') . '/';
        define('BASE_URL', "{$scheme}://{$host}{$baseDir}");
    } else {
        define('BASE_URL', 'http://localhost/reiki_bliss/');
    }
}

// Admin URL definition
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', BASE_URL . 'admin/');
}

// Super Admin URL definition
if (!defined('SUPERADMIN_URL')) {
    define('SUPERADMIN_URL', BASE_URL . 'superadmin/');
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
define('BOOKING_URL', $siteSettings['booking_url'] ?? '');
define('SITE_NAME', $siteSettings['site_name'] ?? 'Reiki Bliss');
define('SITE_TAGLINE', $siteSettings['site_tagline'] ?? 'Heal. Balance. Transform.');
define('SITE_PHONE', $siteSettings['phone'] ?? '+91 99716 55705');
define('SITE_EMAIL', $siteSettings['email'] ?? 'anupama.snj@gmail.com');
define('SITE_ADDRESS', $siteSettings['address'] ?? 'Office No. 305 Building Kusal bazar, 32-33, Nehru Place, New Delhi - 110019');
define('SITE_WHATSAPP', $siteSettings['whatsapp'] ?? '919971655705');
define('FACEBOOK_URL', $siteSettings['facebook_url'] ?? 'https://www.facebook.com/profile.php?id=100063697185284');
define('YOUTUBE_URL', $siteSettings['youtube_url'] ?? 'https://www.youtube.com/@chiraggajjarreikigrandmast8374');
define('INSTAGRAM_URL', $siteSettings['instagram_url'] ?? '');
// Helper to convert any Google Maps URL or place link to an embeddable URL
if (!function_exists('convertGoogleMapsToEmbedUrl')) {
    function convertGoogleMapsToEmbedUrl($url, $address = '') {
        $url = trim((string)$url);
        $address = trim((string)$address);
        if (empty($url) && empty($address)) {
            return '';
        }
        // If full iframe code was pasted, extract src
        if (preg_match('/<iframe\s+[^>]*src="([^"]+)"/i', $url, $m)) {
            return $m[1];
        }
        // Already an embed URL
        if (strpos($url, '/maps/embed') !== false || strpos($url, 'output=embed') !== false) {
            return $url;
        }
        // Extract exact pin coordinates !3d(lat)!4d(lng)
        if (preg_match('/!3d([0-9.-]+)!4d([0-9.-]+)/', $url, $m)) {
            return "https://maps.google.com/maps?q=" . $m[1] . "," . $m[2] . "&hl=en&z=17&output=embed";
        }
        // Extract center coordinates @lat,lng
        if (preg_match('/@([0-9.-]+),([0-9.-]+)/', $url, $m)) {
            return "https://maps.google.com/maps?q=" . $m[1] . "," . $m[2] . "&hl=en&z=17&output=embed";
        }
        // Extract place query
        if (preg_match('#/maps/place/([^/@?]+)#', $url, $m)) {
            return "https://maps.google.com/maps?q=" . urlencode(urldecode($m[1])) . "&hl=en&z=16&output=embed";
        }
        // Extract query parameter q=
        if (preg_match('/[?&]q=([^&]+)/', $url, $m)) {
            return "https://maps.google.com/maps?q=" . $m[1] . "&hl=en&z=16&output=embed";
        }
        // Fallback to address
        if (!empty($address)) {
            return "https://maps.google.com/maps?q=" . urlencode($address) . "&hl=en&z=16&output=embed";
        }
        return $url;
    }
}

define('MAPS_URL', $siteSettings['maps_url'] ?? '');
$resolvedEmbed = !empty($siteSettings['maps_url']) 
    ? convertGoogleMapsToEmbedUrl($siteSettings['maps_url'], $siteSettings['address'] ?? '') 
    : (!empty($siteSettings['maps_embed_url']) ? $siteSettings['maps_embed_url'] : convertGoogleMapsToEmbedUrl('', $siteSettings['address'] ?? ''));
define('MAPS_EMBED_URL', $resolvedEmbed);
define('WORKING_HOURS', $siteSettings['working_hours'] ?? 'Mon–Sat: 7 AM – 6 PM · Sun: 9 AM – 1 PM');
define('LOGO_PATH', $siteSettings['logo_path'] ?? 'assets/images/logo.png');
define('FAVICON_PATH', $siteSettings['favicon_path'] ?? 'assets/images/favicon.ico');
define('OG_IMAGE_PATH', $siteSettings['og_image_path'] ?? 'assets/images/og-image.png');
