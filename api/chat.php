<?php
/**
 * API Endpoint: Ollama Chat Proxy & Scope Guard
 * Path: api/chat.php
 * 
 * Proxies messages from client JS widget to local Ollama LLM via Cloudflare tunnel,
 * maintaining context window, enforcing topic boundaries via backstop pattern filter,
 * handling conversational contact/booking slot collection, and saving conversation history to MySQL database.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Allow CORS from same-origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/chat-config.php';

    // Parse input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload.']);
        exit;
    }

    $token = trim($data['session_id'] ?? '');
    $userMessage = trim($data['message'] ?? '');

    // Validate inputs
    if (empty($token) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Valid session_id is required.']);
        exit;
    }

    if (empty($userMessage)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty.']);
        exit;
    }

    if (mb_strlen($userMessage) > CHAT_MAX_INPUT_LENGTH) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Message exceeds maximum length of ' . CHAT_MAX_INPUT_LENGTH . ' characters.'
        ]);
        exit;
    }

    // Lookup session in DB
    $stmtSession = $pdo->prepare("SELECT id, pending_form FROM chat_sessions WHERE session_token = :token LIMIT 1");
    $stmtSession->execute([':token' => $token]);
    $session = $stmtSession->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Session not found. Please refresh and try again.']);
        exit;
    }

    $sessionId = (int)$session['id'];
    $pendingFormJson = $session['pending_form'];

    // Rate Limiting Check
    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }

    $rateStmt = $pdo->prepare("
        SELECT COUNT(*) FROM chat_messages cm
        JOIN chat_sessions cs ON cm.session_id = cs.id
        WHERE cm.role = 'user' 
          AND (cm.session_id = :sid OR cs.ip_address = :ip)
          AND cm.created_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)
    ");
    $rateStmt->execute([
        ':sid' => $sessionId,
        ':ip' => $clientIp,
        ':window' => CHAT_RATE_LIMIT_WINDOW
    ]);
    $recentMsgCount = (int)$rateStmt->fetchColumn();

    if ($recentMsgCount >= CHAT_RATE_LIMIT_MAX) {
        http_response_code(429);
        echo json_encode([
            'status' => 'error',
            'message' => 'You are sending messages too quickly. Please wait a few minutes before trying again.'
        ]);
        exit;
    }

    // =========================================================================
    // Conversational Booking / Contact Form Filling State Machine
    // =========================================================================

    // 1. Check for cancellation keyword
    if (preg_match('/^(cancel|stop|nevermind|abort|exit)$/i', $userMessage)) {
        if (!empty($pendingFormJson)) {
            $updateSess = $pdo->prepare("UPDATE chat_sessions SET pending_form = NULL, last_active_at = NOW() WHERE id = :id");
            $updateSess->execute([':id' => $sessionId]);

            $cancelReply = "No problem! I have canceled the booking request. How else may I assist you today?";

            $pdo->beginTransaction();
            $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, 0, NOW())");
            $insertMsg->execute([':sid' => $sessionId, ':role' => 'user', ':content' => $userMessage]);
            $insertMsg->execute([':sid' => $sessionId, ':role' => 'assistant', ':content' => $cancelReply]);
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'reply' => $cancelReply,
                'session_id' => $token,
                'canceled' => true
            ]);
            exit;
        }
    }

    // 2. Active Form Filling Flow
    if (!empty($pendingFormJson)) {
        $formState = json_decode($pendingFormJson, true) ?? [];
        $step = $formState['step'] ?? 'name';
        $formData = $formState['data'] ?? [];

        $nextReply = '';
        $advanceStep = false;
        $isComplete = false;
        $validationError = null;

        if ($step === 'name') {
            if (mb_strlen($userMessage) < 2) {
                $validationError = "Please enter your full name (at least 2 characters).";
            } else {
                $formData['name'] = $userMessage;
                $formState['step'] = 'email';
                $formState['data'] = $formData;
                $nextReply = "Thank you, " . htmlspecialchars($formData['name']) . "! 🙏 What is your email address so we can send your session details?";
                $advanceStep = true;
            }
        } elseif ($step === 'email') {
            if (!filter_var($userMessage, FILTER_VALIDATE_EMAIL)) {
                $validationError = "That doesn't look like a valid email address. Please enter a valid email format (e.g. name@example.com).";
            } else {
                $formData['email'] = strtolower($userMessage);
                $formState['step'] = 'phone';
                $formState['data'] = $formData;
                $nextReply = "Got it! What is your Phone or WhatsApp number so Dr. Chirag or Binal Gajjar can reach out to you?";
                $advanceStep = true;
            }
        } elseif ($step === 'phone') {
            $digitsOnly = preg_replace('/[^\d]/', '', $userMessage);
            if (strlen($digitsOnly) < 7) {
                $validationError = "Please provide a valid phone or WhatsApp number (at least 7 digits).";
            } else {
                $formData['phone'] = $userMessage;
                $formState['step'] = 'message';
                $formState['data'] = $formData;
                $nextReply = "Great! Lastly, please share a brief description of your healing concern or inquiry (e.g., preferred time, course interest, or health goals).";
                $advanceStep = true;
            }
        } elseif ($step === 'message') {
            if (mb_strlen($userMessage) < 5) {
                $validationError = "Please enter a brief description of your inquiry (at least 5 characters).";
            } else {
                $formData['message'] = $userMessage;
                $isComplete = true;
            }
        }

        if ($validationError) {
            $pdo->beginTransaction();
            $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, 0, NOW())");
            $insertMsg->execute([':sid' => $sessionId, ':role' => 'user', ':content' => $userMessage]);
            $insertMsg->execute([':sid' => $sessionId, ':role' => 'assistant', ':content' => $validationError]);
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'reply' => $validationError,
                'session_id' => $token,
                'in_form' => true,
                'step' => $step
            ]);
            exit;
        }

        if ($isComplete) {
            // Submit data to existing contact form endpoint / DB table contact_inquiries
            try {
                $stmtContact = $pdo->prepare("INSERT INTO contact_inquiries (name, email, phone, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                $stmtContact->execute([
                    $formData['name'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['message']
                ]);

                // Clear pending_form state
                $updateSess = $pdo->prepare("UPDATE chat_sessions SET pending_form = NULL, last_active_at = NOW() WHERE id = :id");
                $updateSess->execute([':id' => $sessionId]);

                $confirmReply = "Thank you " . htmlspecialchars($formData['name']) . "! ✨ Your message and booking request have been submitted successfully. Dr. Chirag or Binal Gajjar will respond to your inquiry shortly via WhatsApp (" . htmlspecialchars($formData['phone']) . ") or email (" . htmlspecialchars($formData['email']) . ").";

                $pdo->beginTransaction();
                $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, 0, NOW())");
                $insertMsg->execute([':sid' => $sessionId, ':role' => 'user', ':content' => $userMessage]);
                $insertMsg->execute([':sid' => $sessionId, ':role' => 'assistant', ':content' => $confirmReply]);
                $pdo->commit();

                echo json_encode([
                    'status' => 'success',
                    'reply' => $confirmReply,
                    'session_id' => $token,
                    'submitted' => true
                ]);
                exit;

            } catch (\PDOException $e) {
                error_log("Contact submission error from Chatbot: " . $e->getMessage());
                $failReply = "I encountered an error submitting your request. Please try again or WhatsApp us directly at +91 97265 81787.";
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $failReply
                ]);
                exit;
            }
        }

        if ($advanceStep) {
            $updateSess = $pdo->prepare("UPDATE chat_sessions SET pending_form = :pform, last_active_at = NOW() WHERE id = :id");
            $updateSess->execute([':pform' => json_encode($formState), ':id' => $sessionId]);

            $pdo->beginTransaction();
            $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, 0, NOW())");
            $insertMsg->execute([':sid' => $sessionId, ':role' => 'user', ':content' => $userMessage]);
            $insertMsg->execute([':sid' => $sessionId, ':role' => 'assistant', ':content' => $nextReply]);
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'reply' => $nextReply,
                'session_id' => $token,
                'in_form' => true,
                'step' => $formState['step']
            ]);
            exit;
        }
    }

    // 3. Detect Booking/Contact Intent (Initial Trigger)
    $isBookingIntent = false;
    if (isset($CHAT_BOOKING_INTENT_PATTERNS) && is_array($CHAT_BOOKING_INTENT_PATTERNS)) {
        foreach ($CHAT_BOOKING_INTENT_PATTERNS as $bPattern) {
            if (preg_match($bPattern, $userMessage)) {
                $isBookingIntent = true;
                break;
            }
        }
    }

    if ($isBookingIntent) {
        // Initialize form filling state in DB
        $initialFormState = [
            'step' => 'name',
            'data' => []
        ];

        $updateSess = $pdo->prepare("UPDATE chat_sessions SET pending_form = :pform, last_active_at = NOW() WHERE id = :id");
        $updateSess->execute([':pform' => json_encode($initialFormState), ':id' => $sessionId]);

        $promptReply = "I would be delighted to help you book a session or get in touch with our Reiki Masters! May I please have your full name?";

        $pdo->beginTransaction();
        $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, 0, NOW())");
        $insertMsg->execute([':sid' => $sessionId, ':role' => 'user', ':content' => $userMessage]);
        $insertMsg->execute([':sid' => $sessionId, ':role' => 'assistant', ':content' => $promptReply]);
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'reply' => $promptReply,
            'session_id' => $token,
            'in_form' => true,
            'step' => 'name'
        ]);
        exit;
    }

    // =========================================================================
    // Defense-in-Depth Tier 1: Local Backstop Keyword Filter
    // =========================================================================
    $isBlockedByPattern = false;
    if (isset($CHAT_BLOCKED_PATTERNS) && is_array($CHAT_BLOCKED_PATTERNS)) {
        foreach ($CHAT_BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $userMessage)) {
                $isBlockedByPattern = true;
                break;
            }
        }
    }

    if ($isBlockedByPattern) {
        $refusalReply = CHAT_REFUSAL_MESSAGE;

        $pdo->beginTransaction();
        $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, :blocked, NOW())");
        
        $insertMsg->execute([
            ':sid' => $sessionId,
            ':role' => 'user',
            ':content' => $userMessage,
            ':blocked' => 1
        ]);
        $insertMsg->execute([
            ':sid' => $sessionId,
            ':role' => 'assistant',
            ':content' => $refusalReply,
            ':blocked' => 1
        ]);

        $updateSess = $pdo->prepare("UPDATE chat_sessions SET last_active_at = NOW() WHERE id = :id");
        $updateSess->execute([':id' => $sessionId]);
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'reply' => $refusalReply,
            'session_id' => $token,
            'blocked' => true
        ]);
        exit;
    }

    // =========================================================================
    // Defense-in-Depth Tier 2: LLM Proxy with Strict System Prompt
    // =========================================================================

    // Load last N messages from database for conversation context
    $histStmt = $pdo->prepare("
        SELECT role, content 
        FROM (
            SELECT id, role, content, created_at 
            FROM chat_messages 
            WHERE session_id = :sid 
            ORDER BY id DESC 
            LIMIT :max_hist
        ) sub 
        ORDER BY id ASC
    ");
    $histStmt->bindValue(':sid', $sessionId, PDO::PARAM_INT);
    $histStmt->bindValue(':max_hist', CHAT_MAX_HISTORY, PDO::PARAM_INT);
    $histStmt->execute();
    $historyRows = $histStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build message array for Ollama API - System prompt prepended on EVERY call
    $ollamaMessages = [];

    $ollamaMessages[] = [
        'role' => 'system',
        'content' => CHAT_SYSTEM_PROMPT
    ];

    foreach ($historyRows as $row) {
        $ollamaMessages[] = [
            'role' => $row['role'],
            'content' => $row['content']
        ];
    }

    $ollamaMessages[] = [
        'role' => 'user',
        'content' => $userMessage
    ];

    // Construct cURL payload
    $ollamaPayload = [
        'model' => OLLAMA_MODEL,
        'messages' => $ollamaMessages,
        'stream' => false
    ];

    $ollamaEndpoint = rtrim(OLLAMA_BASE_URL, '/') . '/api/chat';

    $ch = curl_init($ollamaEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($ollamaPayload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => CHAT_CURL_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $responseRaw = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErrno !== 0) {
        error_log("Ollama cURL Error ($curlErrno): $curlError");
        $statusCode = ($curlErrno === CURLE_OPERATION_TIMEDOUT) ? 504 : 502;
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'error',
            'message' => 'Connection to AI service timed out or failed. Please ensure Ollama tunnel is active.'
        ]);
        exit;
    }

    if ($httpCode !== 200) {
        error_log("Ollama HTTP Error Code: $httpCode | Body: $responseRaw");
        http_response_code(502);
        echo json_encode([
            'status' => 'error',
            'message' => "AI service returned status HTTP $httpCode. Please try again later."
        ]);
        exit;
    }

    $responseJson = json_decode($responseRaw, true);
    $assistantReply = trim($responseJson['message']['content'] ?? '');

    if (empty($assistantReply)) {
        error_log("Ollama Malformed Response: " . $responseRaw);
        http_response_code(502);
        echo json_encode([
            'status' => 'error',
            'message' => 'Empty or invalid response from AI model.'
        ]);
        exit;
    }

    // Determine if model issued a refusal
    $wasModelRefusal = 0;
    if (
        strpos($assistantReply, "I'm only able to help with questions about") !== false ||
        strpos($assistantReply, "general-purpose resource") !== false ||
        $assistantReply === CHAT_REFUSAL_MESSAGE
    ) {
        $wasModelRefusal = 1;
    }

    // Save user message and assistant reply to MySQL inside transaction
    $pdo->beginTransaction();

    $insertMsg = $pdo->prepare("INSERT INTO chat_messages (session_id, role, content, was_blocked, created_at) VALUES (:sid, :role, :content, :blocked, NOW())");
    
    // Save user message
    $insertMsg->execute([
        ':sid' => $sessionId,
        ':role' => 'user',
        ':content' => $userMessage,
        ':blocked' => $wasModelRefusal
    ]);

    // Save assistant reply
    $insertMsg->execute([
        ':sid' => $sessionId,
        ':role' => 'assistant',
        ':content' => $assistantReply,
        ':blocked' => $wasModelRefusal
    ]);

    // Update session activity
    $updateSess = $pdo->prepare("UPDATE chat_sessions SET last_active_at = NOW() WHERE id = :id");
    $updateSess->execute([':id' => $sessionId]);

    $pdo->commit();

    // Return response to client
    echo json_encode([
        'status' => 'success',
        'reply' => $assistantReply,
        'session_id' => $token,
        'blocked' => (bool)$wasModelRefusal
    ]);

} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Chat API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected server error occurred. Please try again later.'
    ]);
}
