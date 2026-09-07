<?php
// Admin Image Upload Helper
function handleAdminImageUpload($fileKey, $targetSubfolder = '') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null; // No file uploaded or error
    }

    $file = $_FILES[$fileKey];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds 5MB limit.");
    }

    $fileMime = mime_content_type($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileMime, $allowedTypes) && !in_array($ext, $allowedExts)) {
        throw new Exception("Invalid image format. Allowed formats: JPG, PNG, WEBP.");
    }

    $uploadDir = UPLOAD_PATH;
    if (!empty($targetSubfolder)) {
        $uploadDir .= rtrim($targetSubfolder, '/') . '/';
    }

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFilename = uniqid('img_', true) . '.' . $ext;
    $targetPath = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = 'uploads/' . (!empty($targetSubfolder) ? rtrim($targetSubfolder, '/') . '/' : '') . $newFilename;
        return $relativePath;
    } else {
        throw new Exception("Failed to move uploaded file.");
    }
}

// Slug generator helper
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}
