<?php
// API Endpoint - Custom Bracelet Order Submission Handler
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
$orderType = trim($_POST['order_type'] ?? 'birth-chart');
if (!in_array($orderType, ['birth-chart', 'customized'])) {
    $orderType = 'birth-chart';
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? $phone);
$dob = trim($_POST['date_of_birth'] ?? null);
$tob = trim($_POST['time_of_birth'] ?? null);
$pob = trim($_POST['place_of_birth'] ?? null);
$intention = trim($_POST['intention'] ?? null);
$customIntention = trim($_POST['custom_intention'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($intention === 'Custom Intention' && !empty($customIntention)) {
    $intention = 'Custom: ' . $customIntention;
}

if (empty($name) || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter your full name.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (empty($phone) || strlen($phone) < 7) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid phone or WhatsApp number.']);
    exit;
}

if ($orderType === 'birth-chart') {
    if (empty($dob)) {
        echo json_encode(['success' => false, 'message' => 'Please select your Date of Birth.']);
        exit;
    }
    if (empty($pob)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your Place of Birth.']);
        exit;
    }
} else {
    if (empty($intention)) {
        echo json_encode(['success' => false, 'message' => 'Please select your primary intention for the bracelet.']);
        exit;
    }
}

// Build WhatsApp follow-up URL
$waSummary = "Hello Anupama Agrawal, I have submitted my " . ($orderType === 'birth-chart' ? 'Birth Chart' : 'Custom Intention') . " bracelet order!\n\n";
$waSummary .= "Name: " . $name . "\nPhone: " . $phone . "\n";
if ($orderType === 'birth-chart') {
    $waSummary .= "DOB: " . $dob . "\nTime: " . ($tob ?: 'Not specified') . "\nPlace: " . $pob . "\n";
} else {
    $waSummary .= "Intention: " . $intention . "\n";
}
if (!empty($message)) {
    $waSummary .= "Notes: " . $message . "\n";
}
$waSummary .= "Please confirm my custom gemstone recommendation.";

$waUrl = "https://wa.me/919971655705?text=" . urlencode($waSummary);

// Insert into Database
try {
    $stmt = $pdo->prepare("INSERT INTO bracelet_orders (order_type, name, email, phone, whatsapp, date_of_birth, time_of_birth, place_of_birth, intention, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([
        $orderType,
        $name,
        $email,
        $phone,
        $whatsapp,
        !empty($dob) ? $dob : null,
        !empty($tob) ? $tob : null,
        !empty($pob) ? $pob : null,
        !empty($intention) ? $intention : null,
        !empty($message) ? $message : null
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you ' . htmlspecialchars($name) . '! Your custom bracelet order details have been saved.',
        'whatsapp_url' => $waUrl
    ]);
} catch (PDOException $e) {
    error_log("Database error in order-submit.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while saving your order. Please contact us on WhatsApp directly.']);
}
