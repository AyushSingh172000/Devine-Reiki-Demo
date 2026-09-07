<?php
// API Endpoint - Contact Form Submission Handler
header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Sanitize & Validate Inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (empty($phone) || strlen($phone) < 7) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
    exit;
}

if (empty($message) || strlen($message) < 5) {
    echo json_encode(['success' => false, 'message' => 'Please enter your message or inquiry.']);
    exit;
}

// Insert Inquiry into Database
try {
    $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, email, phone, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$name, $email, $phone, $message]);

    echo json_encode([
        'success' => true, 
        'message' => 'Thank you ' . htmlspecialchars($name) . '! Your message has been sent successfully. Reiki Grandmaster Anupama Agrawal will respond to you shortly.'
    ]);
} catch (PDOException $e) {
    error_log("Database insertion error in contact-submit.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while saving your message. Please try again or WhatsApp us directly.']);
}
