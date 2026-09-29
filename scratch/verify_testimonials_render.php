<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$siteSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}

echo "Database home_testimonials_badge: " . ($siteSettings['home_testimonials_badge'] ?? '[not set]') . "\n";

ob_start();
include __DIR__ . '/../superadmin/sections/testimonials.php';
$output = ob_get_clean();

echo "Rendered Testimonials Output:\n";
preg_match('/<div class="testimonials-header-center".*?<\/div>/s', $output, $m);
echo ($m[0] ?? 'Header center not found') . "\n";
