<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'superadmin_homepage_layout'");
$stmt->execute();
$val = $stmt->fetchColumn();
echo "superadmin_homepage_layout:\n" . json_encode(json_decode($val, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
