<?php
/**
 * Helper functions for the GPD Reports application
 */

// Format number with commas
function formatNumber($number) {
    return number_format($number);
}

// Format date to readable format
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Get user's role
function getUserRole() {
    return isset($_SESSION['user_data']['role']) ? $_SESSION['user_data']['role'] : '';
}

// Check if user is admin
function isAdmin() {
    return getUserRole() === 'admin';
}

// Sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Generate random string
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

// Handle file upload
function handleFileUpload($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameters'];
    }

    // Check file errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File size exceeds limit'];
        default:
            return ['success' => false, 'message' => 'Unknown error occurred'];
    }

    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }

    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Generate unique filename
    $newFilename = generateRandomString() . '.' . $extension;
    $targetPath = $targetDir . '/' . $newFilename;

    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    return [
        'success' => true,
        'filename' => $newFilename,
        'path' => $targetPath
    ];
}

// Rearray files from multiple file upload
function reArrayFiles($files) {
    $file_array = array();
    $file_count = count($files['name']);
    $file_keys = array_keys($files);

    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_array[$i][$key] = $files[$key][$i];
        }
    }

    return $file_array;
}

// Upload file and return filename
function uploadFile($file, $targetDir) {
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Handle the file upload
    $result = handleFileUpload($file, $targetDir);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    return $result['filename'];
}

// Delete file
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Check if file is an image
function isImage($filename) {
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
    return in_array(getFileExtension($filename), $imageTypes);
}

// Get file icon based on extension
function getFileIcon($filename) {
    $extension = getFileExtension($filename);
    $icons = [
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'pdf' => 'fa-file-pdf',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image'
    ];
    
    return isset($icons[$extension]) ? $icons[$extension] : 'fa-file';
}

// Create upload directories if they don't exist
function createUploadDirectories() {
    $directories = [
        __DIR__ . '/../uploads',
        __DIR__ . '/../uploads/documents',
        __DIR__ . '/../uploads/pictures'
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// Initialize upload directories
createUploadDirectories();
