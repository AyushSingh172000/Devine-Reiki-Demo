<?php
$pdo = require __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE '%og%' OR setting_key LIKE '%image%'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['setting_key'] . ' => ' . $r['setting_value'] . PHP_EOL;
}
