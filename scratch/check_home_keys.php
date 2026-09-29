<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE '%badge%' OR setting_key LIKE 'hero_%' ORDER BY setting_key");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['setting_key'] . " => " . $row['setting_value'] . "\n";
}
