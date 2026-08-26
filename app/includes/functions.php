<?php
// Sanitize output — prevent XSS
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Validate file upload
function validateUpload($file) {
    $allowed_mime  = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $allowed_ext   = ['pdf', 'docx'];
    $max_size      = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Upload error. Please try again.";
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return "File too large. Maximum size is 5MB.";
    }

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        return "Invalid file type. Only PDF and DOCX allowed.";
    }

    // Check MIME type using finfo (more secure than trusting browser)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mime)) {
        return "Invalid file content. Only PDF and DOCX allowed.";
    }

    return null; // null = no error
}

// Generate safe unique filename
function generateFilename($original) {
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    return uniqid('file_', true) . '_' . time() . '.' . $ext;
}

// Flash message — set
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Flash message — get and clear
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Display flash message HTML
function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
        echo '<div class="flash ' . $type . '">' . e($flash['message']) . '</div>';
    }
}

// Format date nicely
function formatDate($timestamp) {
    return date('d M Y, h:i A', strtotime($timestamp));
}

// Role badge HTML
function roleBadge($role) {
    $colors = [
        'admin'      => '#e74c3c',
        'researcher' => '#3498db',
        'guest'      => '#95a5a6'
    ];
    $color = $colors[$role] ?? '#95a5a6';
    return '<span style="background:' . $color .
           ';color:white;padding:2px 8px;border-radius:4px;font-size:12px;">'
           . e(ucfirst($role)) . '</span>';
}
?>

