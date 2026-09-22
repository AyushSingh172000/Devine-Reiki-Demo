<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';
header("Location: settings.php?tab=footer");
exit;
