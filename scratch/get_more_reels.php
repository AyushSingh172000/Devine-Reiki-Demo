<?php
$ch = curl_init('https://www.instagram.com/reiki_bliss/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
$html = curl_exec($ch);
curl_close($ch);

preg_match_all('#/(?:reel|p)/([A-Za-z0-9_-]{10,12})/#', $html, $m);
$codes = array_unique($m[1]);
echo "Found " . count($codes) . " codes:\n";
print_r($codes);
