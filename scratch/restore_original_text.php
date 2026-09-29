<?php
require_once __DIR__ . '/../config/db.php';

echo "=== RESTORING ORIGINAL PREVIOUS TEXT ===\n";

$originalTexts = [
    'services' => [
        'badge' => 'Holistic Healing Modalities',
        'title' => 'Our Core <em>Services</em>',
        'desc'  => 'Experience personalized Reiki healing, chakra alignment, and aura cleansing guided by Grandmaster Anupama Agrawal to restore physical vitality and spiritual harmony.'
    ],
    'courses' => [
        'badge' => 'Certified Energy Training',
        'title' => 'Explore Reiki &amp; Healing <em>Courses</em>',
        'desc'  => 'Become a certified Reiki healer yourself. Structured curriculum with authentic attunement (Diksha), physical manual, lifetime mentorship, and recognized certificates.'
    ],
    'products' => [
        'badge' => 'Sacred Crystal Energy',
        'title' => 'Featured Reiki Charged <em>Products</em>',
        'desc'  => 'Energized astrological bracelets, natural healing crystals, and sacred gemstone artifacts charged with high-frequency Reiki symbols to amplify protection, prosperity, and peace.'
    ],
    'testimonials' => [
        'badge' => 'STORIES OF HEALING',
        'title' => 'What Our Students &amp; Clients <em>Say</em>',
        'desc'  => 'Read real life experiences from individuals who restored harmony, vitality, and peace through our Reiki sessions.'
    ]
];

$setStmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

foreach ($originalTexts as $secKey => $vals) {
    $setStmt->execute(["home_{$secKey}_badge", $vals['badge']]);
    $setStmt->execute(["home_{$secKey}_title", $vals['title']]);
    $setStmt->execute(["home_{$secKey}_desc",  $vals['desc']]);
    echo "Restored home_{$secKey}_* in site_settings\n";
}

// Also restore superadmin_homepage_layout JSON with exact original values
$stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'superadmin_homepage_layout'");
$layoutJson = $stmt->fetchColumn();
$layoutArr = json_decode($layoutJson, true) ?: [];

foreach ($layoutArr as &$item) {
    $sId = $item['id'] ?? '';
    if (isset($originalTexts[$sId])) {
        $item['badge'] = $originalTexts[$sId]['badge'];
        $item['title'] = $originalTexts[$sId]['title'];
        $item['desc']  = $originalTexts[$sId]['desc'];
    }
}
unset($item);

$setStmt->execute(['superadmin_homepage_layout', json_encode($layoutArr, JSON_UNESCAPED_UNICODE)]);
echo "Restored superadmin_homepage_layout JSON\n";

echo "=== RESTORATION COMPLETE ===\n";
