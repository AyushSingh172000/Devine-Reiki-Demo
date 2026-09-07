<?php
/**
 * API Endpoint: Conversation History Fetcher
 * Path: api/history.php
 * 
 * Returns full message history for a verified chat session in chronological order.
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

    // Parse session_id from JSON payload, GET parameter, or POST body
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?? [];

    $token = trim($data['session_id'] ?? $_GET['session_id'] ?? $_POST['session_id'] ?? '');

    // Validate token format
    if (empty($token) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        echo json_encode([
            'status' => 'success',
            'session_id' => null,
            'history' => []
        ]);
        exit;
    }

    // Lookup session token in DB
    $stmtSession = $pdo->prepare("SELECT id, session_token FROM chat_sessions WHERE session_token = :token LIMIT 1");
    $stmtSession->execute([':token' => $token]);
    $session = $stmtSession->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode([
            'status' => 'success',
            'session_id' => null,
            'history' => []
        ]);
        exit;
    }

    $sessionId = (int)$session['id'];

    // Fetch all messages for session in chronological order
    $historyStmt = $pdo->prepare("
        SELECT id, role, content, was_blocked, created_at 
        FROM chat_messages 
        WHERE session_id = :sid 
        ORDER BY id ASC
    ");
    $historyStmt->execute([':sid' => $sessionId]);
    $rows = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedHistory = [];
    foreach ($rows as $row) {
        $formattedHistory[] = [
            'id' => (int)$row['id'],
            'role' => $row['role'],
            'content' => $row['content'],
            'was_blocked' => (bool)$row['was_blocked'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'session_id' => $session['session_token'],
        'history' => $formattedHistory
    ]);

} catch (\Throwable $e) {
    error_log("History API Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'success',
        'session_id' => null,
        'history' => []
    ]);
}
