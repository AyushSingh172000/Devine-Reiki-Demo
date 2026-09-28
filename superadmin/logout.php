<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset(
    $_SESSION['superadmin_logged_in'],
    $_SESSION['superadmin_id'],
    $_SESSION['superadmin_username'],
    $_SESSION['superadmin_full_name'],
    $_SESSION['superadmin_csrf_token']
);

header('Location: login.php');
exit;
