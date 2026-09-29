<?php
$pdo = require __DIR__ . '/../config/db.php';

$tables = [
    'admin_users', 'services', 'courses', 'products', 'team_members', 
    'testimonials', 'gallery_images', 'blog_posts', 'contact_inquiries', 
    'bracelet_orders', 'site_stats', 'site_settings', 'login_attempts',
    'chat_sessions', 'chat_messages'
];

$output = "-- Reiki Bliss Database Export for Hostinger & Localhost Deployment\n";
$output .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetchColumn();
    if (!$check) continue;

    $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    $output .= $createStmt['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $cols = array_keys($rows[0]);
        $colNames = implode('`, `', $cols);
        $output .= "INSERT INTO `$table` (`$colNames`) VALUES\n";
        $valLines = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $vals[] = "NULL";
                } else {
                    $vals[] = $pdo->quote($val);
                }
            }
            $valLines[] = "(" . implode(', ', $vals) . ")";
        }
        $output .= implode(",\n", $valLines) . ";\n\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/../database.sql', $output);
echo "Successfully updated database.sql (" . strlen($output) . " bytes)\n";
