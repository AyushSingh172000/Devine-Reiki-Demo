<?php
$h = file_get_contents(__DIR__ . '/embed_sample.html');
preg_match_all('#https://[^\s"\'<>]+\.(?:jpg|jpeg|png|webp)[^\s"\'<>]*#i', $h, $matches);
$urls = array_unique($matches[0]);
foreach (array_slice($urls, 0, 15) as $u) {
    echo $u . "\n";
}
