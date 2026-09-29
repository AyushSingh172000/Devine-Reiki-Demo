<?php
$lines = file('admin/settings.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'homepage') !== false || stripos($line, 'hero_badge') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
