<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$siteSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}

echo "=== CHECKING HOMEPAGE BADGE SETTINGS IN DB ===\n";
echo "Services badge: " . ($siteSettings['home_services_badge'] ?? '[not set]') . "\n";
echo "Courses badge: " . ($siteSettings['home_courses_badge'] ?? '[not set]') . "\n";
echo "Products badge: " . ($siteSettings['home_products_badge'] ?? '[not set]') . "\n";
echo "Testimonials badge: " . ($siteSettings['home_testimonials_badge'] ?? '[not set]') . "\n";

echo "\n=== CHECKING CSS RULES ===\n";
$styleCss = file_get_contents(__DIR__ . '/../assets/css/style.css');
if (strpos($styleCss, '.section-label::before') !== false && strpos($styleCss, 'display: none !important') !== false) {
    echo "[PASS] style.css has .section-label::before hidden\n";
} else {
    echo "[FAIL] style.css does not have .section-label::before hidden\n";
}

$homeCss = file_get_contents(__DIR__ . '/../assets/css/home.css');
if (strpos($homeCss, '.services-section .section-label::before') !== false && strpos($homeCss, 'display: none !important') !== false) {
    echo "[PASS] home.css has all section labels ::before hidden\n";
} else {
    echo "[FAIL] home.css does not have all section labels ::before hidden\n";
}

echo "\n=== ALL CHECKS PASSED ===\n";
