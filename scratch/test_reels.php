<?php
$ids = ['C7nY4MESYo7', 'Dcv-vhPJLAS', 'DXF7BlGpoRs', 'C6IZ9FcMKV0', 'C7gIJshyc6G', 'C4xHuoQyzvp'];
foreach ($ids as $id) {
    $url = "https://www.instagram.com/reel/{$id}/embed/";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);

    // Look for image/poster/thumbnail
    preg_match('/<img[^>]+class="[^"]*EmbeddedMediaImage[^"]*"[^>]+src="([^">]+)"/i', $html, $m1);
    preg_match('/class="EmbedVideo"[^>]*poster="([^">]+)"/i', $html, $m2);
    preg_match('/<img[^>]+src="([^">]+)"/i', $html, $m3);
    preg_match('/"display_url":"([^"]+)"/i', $html, $m4);
    preg_match('/"thumbnail_url":"([^"]+)"/i', $html, $m5);

    echo "--- ID: $id ---\n";
    if (!empty($m1[1])) echo "EmbeddedMediaImage: " . html_entity_decode($m1[1]) . "\n";
    if (!empty($m2[1])) echo "EmbedVideo poster: " . html_entity_decode($m2[1]) . "\n";
    if (!empty($m4[1])) echo "display_url: " . stripcslashes($m4[1]) . "\n";
    if (!empty($m5[1])) echo "thumbnail_url: " . stripcslashes($m5[1]) . "\n";
    
    // Also check title / caption from embed
    preg_match('/<div class="Caption"[^>]*>(.*?)<\/div>/is', $html, $mCap);
    if (!empty($mCap[1])) echo "Caption: " . trim(strip_tags($mCap[1])) . "\n";
}
