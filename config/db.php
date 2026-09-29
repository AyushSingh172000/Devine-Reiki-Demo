<?php
// Database configuration and PDO connection

// Check if running on Local XAMPP
$hostHeader = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$isLocal = (empty($hostHeader) || php_sapi_name() === 'cli' || strpos($hostHeader, 'localhost') !== false || strpos($hostHeader, '127.0.0.1') !== false);

if ($isLocal) {
    // Localhost XAMPP
    $host = 'localhost';
    $port = 3306;
    $dbname = 'reiki_website';
    $username = 'root';
    $password = '';
} else {
    // Production / Hostinger Server (reikiblisswellness.com)
    // On Hostinger: Host is typically 'localhost' or your Hostinger MySQL hostname
    $host = 'localhost';
    $port = 3306;
    $dbname = 'u616755674_reiki_bliss';
    $username = 'u616755674_reiki_bliss';
    $password = 'Reiki_bliss@123';
}

$charset = 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET NAMES utf8mb4");
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

return $pdo;
