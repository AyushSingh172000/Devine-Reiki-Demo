<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'contact_hero_%'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['setting_key'] . " => " . json_encode($r['setting_value']) . "\n";
}
