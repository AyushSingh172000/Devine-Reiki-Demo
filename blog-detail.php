<?php
// Blog removed - redirect permanently to home
require_once __DIR__ . '/config/constants.php';
header("Location: " . BASE_URL, true, 301);
exit;
