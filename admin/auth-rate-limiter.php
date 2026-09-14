<?php
/**
 * Admin Authentication Rate Limiter
 * Database-backed rate limiting by IP Address and Admin Username
 * Protects against brute-force attacks and credential stuffing
 */

if (!defined('RATE_LIMIT_LOADED')) {
    define('RATE_LIMIT_LOADED', true);
}

if (!defined('RATE_LIMIT_MAX_ATTEMPTS')) {
    define('RATE_LIMIT_MAX_ATTEMPTS', 5);
}

if (!defined('RATE_LIMIT_WINDOW_SECONDS')) {
    define('RATE_LIMIT_WINDOW_SECONDS', 120); // 2 minutes lockout
}

/**
 * Safely extracts client IP address supporting Cloudflare, Nginx reverse proxy, and standard headers
 */
function getClientIpAddress(): string {
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Reverse Proxy / Nginx
        'HTTP_X_REAL_IP',            // Nginx proxy_set_header
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'                // Standard connection
    ];

    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $rawList = explode(',', $_SERVER[$header]);
            foreach ($rawList as $rawIp) {
                $trimmedIp = trim($rawIp);
                if (filter_var($trimmedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $trimmedIp;
                }
                // Also accept valid local IPs (127.0.0.1, ::1) when developing locally
                if (filter_var($trimmedIp, FILTER_VALIDATE_IP)) {
                    return $trimmedIp;
                }
            }
        }
    }

    return '127.0.0.1';
}

/**
 * Ensures the login_attempts table exists in MySQL automatically
 */
function ensureLoginAttemptsTable(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `username` VARCHAR(100) NOT NULL,
        `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `is_successful` TINYINT(1) DEFAULT 0,
        INDEX `idx_ip_attempt` (`ip_address`, `attempted_at`),
        INDEX `idx_user_attempt` (`username`, `attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $pdo->exec($sql);
        $checked = true;
    } catch (PDOException $e) {
        error_log("Failed to ensure login_attempts table: " . $e->getMessage());
    }
}

/**
 * Checks if the current request is rate-limited by either IP or username.
 *
 * @param PDO $pdo
 * @param string $ip
 * @param string $username
 * @param int $maxAttempts Maximum allowed failures within window (default 5)
 * @param int $windowSeconds Sliding window / lockout duration in seconds (default 120 = 2 mins)
 * @return array ['is_locked' => bool, 'remaining_attempts' => int, 'retry_after' => int, 'failed_count' => int]
 */
function checkLoginRateLimit(PDO $pdo, string $ip, string $username, int $maxAttempts = RATE_LIMIT_MAX_ATTEMPTS, int $windowSeconds = RATE_LIMIT_WINDOW_SECONDS): array {
    ensureLoginAttemptsTable($pdo);

    // 1. Query failed attempts by IP within sliding window
    $ipStmt = $pdo->prepare("SELECT COUNT(*) as failed_count, 
            MAX(UNIX_TIMESTAMP(attempted_at)) as last_attempt_ts,
            UNIX_TIMESTAMP(NOW()) as current_ts 
        FROM login_attempts 
        WHERE ip_address = ? AND is_successful = 0 AND attempted_at >= (NOW() - INTERVAL ? SECOND)");
    $ipStmt->execute([$ip, $windowSeconds]);
    $ipData = $ipStmt->fetch(PDO::FETCH_ASSOC);
    $ipFailedCount = (int)($ipData['failed_count'] ?? 0);
    $ipLastAttempt = (int)($ipData['last_attempt_ts'] ?? 0);
    $currentTs = (int)($ipData['current_ts'] ?? time());

    // 2. Query failed attempts by target username (if provided)
    $userFailedCount = 0;
    $userLastAttempt = 0;
    if (!empty($username)) {
        $userStmt = $pdo->prepare("SELECT COUNT(*) as failed_count, 
                MAX(UNIX_TIMESTAMP(attempted_at)) as last_attempt_ts,
                UNIX_TIMESTAMP(NOW()) as current_ts 
            FROM login_attempts 
            WHERE username = ? AND is_successful = 0 AND attempted_at >= (NOW() - INTERVAL ? SECOND)");
        $userStmt->execute([$username, $windowSeconds]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userFailedCount = (int)($userData['failed_count'] ?? 0);
        $userLastAttempt = (int)($userData['last_attempt_ts'] ?? 0);
        if (!empty($userData['current_ts'])) {
            $currentTs = (int)$userData['current_ts'];
        }
    }

    $failedCount = max($ipFailedCount, $userFailedCount);
    $lastAttempt = max($ipLastAttempt, $userLastAttempt);

    if ($failedCount >= $maxAttempts) {
        $elapsedSinceLast = max(0, $currentTs - $lastAttempt);
        $retryAfter = max(1, $windowSeconds - $elapsedSinceLast);

        return [
            'is_locked' => true,
            'remaining_attempts' => 0,
            'retry_after' => $retryAfter,
            'failed_count' => $failedCount
        ];
    }

    return [
        'is_locked' => false,
        'remaining_attempts' => max(0, $maxAttempts - $failedCount),
        'retry_after' => 0,
        'failed_count' => $failedCount
    ];
}

/**
 * Records an authentication attempt into the database
 */
function recordLoginAttempt(PDO $pdo, string $ip, string $username, bool $isSuccessful): void {
    ensureLoginAttemptsTable($pdo);

    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, is_successful, attempted_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$ip, substr($username, 0, 100), $isSuccessful ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Failed to record login attempt: " . $e->getMessage());
    }
}

/**
 * Clears failed attempts upon successful login for this IP and username
 */
function clearFailedLoginAttempts(PDO $pdo, string $ip, string $username): void {
    ensureLoginAttemptsTable($pdo);

    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE (ip_address = ? OR username = ?) AND is_successful = 0");
        $stmt->execute([$ip, $username]);
    } catch (PDOException $e) {
        error_log("Failed to clear failed login attempts: " . $e->getMessage());
    }
}

/**
 * Prunes old attempt records older than the given hours (default 48h)
 */
function pruneOldLoginAttempts(PDO $pdo, int $hours = 48): void {
    // Only run occasionally (1 in 20 requests) to keep overhead minimal
    if (mt_rand(1, 20) !== 1) {
        return;
    }

    ensureLoginAttemptsTable($pdo);

    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL ? HOUR)");
        $stmt->execute([$hours]);
    } catch (PDOException $e) {
        error_log("Failed to prune old login attempts: " . $e->getMessage());
    }
}
