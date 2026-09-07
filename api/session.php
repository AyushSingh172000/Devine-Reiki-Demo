<?php
/**
 * API Endpoint: Chat Session Manager
 * Path: api/session.php
 * 
 * Creates or validates a chat session token.
 * Automatically inserts the initial welcome message upon brand-new session creation.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Allow CORS from same-origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/chat-config.php';

    // Parse input (support JSON POST body or GET parameter)
    $input = json_decode(file_get_contents('php://input'), true);
    $token = trim($input['session_id'] ?? $_GET['session_id'] ?? $_POST['session_id'] ?? '');

    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }

    $existingSession = null;

    if (!empty($token) && preg_match('/^[a-f0-9]{64}$/i', $token)) {
        $stmt = $pdo->prepare("SELECT id, session_token FROM chat_sessions WHERE session_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $existingSession = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($existingSession) {
        // Update last_active_at
        $updateStmt = $pdo->prepare("UPDATE chat_sessions SET last_active_at = NOW(), ip_address = :ip WHERE id = :id");
        $updateStmt->execute([':ip' => $clientIp, ':id' => $existingSession['id']]);

        echo json_encode([
            'status' => 'success',
            'session_id' => $existingSession['session_token'],
            'is_new' => false
        ]);
        exit;
    }

    // Generate new random 64-character token
    $newToken = bin2hex(random_bytes(32));

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("INSERT INTO chat_sessions (session_token, ip_address, created_at, last_active_at) VALUES (:token, :ip, NOW(), NOW())");
    $insertStmt->execute([
        ':token' => $newToken,
        ':ip' => $clientIp
    ]);

    $newSessionDbId = (int)$pdo->lastInsertId();

    // Insert welcome message into chat_messages for new session
    if (defined('CHAT_WELCOME_MESSAGE') && !empty(CHAT_WELCOME_MESSAGE)) {
        $welcomeStmt = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, 'assistant', :content, 0, NOW())");
        $welcomeStmt->execute([
            ':sid' => $newSessionDbId,
            ':content' => CHAT_WELCOME_MESSAGE
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'session_id' => $newToken,
        'is_new' => true
    ]);

} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Session API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to initialize chat session. Please try again.'
    ]);
}
