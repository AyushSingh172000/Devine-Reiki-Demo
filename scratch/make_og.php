<?php
$hero = __DIR__ . '/../assets/images/hero-bg.jpg';
$targetPng = __DIR__ . '/../assets/images/og-image.png';
$targetJpg = __DIR__ . '/../assets/images/og-image.jpg';

if (file_exists($hero)) {
    copy($hero, $targetPng);
    copy($hero, $targetJpg);
    echo "Copied hero-bg.jpg to og-image.png and og-image.jpg successfully!" . PHP_EOL;
} else {
    echo "hero-bg.jpg not found." . PHP_EOL;
}
