<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== TESTING CONTACT HERO SUBTEXT REMOVAL ===\n";

function setVal($pdo, $k, $v) {
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$k, $v]);
}

// 1. Set subtext to empty string (as user did)
setVal($pdo, 'contact_hero_subtext', '');
setVal($pdo, 'contact_hero_desc', '');

$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$siteSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}

ob_start();
include __DIR__ . '/../contact.php';
$htmlEmpty = ob_get_clean();

if (strpos($htmlEmpty, 'class="contact-hero-subtext"') === false) {
    echo "[PASS] When contact_hero_subtext is empty, paragraph is completely removed from contact page!\n";
} else {
    echo "[FAIL] When contact_hero_subtext is empty, paragraph was still found in contact page!\n";
}

// 2. Set subtext to a custom value
$customMsg = "This is a custom consultation subtext for testing.";
setVal($pdo, 'contact_hero_subtext', $customMsg);
setVal($pdo, 'contact_hero_desc', $customMsg);

$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$siteSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}

ob_start();
include __DIR__ . '/../contact.php';
$htmlCustom = ob_get_clean();

if (strpos($htmlCustom, $customMsg) !== false && strpos($htmlCustom, 'class="contact-hero-subtext"') !== false) {
    echo "[PASS] When contact_hero_subtext has text, it appears properly on contact page!\n";
} else {
    echo "[FAIL] Custom subtext not found!\n";
}

// 3. Reset back to empty (as requested by user in their screenshot)
setVal($pdo, 'contact_hero_subtext', '');
setVal($pdo, 'contact_hero_desc', '');

echo "=== TEST COMPLETED ===\n";
