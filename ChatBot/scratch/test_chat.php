<?php
require_once __DIR__ . '/../config/chat-config.php';

echo "Testing URL: " . OLLAMA_BASE_URL . "\n";
echo "Testing Model: " . OLLAMA_MODEL . "\n\n";

$payload = [
    'model' => OLLAMA_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful customer support agent for Reiki Bliss.'],
        ['role' => 'user', 'content' => 'What are your store timings?']
    ],
    'stream' => false
];

$ch = curl_init(rtrim(OLLAMA_BASE_URL, '/') . '/api/chat');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'ngrok-skip-browser-warning: true'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($err) {
    echo "cURL Error: $err\n";
} else {
    echo "Response Body:\n" . $response . "\n";
}
