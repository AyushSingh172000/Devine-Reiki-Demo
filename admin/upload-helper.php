<?php
/**
 * Image Upload & Utility Helper Functions
 * Divine Reiki Admin Panel
 */

/**
 * Upload an image file with validation
 *
 * @param array $file $_FILES item array (e.g. $_FILES['image'])
 * @param string $targetDir Destination directory path
 * @param int $maxSize Maximum allowed file size in bytes (default 5MB)
 * @return array ['success' => true, 'path' => string, 'filename' => string] or ['success' => false, 'error' => string]
 */
function uploadImage($file, $targetDir = '../uploads/', $maxSize = 5242880) {
    // 1. Validate file presence & upload errors
    if (!isset($file) || !is_array($file) || !isset($file['error'])) {
        return ['success' => false, 'error' => 'No file uploaded or invalid file payload.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        return ['success' => false, 'error' => $uploadErrors[$file['error']] ?? 'Unknown upload error occurred.'];
    }

    // 2. Validate file size (default 5MB max)
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of 5MB.'];
    }

    // 3. Validate file type and extension
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowedMimes = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Invalid file extension. Only JPG, JPEG, PNG, WEBP, and GIF are allowed.'];
    }

    if (function_exists('mime_content_type') && !empty($file['tmp_name'])) {
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowedMimes)) {
            return ['success' => false, 'error' => 'Invalid image MIME type: ' . htmlspecialchars($mime)];
        }
    }

    // 4. Ensure target directory exists
    $targetDir = rtrim($targetDir, '/\\') . '/';
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload destination directory.'];
        }
    }

    // 5. Generate unique filename: time() . '_' . sanitized original name
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $originalName));
    if (empty($sanitized)) {
        $sanitized = 'upload';
    }
    $filename = time() . '_' . $sanitized . '.' . $ext;
    $destination = $targetDir . $filename;

    // 6. Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Return relative path for web storage
        $cleanPath = str_replace('\\', '/', $destination);
        // Normalize relative path if starting with ../
        if (strpos($cleanPath, '../') === 0) {
            $relativePath = substr($cleanPath, 3);
        } else {
            $relativePath = $cleanPath;
        }

        return [
            'success'  => true,
            'path'     => $relativePath,
            'filename' => $filename,
            'full_path'=> $destination
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file to destination directory.'];
    }
}

/**
 * Delete image file from disk if it exists
 *
 * @param string $path File path (relative or absolute)
 * @return bool True if deleted or already gone, false on failure
 */
function deleteImage($path) {
    if (empty($path)) {
        return false;
    }

    // Attempt direct path
    if (file_exists($path) && is_file($path)) {
        return @unlink($path);
    }

    // Attempt relative to project root
    $rootPath = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $path), '/');
    if (file_exists($rootPath) && is_file($rootPath)) {
        return @unlink($rootPath);
    }

    // Attempt relative to admin folder
    $adminRel = __DIR__ . '/' . ltrim(str_replace('\\', '/', $path), '/');
    if (file_exists($adminRel) && is_file($adminRel)) {
        return @unlink($adminRel);
    }

    return false;
}

/**
 * Generate a clean, SEO-friendly URL slug
 *
 * @param string $text Raw input text
 * @return string Clean slug
 */
function generateSlug($text) {
    // Convert to lowercase
    $text = mb_strtolower(trim($text), 'UTF-8');
    // Replace non letters or digits by hyphens
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate if possible
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim hyphens from ends
    $text = trim($text, '-');
    // Remove duplicate hyphens
    $text = preg_replace('~-+~', '-', $text);

    return empty($text) ? 'n-a' : $text;
}

/**
 * Backward-compatibility wrapper for existing admin scripts
 */
function handleAdminImageUpload($fileKey, $targetSubfolder = '') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $targetDir = '../uploads/' . (!empty($targetSubfolder) ? trim($targetSubfolder, '/') . '/' : '');
    $res = uploadImage($_FILES[$fileKey], $targetDir);

    if ($res['success']) {
        return $res['path'];
    } else {
        throw new Exception($res['error']);
    }
}
