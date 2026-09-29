<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$siteSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}

echo "=== VERIFYING RENDERED HTML FOR ALL SECTIONS ===\n\n";

$sections = [
    'services' => 'superadmin/sections/services.php',
    'courses' => 'superadmin/sections/courses.php',
    'products' => 'superadmin/sections/products.php',
    'testimonials' => 'superadmin/sections/testimonials.php'
];

foreach ($sections as $name => $path) {
    ob_start();
    include __DIR__ . '/../' . $path;
    $out = ob_get_clean();

    echo "--- Section: $name ---\n";
    if (preg_match('/<span class="section-label">(.*?)<\/span>/s', $out, $m)) {
        echo "Badge: " . trim($m[1]) . "\n";
    }
    if (preg_match('/<h2 class="section-heading">(.*?)<\/h2>/s', $out, $m)) {
        echo "Title: " . trim($m[1]) . "\n";
    }
    if (preg_match('/<p[^>]*>(.*?)<\/p>/s', $out, $m)) {
        echo "Desc: " . trim($m[1]) . "\n";
    }
    echo "\n";
}
