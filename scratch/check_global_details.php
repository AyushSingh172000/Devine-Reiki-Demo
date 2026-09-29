<?php
require_once __DIR__ . '/../config/db.php';
$keys = ['site_name', 'address', 'phone', 'whatsapp', 'email', 'working_hours', 'maps_url', 'booking_url'];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['setting_key'] . " => " . json_encode($r['setting_value']) . "\n";
}
