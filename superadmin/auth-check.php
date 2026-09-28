<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// Verify Super Admin Session
if (!isset($_SESSION['superadmin_logged_in']) || $_SESSION['superadmin_logged_in'] !== true) {
    $redirectUrl = defined('SUPERADMIN_URL') ? SUPERADMIN_URL . 'login' : 'login.php';
    header("Location: {$redirectUrl}");
    exit;
}

if (!defined('SUPERADMIN_ACCESS')) {
    define('SUPERADMIN_ACCESS', true);
}

// Fetch current logged-in Super Admin record
$superAdminUser = null;
if (!empty($_SESSION['superadmin_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, created_at FROM superadmin_users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['superadmin_id']]);
        $superAdminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Superadmin auth query error: " . $e->getMessage());
    }
}

if (!$superAdminUser) {
    // If user no longer exists, clear session and redirect
    unset($_SESSION['superadmin_logged_in'], $_SESSION['superadmin_id'], $_SESSION['superadmin_username']);
    header('Location: login.php');
    exit;
}
