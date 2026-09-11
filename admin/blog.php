<?php
define('ADMIN_ACCESS', true);
require_once 'auth-check.php';

// Blog section removed - redirect to dashboard
header('Location: index.php');
exit;
