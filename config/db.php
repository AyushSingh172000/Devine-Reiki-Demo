<?php
// Database configuration and PDO connection

// Check if running on Local XAMPP
$hostHeader = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$isLocal = (strpos($hostHeader, 'localhost') !== false || strpos($hostHeader, '127.0.0.1') !== false);

if ($isLocal) {
    // Localhost XAMPP
    $host = 'localhost';
    $port = 3306;
    $dbname = 'reiki_website';
    $username = 'root';
    $password = '';
} else {
    // InfinityFree Cloud (Default for live server)
    $host = 'sql206.infinityfree.com';
    $port = 3306;
    $dbname = 'if0_42861215_reikibliss';
    $username = 'if0_42861215';
    $password = 'Ayush171998';
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
