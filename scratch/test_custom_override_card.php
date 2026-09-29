<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== TESTING CUSTOM OVERRIDES ON CARDS ===\n";

$pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?")->execute(['Custom Center Branch Address', 'contact_card1_text']);

$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$siteSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}

ob_start();
include __DIR__ . '/../contact.php';
$html = ob_get_clean();

if (strpos($html, 'Custom Center Branch Address') !== false) {
    echo "[PASS] Custom card text overrides global address successfully!\n";
} else {
    echo "[FAIL] Custom card text did not override!\n";
}

// Reset back to blank so global details are inherited as user wants
$pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?")->execute(['', 'contact_card1_text']);
echo "Reset back to blank successfully.\n";
