<?php
// API Endpoint - Contact Form Submission Handler
require_once __DIR__ . '/../config/constants.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = require __DIR__ . '/../config/db.php';
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

function sendContactResponse($success, $message, $whatsappUrl = '', $isAjax = true) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'whatsapp_url' => $whatsappUrl
        ]);
        exit;
    } else {
        if ($success && !empty($whatsappUrl)) {
            header("Location: " . $whatsappUrl);
            exit;
        } else {
            $redirectUrl = BASE_URL . 'contact?' . ($success ? 'success=' : 'error=') . urlencode($message) . '#book-form';
            header("Location: " . $redirectUrl);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendContactResponse(false, 'Invalid request method.', '', $isAjax);
}

// Support both form-data/x-www-form-urlencoded and JSON payload
$postData = $_POST;
if (empty($postData)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            $postData = $json;
        }
    }
}

// Sanitize & Validate Inputs
$name = trim($postData['name'] ?? '');
$email = trim($postData['email'] ?? '');
$phone = trim($postData['phone'] ?? '');
$message = trim($postData['message'] ?? '');
$orderType = trim($postData['order_type'] ?? '');
$itemName = trim($postData['item_name'] ?? '');

// Auto-detect order_type from message if not explicitly sent
if (empty($orderType)) {
    if (stripos($message, 'order the ') !== false || stripos($message, 'would like to order') !== false) {
        $orderType = 'product';
    } elseif (stripos($message, 'enrolling in the ') !== false || stripos($message, 'certification course') !== false) {
        $orderType = 'course';
    }
}

if (empty($name) || strlen($name) < 2) {
    sendContactResponse(false, 'Please enter your full name.', '', $isAjax);
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendContactResponse(false, 'Please enter a valid email address.', '', $isAjax);
}
if (empty($email)) {
    $email = 'N/A';
}

if (empty($phone) || strlen($phone) < 7) {
    sendContactResponse(false, 'Please enter a valid phone number.', '', $isAjax);
}

// Message is optional - if empty, fallback to 'General Inquiry'
$dbMessage = !empty($message) ? $message : 'General Inquiry';
$waMessageText = !empty($message) ? $message : 'General Inquiry';

// Insert into Database
try {
    // 1. Always record into contact_inquiries
    $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, email, phone, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$name, $email, $phone, $dbMessage]);

    // 2. If it is a Product or Course order, record it in bracelet_orders for the Orders page
    if (in_array($orderType, ['product', 'course'])) {
        $orderIntention = !empty($itemName) ? $itemName : ($orderType === 'product' ? 'Product Order' : 'Course Enrollment');
        $ordStmt = $pdo->prepare("INSERT INTO bracelet_orders (order_type, name, email, phone, whatsapp, intention, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $ordStmt->execute([$orderType, $name, $email, $phone, $phone, $orderIntention, $dbMessage]);
    }

    // Build WhatsApp URL to Client/Admin WhatsApp number
    $rawWa = !empty($siteSettings['whatsapp']) ? $siteSettings['whatsapp'] : (defined('SITE_WHATSAPP') ? SITE_WHATSAPP : '919971655705');
    $cleanWa = preg_replace('/[^0-9]/', '', $rawWa);
    if (empty($cleanWa)) {
        $cleanWa = '919971655705';
    }

    $siteBrand = defined('SITE_NAME') ? SITE_NAME : 'Reiki Bliss';
    if ($orderType === 'product') {
        $headerTag = "🛍️ *New Product Order - " . $siteBrand . "*";
        $successMsg = 'Thank you ' . htmlspecialchars($name) . '! Your product order has been placed successfully.';
    } elseif ($orderType === 'course') {
        $headerTag = "🎓 *New Course Enrollment - " . $siteBrand . "*";
        $successMsg = 'Thank you ' . htmlspecialchars($name) . '! Your course enrollment inquiry has been sent.';
    } else {
        $headerTag = "✨ *New Website Inquiry - " . $siteBrand . "*";
        $successMsg = 'Thank you ' . htmlspecialchars($name) . '! Your message has been sent successfully.';
    }

    $waText = $headerTag . "\n\n"
            . "👤 *Name:* " . $name . "\n"
            . "📱 *Phone:* " . $phone . "\n";
    if (!empty($itemName)) {
        $waText .= "📦 *Item:* " . $itemName . "\n";
    }
    $waText .= "💬 *Message:*\n" . $waMessageText;

    $waUrl = "https://wa.me/" . $cleanWa . "?text=" . urlencode($waText);

    sendContactResponse(true, $successMsg, $waUrl, $isAjax);
} catch (Throwable $e) {
    error_log("Database insertion error in contact-submit.php: " . $e->getMessage());
    sendContactResponse(false, 'An error occurred while saving your message. Please try again or WhatsApp us directly.', '', $isAjax);
}
