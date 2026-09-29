<?php
require_once __DIR__ . '/../config/db.php';

// Get current DB settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key");
$currentDb = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentDb[$r['setting_key']] = $r['setting_value'];
}

// Get 5c55cdd database.sql
$oldSql = shell_exec("git show 5c55cdd:database.sql");
preg_match_all("/\('(\d+)',\s*'([^']+)',\s*'((?:[^'\\\\]|\\\\.)*)'\)/", $oldSql, $matches, PREG_SET_ORDER);

$oldDb = [];
foreach ($matches as $m) {
    // Unescape SQL string
    $val = stripcslashes($m[3]);
    $oldDb[$m[2]] = $val;
}

echo "=== KEYS CHANGED OR ADDED IN SITE_SETTINGS ===\n";
foreach ($currentDb as $k => $v) {
    if (!isset($oldDb[$k])) {
        echo "ADDED: $k => $v\n";
    } elseif ($oldDb[$k] !== $v) {
        echo "MODIFIED: $k\n";
        echo "  OLD: " . $oldDb[$k] . "\n";
        echo "  NEW: " . $v . "\n";
    }
}
