<?php
/**
 * Branding & Identity Shortcut
 * Reiki Bliss Admin Panel
 */
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/auth-check.php';

header('Location: settings.php?tab=branding');
exit;
