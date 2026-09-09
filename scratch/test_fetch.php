<?php
$ch = curl_init('https://www.instagram.com/reel/C7nY4MESYo7/embed/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
$h = curl_exec($ch);
file_put_contents(__DIR__ . '/embed_sample.html', $h);
echo "Saved " . strlen($h) . " bytes\n";
