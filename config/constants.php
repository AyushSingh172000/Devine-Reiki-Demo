<?php
// Project Constants and Environment Configurations

// Base URL definition
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/DemoWebsite/');
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

// Fetch all site_settings from database into $settings associative array
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (\PDOException $e) {
    error_log("Failed to load site_settings: " . $e->getMessage());
}

// Define Site Constants from $settings
define('SITE_NAME', $settings['site_name'] ?? 'Divine Reiki & Energy Healing Center');
define('SITE_PHONE', $settings['phone'] ?? '');
define('SITE_EMAIL', $settings['email'] ?? '');
define('SITE_ADDRESS', $settings['address'] ?? '');
define('SITE_WHATSAPP', $settings['whatsapp'] ?? '');
define('FACEBOOK_URL', $settings['facebook_url'] ?? '');
define('YOUTUBE_URL', $settings['youtube_url'] ?? '');
define('MAPS_URL', $settings['maps_url'] ?? '');
define('WORKING_HOURS', $settings['working_hours'] ?? '');
