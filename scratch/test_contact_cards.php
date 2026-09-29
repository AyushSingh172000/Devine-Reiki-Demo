<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== VERIFYING CONTACT CARDS RENDERING ===\n";

ob_start();
include __DIR__ . '/../contact.php';
$html = ob_get_clean();

// Check Card 1
if (strpos($html, 'Office No. 305 Building Kusal bazar') !== false && strpos($html, 'View on Google Maps →') !== false) {
    echo "[PASS] Card 1 (Visit Us) has address and clickable Maps button!\n";
} else {
    echo "[FAIL] Card 1 missing address or Maps button!\n";
}

// Check Card 2
if (strpos($html, '+91 99716 55705') !== false && strpos($html, 'Chat on WhatsApp →') !== false && strpos($html, 'https://wa.me/919971655705') !== false) {
    echo "[PASS] Card 2 (Call / WhatsApp) has phone, WhatsApp and clickable WhatsApp button!\n";
} else {
    echo "[FAIL] Card 2 missing phone or WhatsApp button!\n";
}

// Check Card 3
if (strpos($html, 'anupama.snj@gmail.com') !== false && strpos($html, 'Send Email Inquiry →') !== false) {
    echo "[PASS] Card 3 (Email Us) has email and clickable Email button!\n";
} else {
    echo "[FAIL] Card 3 missing email or Email button!\n";
}

// Check Card 4
if (strpos($html, 'Monday - Saturday: 12:00 PM - 6:00 PM (IST)') !== false && strpos($html, 'Book Consultation →') !== false && strpos($html, 'https://calendar.app.google/HRtsYS3JLw8fJdoN9') !== false) {
    echo "[PASS] Card 4 (Center Hours) has hours and clickable booking button!\n";
} else {
    echo "[FAIL] Card 4 missing hours or booking button!\n";
}

echo "=== VERIFICATION COMPLETE ===\n";
