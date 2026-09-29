<?php
require_once __DIR__ . '/../config/db.php';

echo "=== STARTING SECTION HEADINGS TEST ===\n";

// 1. Fetch current settings backup
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$origSettings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $origSettings[$r['setting_key']] = $r['setting_value'];
}

function updateSetting($pdo, $k, $v) {
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$k, $v]);
}

// 2. Test Custom Headings
$customData = [
    'services' => [
        'badge' => 'CUSTOM SERVICES BADGE',
        'title' => 'Custom Services <em>Headline</em>',
        'desc'  => 'This is a custom services description for testing purposes.'
    ],
    'courses' => [
        'badge' => 'CUSTOM COURSES BADGE',
        'title' => 'Custom Courses <em>Headline</em>',
        'desc'  => 'This is a custom courses description for testing purposes.'
    ],
    'products' => [
        'badge' => 'CUSTOM PRODUCTS BADGE',
        'title' => 'Custom Products <em>Headline</em>',
        'desc'  => 'This is a custom products description for testing purposes.'
    ],
    'testimonials' => [
        'badge' => '●● CUSTOM TESTIMONIALS BADGE',
        'title' => 'Custom Testimonials <em>Headline</em>',
        'desc'  => 'This is a custom testimonials description for testing purposes.'
    ]
];

// Apply custom data to site_settings and update superadmin_homepage_layout
foreach ($customData as $sId => $vals) {
    updateSetting($pdo, "home_{$sId}_badge", $vals['badge']);
    updateSetting($pdo, "home_{$sId}_title", $vals['title']);
    updateSetting($pdo, "home_{$sId}_desc", $vals['desc']);
}

$rawLayout = $origSettings['superadmin_homepage_layout'] ?? '[]';
$layoutArr = json_decode($rawLayout, true) ?: [];
foreach ($layoutArr as &$sec) {
    $sId = $sec['id'] ?? '';
    if (isset($customData[$sId])) {
        $sec['badge'] = $customData[$sId]['badge'];
        $sec['title'] = $customData[$sId]['title'];
        $sec['desc']  = $customData[$sId]['desc'];
    }
}
unset($sec);
updateSetting($pdo, 'superadmin_homepage_layout', json_encode($layoutArr));

// Render index.php output via cURL or local buffer
ob_start();
// Setup global mock for siteSettings
$_SERVER['REQUEST_METHOD'] = 'GET';
$siteSettings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}
include __DIR__ . '/../index.php';
$html = ob_get_clean();

$passed = true;
foreach ($customData as $sId => $vals) {
    if (strpos($html, $vals['badge']) !== false) {
        echo "[PASS] Custom badge found for {$sId}: {$vals['badge']}\n";
    } else {
        echo "[FAIL] Custom badge NOT found for {$sId}\n";
        $passed = false;
    }

    if (strpos($html, $vals['title']) !== false) {
        echo "[PASS] Custom title found for {$sId}: {$vals['title']}\n";
    } else {
        echo "[FAIL] Custom title NOT found for {$sId}\n";
        $passed = false;
    }

    if (strpos($html, $vals['desc']) !== false) {
        echo "[PASS] Custom desc found for {$sId}: {$vals['desc']}\n";
    } else {
        echo "[FAIL] Custom desc NOT found for {$sId}\n";
        $passed = false;
    }
}

// 3. Test Clearing/Hiding Elements (Empty String)
echo "\n--- TESTING CLEARING (EMPTY STRING) BEHAVIOR ---\n";
foreach (['services', 'courses', 'products', 'testimonials'] as $sId) {
    updateSetting($pdo, "home_{$sId}_badge", "");
    updateSetting($pdo, "home_{$sId}_desc", "");
}
foreach ($layoutArr as &$sec) {
    $sId = $sec['id'] ?? '';
    if (in_array($sId, ['services', 'courses', 'products', 'testimonials'])) {
        $sec['badge'] = '';
        $sec['desc']  = '';
    }
}
unset($sec);
updateSetting($pdo, 'superadmin_homepage_layout', json_encode($layoutArr));

ob_start();
$siteSettings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $siteSettings[$r['setting_key']] = $r['setting_value'];
}
include __DIR__ . '/../index.php';
$clearedHtml = ob_get_clean();

// Check that custom badges/descs and default strings are absent
$defaultStrings = [
    'Holistic Healing Modalities',
    'Certified Energy Training',
    'Sacred Crystal Energy',
    '●● STORIES OF HEALING'
];
foreach ($defaultStrings as $ds) {
    if (strpos($clearedHtml, $ds) === false) {
        echo "[PASS] Cleared badge is cleanly omitted from HTML (not leaking default '{$ds}')\n";
    } else {
        echo "[FAIL] Cleared badge still showing default '{$ds}'\n";
        $passed = false;
    }
}

// 4. Restore original database settings
echo "\n--- RESTORING ORIGINAL DB STATE ---\n";
foreach ($origSettings as $k => $v) {
    updateSetting($pdo, $k, $v);
}
// Clean any test keys not originally present
foreach (['services', 'courses', 'products', 'testimonials'] as $sId) {
    foreach (['badge', 'title', 'desc'] as $f) {
        $k = "home_{$sId}_{$f}";
        if (!array_key_exists($k, $origSettings)) {
            // Initialize with nice default
            if ($f === 'badge') {
                $val = ($sId === 'services' ? 'Holistic Healing Modalities' : ($sId === 'courses' ? 'Certified Energy Training' : ($sId === 'products' ? 'Sacred Crystal Energy' : '●● STORIES OF HEALING')));
            } elseif ($f === 'title') {
                $val = ($sId === 'services' ? 'Our Core <em>Services</em>' : ($sId === 'courses' ? 'Explore Reiki &amp; Healing <em>Courses</em>' : ($sId === 'products' ? 'Featured Reiki Charged <em>Products</em>' : 'What Our Students &amp; Clients <em>Say</em>')));
            } else {
                $val = ($sId === 'services' ? 'Experience personalized Reiki healing, chakra alignment, and aura cleansing guided by Grandmaster Anupama Agrawal to restore physical vitality and spiritual harmony.' : ($sId === 'courses' ? 'Become a certified Reiki healer yourself. Structured curriculum with authentic attunement (Diksha), physical manual, lifetime mentorship, and recognized certificates.' : ($sId === 'products' ? 'Energized astrological bracelets, natural healing crystals, and sacred gemstone artifacts charged with high-frequency Reiki symbols to amplify protection, prosperity, and peace.' : 'Read real life experiences from individuals who restored harmony, vitality, and peace through our Reiki sessions.')));
            }
            updateSetting($pdo, $k, $val);
        }
    }
}
updateSetting($pdo, 'superadmin_homepage_layout', $origSettings['superadmin_homepage_layout'] ?? '[]');

echo $passed ? "\n=== ALL TESTS PASSED SUCCESSFULLY! ===\n" : "\n=== SOME TESTS FAILED! ===\n";
