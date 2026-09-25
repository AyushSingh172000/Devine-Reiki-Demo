<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login');
    exit;
}

if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}
